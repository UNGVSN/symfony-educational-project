<?php

declare(strict_types=1);

namespace App\Tests\Templating;

use App\Templating\TwigEngine;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Error\RuntimeError;

class TwigEngineTest extends TestCase
{
    private TwigEngine $engine;
    private Environment $twig;

    protected function setUp(): void
    {
        $loader = new ArrayLoader([
            'simple.html.twig' => 'Hello {{ name }}!',
            'multi.html.twig' => '{{ greeting }}, {{ name }}! You are {{ age }} years old.',
            'escape.html.twig' => '{{ html }}',
            'error.html.twig' => '{{ undefined_variable }}',
        ]);

        $this->twig = new Environment($loader, [
            'strict_variables' => true,
        ]);

        $this->engine = new TwigEngine($this->twig);
    }

    public function testRenderSimpleTemplate(): void
    {
        $output = $this->engine->render('simple.html.twig', ['name' => 'World']);

        $this->assertEquals('Hello World!', $output);
    }

    public function testRenderWithMultipleVariables(): void
    {
        $output = $this->engine->render('multi.html.twig', [
            'greeting' => 'Hello',
            'name' => 'John',
            'age' => 30,
        ]);

        $this->assertEquals('Hello, John! You are 30 years old.', $output);
    }

    public function testAutoEscaping(): void
    {
        $output = $this->engine->render('escape.html.twig', [
            'html' => '<script>alert("XSS")</script>',
        ]);

        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testExists(): void
    {
        $this->assertTrue($this->engine->exists('simple.html.twig'));
        $this->assertFalse($this->engine->exists('nonexistent.html.twig'));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->engine->supports('template.html.twig'));
        $this->assertTrue($this->engine->supports('template.twig'));
        $this->assertFalse($this->engine->supports('template.php'));
        $this->assertFalse($this->engine->supports('template.html'));
    }

    public function testRenderError(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error rendering Twig template');

        $this->engine->render('error.html.twig', []);
    }

    public function testGetTwig(): void
    {
        $this->assertSame($this->twig, $this->engine->getTwig());
    }

    public function testTemplateNotFound(): void
    {
        $this->expectException(\RuntimeException::class);

        $this->engine->render('nonexistent.html.twig');
    }
}
