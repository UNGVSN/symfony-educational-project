# HTTP Deep Dive - Advanced Topics

Advanced HTTP concepts and modern protocols for building high-performance Symfony applications.

---

## Table of Contents

1. [HTTP/2 and HTTP/3](#1-http2-and-http3)
2. [Streaming Responses](#2-streaming-responses)
3. [Server-Sent Events (SSE)](#3-server-sent-events-sse)
4. [WebSockets Basics](#4-websockets-basics)
5. [HttpClient Advanced Usage](#5-httpclient-advanced-usage)
6. [HTTP Caching Strategies](#6-http-caching-strategies)
7. [Performance Optimization](#7-performance-optimization)

---

## 1. HTTP/2 and HTTP/3

### HTTP/1.1 Limitations

```
HTTP/1.1 Issues:
- Head-of-line blocking
- Multiple TCP connections needed
- Plain text headers (overhead)
- No request prioritization
- Limited concurrency
```

### HTTP/2 Improvements

HTTP/2 was standardized in 2015 and brings significant improvements:

#### Key Features

1. **Binary Protocol**: More efficient parsing
2. **Multiplexing**: Multiple requests over single connection
3. **Header Compression**: HPACK algorithm reduces overhead
4. **Server Push**: Server can send resources proactively
5. **Stream Prioritization**: Request priority levels

```nginx
# Nginx Configuration for HTTP/2
server {
    listen 443 ssl http2;
    server_name example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # HTTP/2 Server Push
    location = /index.html {
        http2_push /css/app.css;
        http2_push /js/app.js;
    }

    location / {
        try_files $uri /index.php$is_args$args;
    }
}
```

#### Symfony and HTTP/2

Symfony automatically works with HTTP/2 when configured on the web server:

```php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Http2Controller extends AbstractController
{
    #[Route('/dashboard')]
    public function dashboard(): Response
    {
        $response = $this->render('dashboard.html.twig');

        // Add Link header for HTTP/2 Server Push
        $response->headers->set('Link', '<style.css>; rel=preload; as=style');
        $response->headers->set('Link', '<script.js>; rel=preload; as=script', false);

        return $response;
    }

    #[Route('/api/stream')]
    public function stream(): Response
    {
        // HTTP/2 multiplexing benefits streaming responses
        return new StreamedResponse(function() {
            for ($i = 0; $i < 10; $i++) {
                echo json_encode(['chunk' => $i]) . "\n";
                flush();
                usleep(100000); // 100ms delay
            }
        });
    }
}
```

### HTTP/3 (QUIC)

HTTP/3 uses QUIC protocol (based on UDP) instead of TCP:

#### HTTP/3 Benefits

```
Advantages:
✓ Faster connection establishment (0-RTT)
✓ No head-of-line blocking at transport layer
✓ Better performance on lossy networks
✓ Connection migration (survives IP changes)
✓ Built-in encryption (TLS 1.3)
```

```nginx
# Nginx with HTTP/3 support (1.25.0+)
server {
    listen 443 ssl http3 reuseport;
    listen 443 ssl http2;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Advertise HTTP/3 support
    add_header Alt-Svc 'h3=":443"; ma=86400';

    location / {
        # Your Symfony app
        try_files $uri /index.php$is_args$args;
    }
}
```

### Protocol Comparison

| Feature | HTTP/1.1 | HTTP/2 | HTTP/3 |
|---------|----------|---------|---------|
| Transport | TCP | TCP | QUIC (UDP) |
| Multiplexing | No | Yes | Yes |
| Header Compression | No | HPACK | QPACK |
| Server Push | No | Yes | Yes |
| Connection Setup | 3 RTT | 3 RTT | 0-1 RTT |
| Head-of-line | Yes | Partial | No |

---

## 2. Streaming Responses

Streaming allows sending data to the client before the entire response is ready.

### StreamedResponse

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamingController extends AbstractController
{
    #[Route('/export/csv')]
    public function exportCsv(): StreamedResponse
    {
        return new StreamedResponse(function() {
            $handle = fopen('php://output', 'w');

            // Write CSV header
            fputcsv($handle, ['ID', 'Name', 'Email', 'Created']);

            // Stream large dataset in batches
            $offset = 0;
            $batchSize = 1000;

            while (true) {
                $users = $this->userRepository->findBatch($offset, $batchSize);

                if (empty($users)) {
                    break;
                }

                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->getId(),
                        $user->getName(),
                        $user->getEmail(),
                        $user->getCreatedAt()->format('Y-m-d H:i:s'),
                    ]);
                }

                // Force send to client
                flush();

                $offset += $batchSize;

                // Prevent timeout
                set_time_limit(30);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users.csv"',
            'X-Accel-Buffering' => 'no', // Disable nginx buffering
        ]);
    }

    #[Route('/logs/tail')]
    public function tailLogs(): StreamedResponse
    {
        return new StreamedResponse(function() {
            $logFile = $this->getParameter('kernel.logs_dir') . '/dev.log';
            $handle = fopen($logFile, 'r');

            // Seek to end
            fseek($handle, 0, SEEK_END);

            while (!connection_aborted()) {
                $line = fgets($handle);

                if ($line !== false) {
                    echo $line;
                    flush();
                } else {
                    // No new data, wait a bit
                    usleep(100000); // 100ms
                    clearstatcache();
                }

                // Check if file was rotated
                if (!file_exists($logFile)) {
                    break;
                }
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/plain',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    #[Route('/api/bulk-process')]
    public function bulkProcess(Request $request): StreamedResponse
    {
        $items = $request->toArray()['items'];

        return new StreamedResponse(function() use ($items) {
            echo "data: " . json_encode(['status' => 'started', 'total' => count($items)]) . "\n\n";
            flush();

            foreach ($items as $index => $item) {
                try {
                    $result = $this->processItem($item);

                    echo "data: " . json_encode([
                        'index' => $index,
                        'status' => 'success',
                        'result' => $result,
                    ]) . "\n\n";

                } catch (\Exception $e) {
                    echo "data: " . json_encode([
                        'index' => $index,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ]) . "\n\n";
                }

                flush();
            }

            echo "data: " . json_encode(['status' => 'completed']) . "\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
```

### Large File Downloads

```php
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class DownloadController extends AbstractController
{
    #[Route('/download/{id}')]
    public function download(int $id): BinaryFileResponse
    {
        $file = $this->fileRepository->find($id);

        if (!$file) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($file->getPath());

        // Force download
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $file->getOriginalName()
        );

        // Enable range requests for resumable downloads
        $response->headers->set('Accept-Ranges', 'bytes');

        // Delete file after download
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route('/stream-video/{id}')]
    public function streamVideo(int $id, Request $request): Response
    {
        $video = $this->videoRepository->find($id);
        $path = $video->getPath();
        $size = filesize($path);

        // Handle range requests for video seeking
        $range = $request->headers->get('Range');

        if ($range) {
            // Parse Range header: bytes=start-end
            preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
            $start = (int) $matches[1];
            $end = $matches[2] ? (int) $matches[2] : $size - 1;
            $length = $end - $start + 1;

            return new StreamedResponse(
                function() use ($path, $start, $length) {
                    $handle = fopen($path, 'rb');
                    fseek($handle, $start);
                    echo fread($handle, $length);
                    fclose($handle);
                },
                Response::HTTP_PARTIAL_CONTENT,
                [
                    'Content-Type' => 'video/mp4',
                    'Content-Length' => $length,
                    'Content-Range' => "bytes {$start}-{$end}/{$size}",
                    'Accept-Ranges' => 'bytes',
                ]
            );
        }

        // Full file response
        return new BinaryFileResponse($path);
    }
}
```

---

## 3. Server-Sent Events (SSE)

SSE provides one-way real-time communication from server to client.

### SSE Basics

```
SSE Protocol:
- Content-Type: text/event-stream
- Data format: "data: message\n\n"
- Keep connection alive
- Auto-reconnect on disconnect
- Last-Event-ID for resuming
```

### Implementation

```php
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends AbstractController
{
    #[Route('/sse/notifications')]
    public function notifications(SessionInterface $session): StreamedResponse
    {
        $userId = $session->get('user_id');

        return new StreamedResponse(
            function() use ($userId) {
                // Send initial connection confirmation
                $this->sendSseMessage([
                    'type' => 'connected',
                    'timestamp' => time(),
                ]);

                $lastId = 0;

                while (!connection_aborted()) {
                    // Check for new notifications
                    $notifications = $this->notificationRepository
                        ->findNewNotifications($userId, $lastId);

                    foreach ($notifications as $notification) {
                        $this->sendSseMessage(
                            data: [
                                'id' => $notification->getId(),
                                'type' => $notification->getType(),
                                'message' => $notification->getMessage(),
                                'created_at' => $notification->getCreatedAt(),
                            ],
                            id: $notification->getId(),
                            event: 'notification'
                        );

                        $lastId = max($lastId, $notification->getId());
                    }

                    // Send heartbeat to keep connection alive
                    $this->sendSseMessage(
                        data: ['type' => 'heartbeat'],
                        event: 'heartbeat'
                    );

                    // Wait before checking again
                    sleep(2);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
                'Connection' => 'keep-alive',
            ]
        );
    }

    private function sendSseMessage(array $data, ?int $id = null, ?string $event = null): void
    {
        if ($id !== null) {
            echo "id: {$id}\n";
        }

        if ($event !== null) {
            echo "event: {$event}\n";
        }

        echo "data: " . json_encode($data) . "\n\n";
        flush();
    }

    #[Route('/sse/progress/{jobId}')]
    public function jobProgress(string $jobId): StreamedResponse
    {
        return new StreamedResponse(
            function() use ($jobId) {
                while (!connection_aborted()) {
                    $job = $this->jobRepository->find($jobId);

                    if (!$job) {
                        $this->sendSseMessage(['error' => 'Job not found']);
                        break;
                    }

                    $this->sendSseMessage([
                        'status' => $job->getStatus(),
                        'progress' => $job->getProgress(),
                        'message' => $job->getMessage(),
                    ]);

                    // If job is complete, close connection
                    if (in_array($job->getStatus(), ['completed', 'failed'])) {
                        $this->sendSseMessage([
                            'type' => 'close',
                            'message' => 'Job finished',
                        ]);
                        break;
                    }

                    sleep(1);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }

    #[Route('/sse/live-metrics')]
    public function liveMetrics(): StreamedResponse
    {
        return new StreamedResponse(
            function() {
                while (!connection_aborted()) {
                    $metrics = [
                        'cpu' => sys_getloadavg()[0],
                        'memory' => memory_get_usage(true),
                        'active_users' => $this->getActiveUserCount(),
                        'requests_per_second' => $this->getRequestRate(),
                        'timestamp' => microtime(true),
                    ];

                    $this->sendSseMessage($metrics, event: 'metrics');

                    sleep(5);
                }
            },
            200,
            [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'X-Accel-Buffering' => 'no',
            ]
        );
    }
}
```

### JavaScript Client

```javascript
// Connect to SSE endpoint
const eventSource = new EventSource('/sse/notifications');

// Listen for messages
eventSource.addEventListener('notification', (event) => {
    const data = JSON.parse(event.data);
    console.log('New notification:', data);
    showNotification(data);
});

eventSource.addEventListener('heartbeat', (event) => {
    console.log('Heartbeat received');
});

// Handle errors
eventSource.onerror = (error) => {
    console.error('SSE error:', error);
    if (eventSource.readyState === EventSource.CLOSED) {
        console.log('Connection closed');
    }
};

// Close connection
eventSource.close();
```

---

## 4. WebSockets Basics

While Symfony doesn't have native WebSocket support, you can integrate with libraries.

### WebSocket vs SSE

| Feature | WebSocket | SSE |
|---------|-----------|-----|
| Direction | Bi-directional | Server to Client |
| Protocol | ws:// wss:// | HTTP/HTTPS |
| Reconnect | Manual | Automatic |
| Data Format | Binary/Text | Text only |
| Browser Support | Excellent | Good |
| Complexity | Higher | Lower |

### Using Ratchet with Symfony

```bash
composer require cboden/ratchet
```

```php
// src/WebSocket/ChatServer.php
namespace App\WebSocket;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        echo "New connection: {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $data = json_decode($msg, true);

        foreach ($this->clients as $client) {
            if ($from !== $client) {
                // Send to all clients except sender
                $client->send(json_encode([
                    'type' => 'message',
                    'from' => $from->resourceId,
                    'message' => $data['message'],
                    'timestamp' => time(),
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        echo "Connection closed: {$conn->resourceId}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}
```

```php
// bin/websocket-server.php
#!/usr/bin/env php
<?php

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use App\WebSocket\ChatServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "WebSocket server started on port 8080\n";
$server->run();
```

### Mercure (Recommended for Symfony)

Mercure is a better fit for Symfony applications:

```bash
composer require symfony/mercure-bundle
```

```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            public_url: '%env(MERCURE_PUBLIC_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                algorithm: 'HS256'
```

```php
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationController extends AbstractController
{
    #[Route('/notify', methods: ['POST'])]
    public function notify(Request $request, HubInterface $hub): Response
    {
        $data = $request->toArray();

        $update = new Update(
            topics: ['https://example.com/notifications/' . $data['user_id']],
            data: json_encode([
                'message' => $data['message'],
                'timestamp' => time(),
            ])
        );

        $hub->publish($update);

        return $this->json(['status' => 'sent']);
    }
}
```

---

## 5. HttpClient Advanced Usage

### Async Requests

```php
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class AsyncHttpService
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {}

    public function fetchMultipleApis(): array
    {
        // Start all requests in parallel
        $responses = [
            'users' => $this->httpClient->request('GET', 'https://api1.example.com/users'),
            'posts' => $this->httpClient->request('GET', 'https://api2.example.com/posts'),
            'comments' => $this->httpClient->request('GET', 'https://api3.example.com/comments'),
        ];

        $results = [];

        // Process responses as they complete
        foreach ($this->httpClient->stream($responses) as $response => $chunk) {
            if ($chunk->isLast()) {
                $key = array_search($response, $responses, true);
                $results[$key] = $response->toArray();
            }
        }

        return $results;
    }

    public function fetchWithTimeout(array $urls, float $timeout = 5.0): array
    {
        $responses = [];
        foreach ($urls as $key => $url) {
            $responses[$key] = $this->httpClient->request('GET', $url, [
                'timeout' => $timeout,
            ]);
        }

        $results = [];

        try {
            foreach ($this->httpClient->stream($responses, $timeout) as $response => $chunk) {
                if ($chunk->isTimeout()) {
                    $key = array_search($response, $responses, true);
                    $results[$key] = ['error' => 'timeout'];
                    continue;
                }

                if ($chunk->isLast()) {
                    $key = array_search($response, $responses, true);
                    $results[$key] = $response->toArray();
                }
            }
        } catch (\Exception $e) {
            // Handle exception
        }

        return $results;
    }
}
```

### Retry and Exponential Backoff

```php
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;

// Configure in services.yaml
// services:
//     app.http_client.retryable:
//         class: Symfony\Component\HttpClient\RetryableHttpClient
//         arguments:
//             $client: '@http_client'
//             $strategy: '@app.retry_strategy'

class CustomRetryStrategy extends GenericRetryStrategy
{
    public function __construct()
    {
        parent::__construct(
            statusCodes: [423, 429, 500, 502, 503, 504],
            delayMs: 1000, // Initial delay
            multiplier: 2, // Exponential backoff
            maxDelayMs: 10000,
            jitter: 0.1
        );
    }

    public function shouldRetry(
        AsyncContext $context,
        ?string $responseContent,
        ?TransportExceptionInterface $exception
    ): ?bool {
        // Custom retry logic
        if ($exception instanceof TransportExceptionInterface) {
            return true; // Retry on network errors
        }

        $statusCode = $context->getStatusCode();

        // Don't retry client errors (except rate limit)
        if ($statusCode >= 400 && $statusCode < 500 && $statusCode !== 429) {
            return false;
        }

        return parent::shouldRetry($context, $responseContent, $exception);
    }
}
```

### Request Caching

```php
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;

class CachedApiService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $httpClient, string $cacheDir)
    {
        $store = new Store($cacheDir);
        $this->client = new CachingHttpClient($httpClient, $store);
    }

    public function getCachedData(string $url): array
    {
        // Response will be cached based on Cache-Control headers
        $response = $this->client->request('GET', $url);

        return $response->toArray();
    }
}
```

### Mock Client for Testing

```php
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class ApiServiceTest extends TestCase
{
    public function testApiCall(): void
    {
        $mockResponse = new MockResponse(
            json_encode(['id' => 1, 'name' => 'Test']),
            ['http_code' => 200]
        );

        $httpClient = new MockHttpClient($mockResponse);
        $service = new ApiService($httpClient);

        $result = $service->fetchUser(1);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Test', $result['name']);
    }

    public function testMultipleResponses(): void
    {
        $responses = [
            new MockResponse('{"page": 1}'),
            new MockResponse('{"page": 2}'),
            new MockResponse('{"page": 3}'),
        ];

        $httpClient = new MockHttpClient($responses);

        // Each request will use next response
        $page1 = $httpClient->request('GET', '/page/1')->toArray();
        $page2 = $httpClient->request('GET', '/page/2')->toArray();
        $page3 = $httpClient->request('GET', '/page/3')->toArray();

        $this->assertEquals(1, $page1['page']);
        $this->assertEquals(2, $page2['page']);
        $this->assertEquals(3, $page3['page']);
    }
}
```

---

## 6. HTTP Caching Strategies

### ETag Implementation

```php
class ETagController extends AbstractController
{
    #[Route('/api/posts/{id}')]
    public function show(Request $request, int $id): Response
    {
        $post = $this->postRepository->find($id);

        if (!$post) {
            return $this->json(['error' => 'Not found'], 404);
        }

        $response = $this->json($post);

        // Generate ETag from content
        $etag = md5(json_encode($post) . $post->getUpdatedAt()->getTimestamp());
        $response->setEtag($etag);

        // Check if client cache is valid
        if ($response->isNotModified($request)) {
            return $response; // Returns 304
        }

        // Set caching headers
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
```

### Last-Modified Implementation

```php
class LastModifiedController extends AbstractController
{
    #[Route('/api/articles')]
    public function list(Request $request): Response
    {
        // Get latest update time
        $lastModified = $this->articleRepository->getLastModifiedDate();

        $response = new Response();
        $response->setLastModified($lastModified);

        // Check if client cache is valid
        if ($response->isNotModified($request)) {
            return $response;
        }

        // Fetch and return data
        $articles = $this->articleRepository->findAll();
        $response->setContent(json_encode($articles));
        $response->headers->set('Content-Type', 'application/json');
        $response->setPublic();
        $response->setMaxAge(3600);

        return $response;
    }
}
```

### Vary Header

```php
#[Route('/api/content')]
public function content(Request $request): Response
{
    $format = $request->getPreferredFormat(['json', 'xml']);
    $language = $request->getPreferredLanguage(['en', 'fr', 'de']);

    $content = $this->getContent($language);

    $response = match($format) {
        'json' => $this->json($content),
        'xml' => new Response($this->toXml($content), 200, [
            'Content-Type' => 'application/xml',
        ]),
    };

    // Cache must store separate versions for different Accept/Accept-Language
    $response->setVary(['Accept', 'Accept-Language']);
    $response->setPublic();
    $response->setMaxAge(3600);

    return $response;
}
```

---

## 7. Performance Optimization

### Connection Pooling

```yaml
# config/packages/framework.yaml
framework:
    http_client:
        max_host_connections: 6  # Default
        scoped_clients:
            api_client:
                base_uri: 'https://api.example.com'
                max_host_connections: 10
```

### Response Compression

```php
class CompressionController extends AbstractController
{
    #[Route('/api/large-dataset')]
    public function largeDataset(Request $request): Response
    {
        $data = $this->getLargeDataset();
        $content = json_encode($data);

        $acceptEncoding = $request->headers->get('Accept-Encoding', '');

        if (str_contains($acceptEncoding, 'gzip')) {
            $compressed = gzencode($content, 6); // Compression level 1-9

            return new Response($compressed, 200, [
                'Content-Type' => 'application/json',
                'Content-Encoding' => 'gzip',
                'Content-Length' => strlen($compressed),
                'Vary' => 'Accept-Encoding',
            ]);
        }

        return $this->json($data);
    }
}
```

### Request/Response Profiling

```php
use Symfony\Component\Stopwatch\Stopwatch;

class ProfilingController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private Stopwatch $stopwatch
    ) {}

    #[Route('/api/profile')]
    public function profile(): Response
    {
        $this->stopwatch->start('external_api');

        $response = $this->httpClient->request('GET', 'https://api.example.com/data');
        $data = $response->toArray();

        $event = $this->stopwatch->stop('external_api');

        return $this->json([
            'duration' => $event->getDuration(),
            'memory' => $event->getMemory(),
            'data' => $data,
        ]);
    }
}
```

### HTTP/2 Server Push

```php
#[Route('/app')]
public function app(): Response
{
    $response = $this->render('app.html.twig');

    // Preload critical resources
    $response->headers->set('Link', '</css/critical.css>; rel=preload; as=style');
    $response->headers->set('Link', '</js/app.js>; rel=preload; as=script', false);
    $response->headers->set('Link', '</fonts/main.woff2>; rel=preload; as=font; crossorigin', false);

    return $response;
}
```

---

## Summary

Advanced HTTP features enable building high-performance, real-time applications:

1. **HTTP/2 & HTTP/3**: Multiplexing, server push, faster connections
2. **Streaming**: Efficient handling of large datasets and downloads
3. **SSE**: Real-time server-to-client updates
4. **WebSockets**: Full-duplex communication (via Mercure or Ratchet)
5. **Async HttpClient**: Parallel requests, retry strategies, caching
6. **Advanced Caching**: ETag, Last-Modified, Vary headers
7. **Performance**: Compression, connection pooling, profiling

These techniques are essential for modern web applications requiring real-time features and optimal performance.
