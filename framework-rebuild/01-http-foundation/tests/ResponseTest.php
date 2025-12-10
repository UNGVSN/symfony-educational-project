<?php

declare(strict_types=1);

namespace FrameworkRebuild\HttpFoundation\Tests;

use FrameworkRebuild\HttpFoundation\Response;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Response class.
 *
 * These tests verify that Response objects are built correctly
 * and can be inspected before being sent.
 */
class ResponseTest extends TestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $response = new Response();

        $this->assertSame('', $response->getContent());
        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('text/html; charset=UTF-8', $response->getHeader('Content-Type'));
    }

    public function testConstructorAcceptsParameters(): void
    {
        $response = new Response(
            '<h1>Hello</h1>',
            Response::HTTP_CREATED,
            ['X-Custom-Header' => 'value']
        );

        $this->assertSame('<h1>Hello</h1>', $response->getContent());
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('value', $response->getHeader('X-Custom-Header'));
    }

    public function testSetContent(): void
    {
        $response = new Response();
        $result = $response->setContent('Test content');

        $this->assertSame($response, $result); // Fluent interface
        $this->assertSame('Test content', $response->getContent());
    }

    public function testSetStatusCode(): void
    {
        $response = new Response();
        $result = $response->setStatusCode(Response::HTTP_NOT_FOUND);

        $this->assertSame($response, $result); // Fluent interface
        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testSetStatusCodeValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = new Response();
        $response->setStatusCode(999); // Invalid status code
    }

    public function testSetHeader(): void
    {
        $response = new Response();
        $result = $response->setHeader('X-Test', 'value');

        $this->assertSame($response, $result); // Fluent interface
        $this->assertSame('value', $response->getHeader('X-Test'));
    }

    public function testGetHeader(): void
    {
        $response = new Response();
        $response->setHeader('X-Test', 'value');

        $this->assertSame('value', $response->getHeader('X-Test'));
        $this->assertNull($response->getHeader('NonExistent'));
        $this->assertSame('default', $response->getHeader('NonExistent', 'default'));
    }

    public function testHasHeader(): void
    {
        $response = new Response();
        $response->setHeader('X-Test', 'value');

        $this->assertTrue($response->hasHeader('X-Test'));
        $this->assertFalse($response->hasHeader('NonExistent'));
    }

    public function testRemoveHeader(): void
    {
        $response = new Response();
        $response->setHeader('X-Test', 'value');

        $this->assertTrue($response->hasHeader('X-Test'));

        $result = $response->removeHeader('X-Test');

        $this->assertSame($response, $result); // Fluent interface
        $this->assertFalse($response->hasHeader('X-Test'));
    }

    public function testGetHeaders(): void
    {
        $response = new Response('', 200, [
            'X-Custom-1' => 'value1',
            'X-Custom-2' => 'value2',
        ]);

        $headers = $response->getHeaders();

        $this->assertIsArray($headers);
        $this->assertArrayHasKey('X-Custom-1', $headers);
        $this->assertArrayHasKey('X-Custom-2', $headers);
        $this->assertSame('value1', $headers['X-Custom-1']);
        $this->assertSame('value2', $headers['X-Custom-2']);
    }

    public function testCreateJson(): void
    {
        $data = ['status' => 'success', 'data' => ['id' => 1, 'name' => 'Test']];
        $response = Response::createJson($data);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
        $this->assertSame(json_encode($data), $response->getContent());
    }

    public function testCreateJsonWithCustomStatusCode(): void
    {
        $data = ['error' => 'Not found'];
        $response = Response::createJson($data, Response::HTTP_NOT_FOUND);

        $this->assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        $this->assertSame(json_encode($data), $response->getContent());
    }

    public function testCreateJsonWithInvalidData(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode JSON');

        // Create a resource that can't be JSON encoded
        $resource = fopen('php://memory', 'r');
        Response::createJson(['resource' => $resource]);
        fclose($resource);
    }

    public function testCreateRedirect(): void
    {
        $response = Response::createRedirect('https://example.com/new-page');

        $this->assertSame(Response::HTTP_FOUND, $response->getStatusCode());
        $this->assertSame('https://example.com/new-page', $response->getHeader('Location'));
    }

    public function testCreateRedirectWithCustomStatusCode(): void
    {
        $response = Response::createRedirect(
            'https://example.com/permanent',
            Response::HTTP_MOVED_PERMANENTLY
        );

        $this->assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
    }

    public function testCreateNoContent(): void
    {
        $response = Response::createNoContent();

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function testSetCookie(): void
    {
        $response = new Response();
        $result = $response->setCookie(
            'test_cookie',
            'value123',
            time() + 3600,
            '/path',
            'example.com',
            true,
            true,
            'Strict'
        );

        $this->assertSame($response, $result); // Fluent interface
        // Note: We can't easily test cookie output without actually sending the response
    }

    public function testDeleteCookie(): void
    {
        $response = new Response();
        $result = $response->deleteCookie('test_cookie');

        $this->assertSame($response, $result); // Fluent interface
    }

    public function testSetCacheHeaders(): void
    {
        $response = new Response();
        $result = $response->setCacheHeaders(3600);

        $this->assertSame($response, $result); // Fluent interface
        $this->assertStringContainsString('max-age=3600', $response->getHeader('Cache-Control'));
        $this->assertNotNull($response->getHeader('Expires'));
    }

    public function testSetNoCacheHeaders(): void
    {
        $response = new Response();
        $response->setCacheHeaders(0);

        $this->assertStringContainsString('no-cache', $response->getHeader('Cache-Control'));
        $this->assertSame('no-cache', $response->getHeader('Pragma'));
        $this->assertSame('0', $response->getHeader('Expires'));
    }

    public function testIsSuccessful(): void
    {
        $response = new Response('', Response::HTTP_OK);
        $this->assertTrue($response->isSuccessful());

        $response = new Response('', Response::HTTP_CREATED);
        $this->assertTrue($response->isSuccessful());

        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->assertFalse($response->isSuccessful());
    }

    public function testIsRedirect(): void
    {
        $response = new Response('', Response::HTTP_FOUND);
        $this->assertTrue($response->isRedirect());

        $response = new Response('', Response::HTTP_MOVED_PERMANENTLY);
        $this->assertTrue($response->isRedirect());

        $response = new Response('', Response::HTTP_OK);
        $this->assertFalse($response->isRedirect());
    }

    public function testIsClientError(): void
    {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->assertTrue($response->isClientError());

        $response = new Response('', Response::HTTP_BAD_REQUEST);
        $this->assertTrue($response->isClientError());

        $response = new Response('', Response::HTTP_OK);
        $this->assertFalse($response->isClientError());
    }

    public function testIsServerError(): void
    {
        $response = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertTrue($response->isServerError());

        $response = new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        $this->assertTrue($response->isServerError());

        $response = new Response('', Response::HTTP_OK);
        $this->assertFalse($response->isServerError());
    }

    public function testIsOk(): void
    {
        $response = new Response('', Response::HTTP_OK);
        $this->assertTrue($response->isOk());

        $response = new Response('', Response::HTTP_CREATED);
        $this->assertFalse($response->isOk());
    }

    public function testIsForbidden(): void
    {
        $response = new Response('', Response::HTTP_FORBIDDEN);
        $this->assertTrue($response->isForbidden());

        $response = new Response('', Response::HTTP_UNAUTHORIZED);
        $this->assertFalse($response->isForbidden());
    }

    public function testIsNotFound(): void
    {
        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->assertTrue($response->isNotFound());

        $response = new Response('', Response::HTTP_OK);
        $this->assertFalse($response->isNotFound());
    }

    public function testIsEmpty(): void
    {
        $response = new Response('', Response::HTTP_NO_CONTENT);
        $this->assertTrue($response->isEmpty());

        $response = new Response('', Response::HTTP_NOT_MODIFIED);
        $this->assertTrue($response->isEmpty());

        $response = new Response('', Response::HTTP_OK);
        $this->assertFalse($response->isEmpty());
    }

    public function testGetStatusText(): void
    {
        $response = new Response('', Response::HTTP_OK);
        $this->assertSame('OK', $response->getStatusText());

        $response = new Response('', Response::HTTP_NOT_FOUND);
        $this->assertSame('Not Found', $response->getStatusText());

        $response = new Response('', Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertSame('Internal Server Error', $response->getStatusText());
    }

    public function testToString(): void
    {
        $response = new Response('<h1>Hello</h1>', Response::HTTP_OK, [
            'X-Custom' => 'value',
        ]);

        $string = (string) $response;

        $this->assertStringContainsString('HTTP/1.1 200 OK', $string);
        $this->assertStringContainsString('X-Custom: value', $string);
        $this->assertStringContainsString('<h1>Hello</h1>', $string);
    }

    public function testFluentInterface(): void
    {
        $response = new Response();

        $result = $response
            ->setContent('<h1>Test</h1>')
            ->setStatusCode(Response::HTTP_CREATED)
            ->setHeader('X-Custom', 'value')
            ->setCacheHeaders(3600);

        // All methods should return the same instance
        $this->assertSame($response, $result);

        // Verify the configuration
        $this->assertSame('<h1>Test</h1>', $response->getContent());
        $this->assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        $this->assertSame('value', $response->getHeader('X-Custom'));
    }

    public function testStatusCodeConstants(): void
    {
        // Just verify a few important constants exist
        $this->assertSame(200, Response::HTTP_OK);
        $this->assertSame(201, Response::HTTP_CREATED);
        $this->assertSame(204, Response::HTTP_NO_CONTENT);
        $this->assertSame(301, Response::HTTP_MOVED_PERMANENTLY);
        $this->assertSame(302, Response::HTTP_FOUND);
        $this->assertSame(400, Response::HTTP_BAD_REQUEST);
        $this->assertSame(401, Response::HTTP_UNAUTHORIZED);
        $this->assertSame(403, Response::HTTP_FORBIDDEN);
        $this->assertSame(404, Response::HTTP_NOT_FOUND);
        $this->assertSame(500, Response::HTTP_INTERNAL_SERVER_ERROR);
        $this->assertSame(503, Response::HTTP_SERVICE_UNAVAILABLE);
    }

    /**
     * Test that send() outputs headers and content.
     * Note: This is difficult to test without output buffering.
     */
    public function testSendOutputsContent(): void
    {
        $response = new Response('Test content');

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame('Test content', $output);
    }

    public function testSendWithJsonResponse(): void
    {
        $data = ['status' => 'ok', 'message' => 'Success'];
        $response = Response::createJson($data);

        ob_start();
        $response->send();
        $output = ob_get_clean();

        $this->assertSame(json_encode($data), $output);
    }
}
