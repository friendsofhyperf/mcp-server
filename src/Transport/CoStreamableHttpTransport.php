<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\McpServer\Transport;

use Http\Discovery\Psr17FactoryDiscovery;
use Hyperf\Context\Context;
use Hyperf\Context\RequestContext;
use JsonException;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Server\Transport\BaseTransport;
use Mcp\Server\Transport\CallbackStream;
use Mcp\Server\Transport\TransportInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Swow\Psr7\Message\ServerRequestPlusInterface;
use Symfony\Component\Uid\Uuid;
use Throwable;

/**
 * @implements TransportInterface<ResponseInterface>
 *
 * */
class CoStreamableHttpTransport extends BaseTransport implements TransportInterface
{
    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private ?string $immediateResponse = null;

    private ?int $immediateStatusCode = null;

    /** @var array<string, string> */
    private array $corsHeaders;

    /**
     * @param array<string, string> $corsHeaders
     */
    public function __construct(
        ?ResponseFactoryInterface $responseFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        array $corsHeaders = [],
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct($logger);

        $this->responseFactory = $responseFactory ?? Psr17FactoryDiscovery::findResponseFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();

        $this->corsHeaders = array_merge([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Mcp-Session-Id, Mcp-Protocol-Version, Last-Event-ID, Authorization, Accept',
        ], $corsHeaders);
    }

    public function initialize(): void
    {
    }

    public function send(string $data, array $context): void
    {
        $this->immediateResponse = $data;
        $this->immediateStatusCode = $context['status_code'] ?? 200;
    }

    public function listen(): ResponseInterface
    {
        return match ($this->getRequest()?->getMethod()) {
            'OPTIONS' => $this->handleOptionsRequest(),
            'POST' => $this->handlePostRequest(),
            'DELETE' => $this->handleDeleteRequest(),
            default => $this->createErrorResponse(Error::forInvalidRequest('Method Not Allowed'), 405),
        };
    }

    protected function handleOptionsRequest(): ResponseInterface
    {
        return $this->withCorsHeaders($this->responseFactory->createResponse(204));
    }

    protected function handlePostRequest(): ResponseInterface
    {
        $request = $this->getRequest();
        $body = $request?->getBody()->getContents();
        $this->handleMessage($body, $this->getSessionId());

        if ($this->immediateResponse !== null) {
            $response = $this->responseFactory->createResponse($this->immediateStatusCode ?? 200)
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($this->immediateResponse));

            return $this->withCorsHeaders($response);
        }

        if ($this->sessionFiber !== null) {
            $this->logger->info('Fiber suspended, handling via SSE.');

            return $this->createStreamedResponse();
        }

        return $this->createJsonResponse();
    }

    protected function handleDeleteRequest(): ResponseInterface
    {
        if (! $this->getSessionId()) {
            return $this->createErrorResponse(Error::forInvalidRequest('Mcp-Session-Id header is required.'), 400);
        }

        $this->handleSessionEnd($this->getSessionId());

        return $this->withCorsHeaders($this->responseFactory->createResponse(204));
    }

    protected function createJsonResponse(): ResponseInterface
    {
        $outgoingMessages = $this->getOutgoingMessages($this->getSessionId());

        if (empty($outgoingMessages)) {
            return $this->withCorsHeaders($this->responseFactory->createResponse(202));
        }

        $messages = array_column($outgoingMessages, 'message');
        $responseBody = \count($messages) === 1 ? $messages[0] : '[' . implode(',', $messages) . ']';

        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($responseBody));

        if ($this->getSessionId()) {
            $response = $response->withHeader('Mcp-Session-Id', $this->getSessionId()->toRfc4122());
        }

        return $this->withCorsHeaders($response);
    }

    protected function createStreamedResponse(): ResponseInterface
    {
        $callback = function (): void {
            try {
                $this->logger->info('SSE: Starting request processing loop');

                while ($this->sessionFiber->isSuspended()) {
                    $this->flushOutgoingMessages($this->getSessionId());

                    $pendingRequests = $this->getPendingRequests($this->getSessionId());

                    if (empty($pendingRequests)) {
                        $yielded = $this->sessionFiber->resume();
                        $this->handleFiberYield($yielded, $this->getSessionId());
                        continue;
                    }

                    $resumed = false;
                    foreach ($pendingRequests as $pending) {
                        $requestId = $pending['request_id'];
                        $timestamp = $pending['timestamp'];
                        $timeout = $pending['timeout'] ?? 120;

                        $response = $this->checkForResponse($requestId, $this->getSessionId());

                        if ($response !== null) {
                            $yielded = $this->sessionFiber->resume($response);
                            $this->handleFiberYield($yielded, $this->getSessionId());
                            $resumed = true;
                            break;
                        }

                        if (time() - $timestamp >= $timeout) {
                            $error = Error::forInternalError('Request timed out', $requestId);
                            $yielded = $this->sessionFiber->resume($error);
                            $this->handleFiberYield($yielded, $this->getSessionId());
                            $resumed = true;
                            break;
                        }
                    }

                    if (! $resumed) {
                        usleep(100000);
                    } // Prevent tight loop
                }

                $this->handleFiberTermination();
            } finally {
                $this->sessionFiber = null;
            }
        };

        $stream = new CallbackStream($callback, $this->logger);
        $response = $this->responseFactory->createResponse(200)
            ->withHeader('Content-Type', 'text/event-stream')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Connection', 'keep-alive')
            ->withHeader('X-Accel-Buffering', 'no')
            ->withBody($stream);

        if ($this->getSessionId()) {
            $response = $response->withHeader('Mcp-Session-Id', $this->getSessionId()->toRfc4122());
        }

        return $this->withCorsHeaders($response);
    }

    protected function handleFiberTermination(): void
    {
        $finalResult = $this->sessionFiber->getReturn();

        if ($finalResult !== null) {
            try {
                $encoded = json_encode($finalResult, JSON_THROW_ON_ERROR);
                echo "event: message\n";
                echo "data: {$encoded}\n\n";
                @ob_flush();
                flush();
            } catch (JsonException $e) {
                $this->logger->error('SSE: Failed to encode final Fiber result.', ['exception' => $e]);
            }
        }

        $this->sessionFiber = null;
    }

    protected function flushOutgoingMessages(?Uuid $sessionId): void
    {
        $messages = $this->getOutgoingMessages($sessionId);

        foreach ($messages as $message) {
            echo "event: message\n";
            echo "data: {$message['message']}\n\n";
            @ob_flush();
            flush();
        }
    }

    protected function createErrorResponse(Error $jsonRpcError, int $statusCode): ResponseInterface
    {
        $payload = json_encode($jsonRpcError, JSON_THROW_ON_ERROR);
        $response = $this->responseFactory->createResponse($statusCode)
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->streamFactory->createStream($payload));

        return $this->withCorsHeaders($response);
    }

    protected function withCorsHeaders(ResponseInterface $response): ResponseInterface
    {
        foreach ($this->corsHeaders as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    protected function getRequest(): ?ServerRequestPlusInterface
    {
        try {
            return RequestContext::get();
        } catch (Throwable $e) {
            $this->logger->warning('Failed to get request from context.', ['exception' => $e]);
            return null;
        }
    }

    protected function getSessionId(): ?Uuid
    {
        return Context::getOrSet('mcp.session.id', function () {
            $request = $this->getRequest();
            $sessionId = $request?->getHeaderLine('Mcp-Session-Id');
            if ($sessionId) {
                return Uuid::fromString($sessionId);
            }
            return null;
        });
    }
}
