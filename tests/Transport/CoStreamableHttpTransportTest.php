<?php

declare(strict_types=1);
/**
 * This file is part of friendsofhyperf/mcp.
 *
 * @link     https://github.com/friendsofhyperf/mcp
 * @document https://github.com/friendsofhyperf/mcp/blob/main/README.md
 * @contact  Deeka Wong <huangdijia@gmail.com>
 */

namespace FriendsOfHyperf\Tests\McpServer\Transport;

use FriendsOfHyperf\McpServer\Transport\CoStreamableHttpTransport;
use Mcp\Schema\JsonRpc\Error;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;

/**
 * @covers \FriendsOfHyperf\McpServer\Transport\CoStreamableHttpTransport
 */
class CoStreamableHttpTransportTest extends TestCase
{
    private ResponseFactoryInterface $responseFactory;

    private StreamFactoryInterface $streamFactory;

    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $this->streamFactory = $this->createMock(StreamFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testConstructWithCustomFactories(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );
        $this->assertInstanceOf(CoStreamableHttpTransport::class, $transport);
    }

    public function testConstructWithCustomCorsHeaders(): void
    {
        $corsHeaders = [
            'Access-Control-Allow-Origin' => 'https://example.com',
            'Access-Control-Allow-Methods' => 'GET, POST',
        ];

        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            $corsHeaders,
            $this->logger
        );
        $this->assertInstanceOf(CoStreamableHttpTransport::class, $transport);
    }

    public function testInitializeDoesNothing(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        // initialize() should not throw any exception
        $transport->initialize();
        $this->assertTrue(true);
    }

    public function testSendStoresImmediateResponse(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $data = '{"jsonrpc":"2.0","result":{},"id":1}';
        $context = ['status_code' => 200];

        $transport->send($data, $context);

        // Use reflection to verify internal state
        $reflection = new ReflectionClass($transport);

        $immediateResponseProperty = $reflection->getProperty('immediateResponse');
        $immediateResponseProperty->setAccessible(true);
        $this->assertEquals($data, $immediateResponseProperty->getValue($transport));

        $immediateStatusCodeProperty = $reflection->getProperty('immediateStatusCode');
        $immediateStatusCodeProperty->setAccessible(true);
        $this->assertEquals(200, $immediateStatusCodeProperty->getValue($transport));
    }

    public function testSendWithoutStatusCodeUsesDefault(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $data = '{"jsonrpc":"2.0","result":{},"id":1}';
        $context = [];

        $transport->send($data, $context);

        $reflection = new ReflectionClass($transport);

        $immediateResponseProperty = $reflection->getProperty('immediateResponse');
        $immediateResponseProperty->setAccessible(true);
        $this->assertEquals($data, $immediateResponseProperty->getValue($transport));

        // When no status_code is provided, immediateStatusCode defaults to 200
        $immediateStatusCodeProperty = $reflection->getProperty('immediateStatusCode');
        $immediateStatusCodeProperty->setAccessible(true);
        $this->assertEquals(200, $immediateStatusCodeProperty->getValue($transport));
    }

    public function testWithCorsHeaders(): void
    {
        $corsHeaders = [
            'Access-Control-Allow-Origin' => 'https://example.com',
        ];

        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            $corsHeaders,
            $this->logger
        );

        $reflection = new ReflectionClass($transport);
        $method = $reflection->getMethod('withCorsHeaders');
        $method->setAccessible(true);

        $mockResponse = $this->createMock(ResponseInterface::class);

        // The mockResponse should receive withHeader calls for each CORS header
        // Default CORS headers + custom one
        $mockResponse->method('withHeader')->willReturnSelf();

        $result = $method->invoke($transport, $mockResponse);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testHandleOptionsRequest(): void
    {
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('withHeader')->willReturnSelf();

        $this->responseFactory
            ->method('createResponse')
            ->with(204)
            ->willReturn($mockResponse);

        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $reflection = new ReflectionClass($transport);
        $method = $reflection->getMethod('handleOptionsRequest');
        $method->setAccessible(true);

        $result = $method->invoke($transport);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testCreateErrorResponse(): void
    {
        $mockStream = $this->createMock(StreamInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('withHeader')->willReturnSelf();
        $mockResponse->method('withBody')->willReturnSelf();

        $this->responseFactory
            ->method('createResponse')
            ->willReturn($mockResponse);

        $this->streamFactory
            ->method('createStream')
            ->willReturn($mockStream);

        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $reflection = new ReflectionClass($transport);
        $method = $reflection->getMethod('createErrorResponse');
        $method->setAccessible(true);

        $jsonRpcError = Error::forInvalidRequest('Test error message');
        $result = $method->invoke($transport, $jsonRpcError, 400);

        $this->assertInstanceOf(ResponseInterface::class, $result);
    }

    public function testDefaultCorsHeadersAreSet(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $reflection = new ReflectionClass($transport);
        $corsHeadersProperty = $reflection->getProperty('corsHeaders');
        $corsHeadersProperty->setAccessible(true);

        $corsHeaders = $corsHeadersProperty->getValue($transport);

        $this->assertArrayHasKey('Access-Control-Allow-Origin', $corsHeaders);
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $corsHeaders);
        $this->assertArrayHasKey('Access-Control-Allow-Headers', $corsHeaders);

        $this->assertEquals('*', $corsHeaders['Access-Control-Allow-Origin']);
        $this->assertEquals('GET, POST, DELETE, OPTIONS', $corsHeaders['Access-Control-Allow-Methods']);
    }

    public function testCustomCorsHeadersOverrideDefaults(): void
    {
        $customCorsHeaders = [
            'Access-Control-Allow-Origin' => 'https://mysite.com',
        ];

        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            $customCorsHeaders,
            $this->logger
        );

        $reflection = new ReflectionClass($transport);
        $corsHeadersProperty = $reflection->getProperty('corsHeaders');
        $corsHeadersProperty->setAccessible(true);

        $corsHeaders = $corsHeadersProperty->getValue($transport);

        // Custom header should override default
        $this->assertEquals('https://mysite.com', $corsHeaders['Access-Control-Allow-Origin']);
        // Other default headers should still be present
        $this->assertArrayHasKey('Access-Control-Allow-Methods', $corsHeaders);
    }

    public function testSendWithCustomStatusCode(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $data = '{"jsonrpc":"2.0","error":{"code":-32600,"message":"Invalid Request"},"id":null}';
        $context = ['status_code' => 400];

        $transport->send($data, $context);

        $reflection = new ReflectionClass($transport);
        $immediateStatusCodeProperty = $reflection->getProperty('immediateStatusCode');
        $immediateStatusCodeProperty->setAccessible(true);

        $this->assertEquals(400, $immediateStatusCodeProperty->getValue($transport));
    }

    public function testSendMultipleTimes(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $data1 = '{"jsonrpc":"2.0","result":{},"id":1}';
        $data2 = '{"jsonrpc":"2.0","result":{},"id":2}';

        $transport->send($data1, ['status_code' => 200]);
        $transport->send($data2, ['status_code' => 201]);

        $reflection = new ReflectionClass($transport);

        // Last send should override previous values
        $immediateResponseProperty = $reflection->getProperty('immediateResponse');
        $immediateResponseProperty->setAccessible(true);
        $this->assertEquals($data2, $immediateResponseProperty->getValue($transport));

        $immediateStatusCodeProperty = $reflection->getProperty('immediateStatusCode');
        $immediateStatusCodeProperty->setAccessible(true);
        $this->assertEquals(201, $immediateStatusCodeProperty->getValue($transport));
    }

    public function testConstructWithLogger(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            $this->logger
        );

        $this->assertInstanceOf(CoStreamableHttpTransport::class, $transport);
    }

    public function testConstructWithNullLogger(): void
    {
        $transport = new CoStreamableHttpTransport(
            $this->responseFactory,
            $this->streamFactory,
            [],
            null
        );

        $this->assertInstanceOf(CoStreamableHttpTransport::class, $transport);
    }
}
