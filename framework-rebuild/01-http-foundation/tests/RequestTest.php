<?php

declare(strict_types=1);

namespace FrameworkRebuild\HttpFoundation\Tests;

use FrameworkRebuild\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the Request class.
 *
 * These tests demonstrate how to create and test Request objects
 * without relying on PHP superglobals.
 */
class RequestTest extends TestCase
{
    public function testConstructorInitializesParameterBags(): void
    {
        $query = ['page' => '1', 'sort' => 'desc'];
        $request = ['name' => 'John', 'email' => 'john@example.com'];
        $attributes = ['_route' => 'user_profile'];
        $cookies = ['session_id' => 'abc123'];
        $files = ['upload' => ['name' => 'test.txt']];
        $server = ['REQUEST_METHOD' => 'POST'];

        $req = new Request($query, $request, $attributes, $cookies, $files, $server);

        $this->assertSame($query, $req->query->all());
        $this->assertSame($request, $req->request->all());
        $this->assertSame($attributes, $req->attributes->all());
        $this->assertSame($cookies, $req->cookies->all());
        $this->assertSame($files, $req->files->all());
        $this->assertSame($server, $req->server->all());
    }

    public function testGetMethod(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST']);
        $this->assertSame('POST', $request->getMethod());

        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'get']);
        $this->assertSame('GET', $request->getMethod());
    }

    public function testGetMethodWithOverride(): void
    {
        // Method override via _method parameter
        $request = new Request(
            [],
            ['_method' => 'PUT'],
            [],
            [],
            [],
            ['REQUEST_METHOD' => 'POST']
        );
        $this->assertSame('PUT', $request->getMethod());

        // Method override via header
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'DELETE',
            ]
        );
        $this->assertSame('DELETE', $request->getMethod());

        // POST parameter takes precedence over header
        $request = new Request(
            [],
            ['_method' => 'PATCH'],
            [],
            [],
            [],
            [
                'REQUEST_METHOD' => 'POST',
                'HTTP_X_HTTP_METHOD_OVERRIDE' => 'DELETE',
            ]
        );
        $this->assertSame('PATCH', $request->getMethod());
    }

    public function testIsMethod(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_METHOD' => 'POST']);

        $this->assertTrue($request->isMethod('POST'));
        $this->assertTrue($request->isMethod('post'));
        $this->assertFalse($request->isMethod('GET'));
    }

    public function testGetPathInfo(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/users/123']);
        $this->assertSame('/users/123', $request->getPathInfo());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/users/123?page=1']);
        $this->assertSame('/users/123', $request->getPathInfo());

        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/']);
        $this->assertSame('/', $request->getPathInfo());

        // Test URL encoding
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/users/john%20doe']);
        $this->assertSame('/users/john doe', $request->getPathInfo());
    }

    public function testGetRequestUri(): void
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/users/123?page=1']);
        $this->assertSame('/users/123?page=1', $request->getRequestUri());
    }

    public function testGetScheme(): void
    {
        $request = new Request([], [], [], [], [], ['HTTPS' => 'on']);
        $this->assertSame('https', $request->getScheme());

        $request = new Request([], [], [], [], [], ['HTTPS' => 'off']);
        $this->assertSame('http', $request->getScheme());

        $request = new Request([], [], [], [], [], []);
        $this->assertSame('http', $request->getScheme());
    }

    public function testIsSecure(): void
    {
        $request = new Request([], [], [], [], [], ['HTTPS' => 'on']);
        $this->assertTrue($request->isSecure());

        $request = new Request([], [], [], [], [], ['HTTPS' => 'off']);
        $this->assertFalse($request->isSecure());

        $request = new Request([], [], [], [], [], []);
        $this->assertFalse($request->isSecure());
    }

    public function testGetHost(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'example.com']);
        $this->assertSame('example.com', $request->getHost());

        $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'example.com:8080']);
        $this->assertSame('example.com', $request->getHost());

        $request = new Request([], [], [], [], [], ['SERVER_NAME' => 'example.com']);
        $this->assertSame('example.com', $request->getHost());
    }

    public function testGetPort(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'example.com:8080']);
        $this->assertSame(8080, $request->getPort());

        $request = new Request([], [], [], [], [], ['SERVER_PORT' => '443']);
        $this->assertSame(443, $request->getPort());

        $request = new Request([], [], [], [], [], []);
        $this->assertSame(80, $request->getPort());
    }

    public function testGetUri(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTPS' => 'on',
                'HTTP_HOST' => 'example.com',
                'REQUEST_URI' => '/users/123?page=1',
            ]
        );
        $this->assertSame('https://example.com/users/123?page=1', $request->getUri());

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_HOST' => 'example.com:8080',
                'REQUEST_URI' => '/test',
            ]
        );
        $this->assertSame('http://example.com:8080/test', $request->getUri());
    }

    public function testGetClientIp(): void
    {
        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $this->assertSame('192.168.1.1', $request->getClientIp());

        // Test X-Forwarded-For with single IP
        $request = new Request([], [], [], [], [], ['HTTP_X_FORWARDED_FOR' => '203.0.113.1']);
        $this->assertSame('203.0.113.1', $request->getClientIp());

        // Test X-Forwarded-For with multiple IPs
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_X_FORWARDED_FOR' => '203.0.113.1, 198.51.100.1, 192.0.2.1']
        );
        $this->assertSame('203.0.113.1', $request->getClientIp());

        // Test invalid IP is skipped
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            [
                'HTTP_X_FORWARDED_FOR' => 'invalid',
                'REMOTE_ADDR' => '192.168.1.1',
            ]
        );
        $this->assertSame('192.168.1.1', $request->getClientIp());
    }

    public function testGetUserAgent(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $request = new Request([], [], [], [], [], ['HTTP_USER_AGENT' => $userAgent]);

        $this->assertSame($userAgent, $request->getUserAgent());
    }

    public function testIsXmlHttpRequest(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']);
        $this->assertTrue($request->isXmlHttpRequest());

        $request = new Request([], [], [], [], [], []);
        $this->assertFalse($request->isXmlHttpRequest());
    }

    public function testGetContentType(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->assertSame('application/json', $request->getContentType());

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json; charset=UTF-8']
        );
        $this->assertSame('application/json', $request->getContentType());

        $request = new Request([], [], [], [], [], []);
        $this->assertNull($request->getContentType());
    }

    public function testIsJson(): void
    {
        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'application/json']);
        $this->assertTrue($request->isJson());

        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/vnd.api+json']
        );
        $this->assertTrue($request->isJson());

        $request = new Request([], [], [], [], [], ['CONTENT_TYPE' => 'text/html']);
        $this->assertFalse($request->isJson());
    }

    public function testGet(): void
    {
        $request = new Request(
            ['page' => '1'],
            ['name' => 'John'],
            ['_route' => 'home']
        );

        // Attributes have highest priority
        $this->assertSame('home', $request->get('_route'));

        // Query parameters next
        $this->assertSame('1', $request->get('page'));

        // Request parameters last
        $this->assertSame('John', $request->get('name'));

        // Default value
        $this->assertSame('default', $request->get('nonexistent', 'default'));
    }

    public function testHas(): void
    {
        $request = new Request(['page' => '1'], ['name' => 'John']);

        $this->assertTrue($request->has('page'));
        $this->assertTrue($request->has('name'));
        $this->assertFalse($request->has('nonexistent'));
    }

    public function testAll(): void
    {
        $request = new Request(
            ['page' => '1'],
            ['name' => 'John']
        );

        $all = $request->all();
        $this->assertSame('1', $all['page']);
        $this->assertSame('John', $all['name']);
    }

    public function testGetReferer(): void
    {
        $referer = 'https://example.com/previous-page';
        $request = new Request([], [], [], [], [], ['HTTP_REFERER' => $referer]);

        $this->assertSame($referer, $request->getReferer());

        $request = new Request([], [], [], [], [], []);
        $this->assertNull($request->getReferer());
    }

    public function testExpectsJson(): void
    {
        $request = new Request(
            [],
            [],
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json, text/html']
        );
        $this->assertTrue($request->expectsJson());

        $request = new Request([], [], [], [], [], ['HTTP_ACCEPT' => 'text/html']);
        $this->assertFalse($request->expectsJson());
    }

    public function testIsPrefetch(): void
    {
        $request = new Request([], [], [], [], [], ['HTTP_X_MOZ' => 'prefetch']);
        $this->assertTrue($request->isPrefetch());

        $request = new Request([], [], [], [], [], ['HTTP_PURPOSE' => 'prefetch']);
        $this->assertTrue($request->isPrefetch());

        $request = new Request([], [], [], [], [], []);
        $this->assertFalse($request->isPrefetch());
    }

    public function testCreateFromGlobalsReadsSuperglobals(): void
    {
        // Save original superglobals
        $originalGet = $_GET;
        $originalPost = $_POST;
        $originalCookie = $_COOKIE;
        $originalFiles = $_FILES;
        $originalServer = $_SERVER;

        try {
            // Set up test superglobals
            $_GET = ['test' => 'value'];
            $_POST = ['name' => 'Test'];
            $_COOKIE = ['session' => 'xyz'];
            $_FILES = [];
            $_SERVER = ['REQUEST_METHOD' => 'POST'];

            $request = Request::createFromGlobals();

            $this->assertSame('value', $request->query->get('test'));
            $this->assertSame('Test', $request->request->get('name'));
            $this->assertSame('xyz', $request->cookies->get('session'));
            $this->assertSame('POST', $request->getMethod());
        } finally {
            // Restore original superglobals
            $_GET = $originalGet;
            $_POST = $originalPost;
            $_COOKIE = $originalCookie;
            $_FILES = $originalFiles;
            $_SERVER = $originalServer;
        }
    }

    public function testParameterBagTypeSafety(): void
    {
        $request = new Request(
            [
                'page' => '5',
                'active' => '1',
                'name' => 'test',
            ]
        );

        $this->assertSame(5, $request->query->getInt('page'));
        $this->assertSame(0, $request->query->getInt('nonexistent'));
        $this->assertSame(10, $request->query->getInt('nonexistent', 10));

        $this->assertTrue($request->query->getBoolean('active'));
        $this->assertFalse($request->query->getBoolean('nonexistent'));

        $this->assertSame('test', $request->query->getString('name'));
        $this->assertSame('', $request->query->getString('nonexistent'));
        $this->assertSame('default', $request->query->getString('nonexistent', 'default'));
    }
}
