<?php

declare(strict_types=1);

namespace App\Tests\Http;

use App\Http\AbstractController;
use App\Http\Response;
use App\Http\JsonResponse;
use App\Http\RedirectResponse;
use App\Http\NotFoundHttpException;
use App\Templating\EngineInterface;
use PHPUnit\Framework\TestCase;

class AbstractControllerTest extends TestCase
{
    private AbstractController $controller;
    private EngineInterface $templateEngine;

    protected function setUp(): void
    {
        $this->templateEngine = $this->createMock(EngineInterface::class);

        $this->controller = new class extends AbstractController {
            public function testRender(string $template, array $params = []): Response
            {
                return $this->render($template, $params);
            }

            public function testRenderView(string $template, array $params = []): string
            {
                return $this->renderView($template, $params);
            }

            public function testJson(mixed $data): JsonResponse
            {
                return $this->json($data);
            }

            public function testRedirect(string $url): RedirectResponse
            {
                return $this->redirect($url);
            }

            public function testCreateNotFoundException(): NotFoundHttpException
            {
                return $this->createNotFoundException('Custom message');
            }
        };

        $this->controller->setTemplateEngine($this->templateEngine);
    }

    public function testRender(): void
    {
        $this->templateEngine
            ->expects($this->once())
            ->method('render')
            ->with('blog/show.html.twig', ['post' => 'data'])
            ->willReturn('<html>Rendered content</html>');

        $response = $this->controller->testRender('blog/show.html.twig', ['post' => 'data']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('<html>Rendered content</html>', $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testRenderView(): void
    {
        $this->templateEngine
            ->expects($this->once())
            ->method('render')
            ->with('blog/show.html.twig', ['post' => 'data'])
            ->willReturn('<html>Rendered content</html>');

        $content = $this->controller->testRenderView('blog/show.html.twig', ['post' => 'data']);

        $this->assertEquals('<html>Rendered content</html>', $content);
    }

    public function testRenderWithoutTemplateEngine(): void
    {
        $controller = new class extends AbstractController {
            public function testRender(): Response
            {
                return $this->render('template.html.twig');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template engine not configured');

        $controller->testRender();
    }

    public function testJson(): void
    {
        $data = ['name' => 'John', 'age' => 30];

        $response = $this->controller->testJson($data);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(json_encode($data), $response->getContent());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaders()['Content-Type']);
    }

    public function testRedirect(): void
    {
        $response = $this->controller->testRedirect('/blog');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/blog', $response->getHeaders()['Location']);
    }

    public function testCreateNotFoundException(): void
    {
        $exception = $this->controller->testCreateNotFoundException();

        $this->assertInstanceOf(NotFoundHttpException::class, $exception);
        $this->assertEquals('Custom message', $exception->getMessage());
        $this->assertEquals(404, $exception->getCode());
    }
}
