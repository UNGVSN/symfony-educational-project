<?php

declare(strict_types=1);

namespace App\Tests\Templating;

use App\Templating\PhpEngine;
use App\Templating\Helper\HelperInterface;
use PHPUnit\Framework\TestCase;

class PhpEngineTest extends TestCase
{
    private string $templateDir;
    private PhpEngine $engine;

    protected function setUp(): void
    {
        $this->templateDir = sys_get_temp_dir() . '/templates_' . uniqid();
        mkdir($this->templateDir);

        $this->engine = new PhpEngine($this->templateDir);
    }

    protected function tearDown(): void
    {
        // Clean up template files
        $files = glob($this->templateDir . '/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->templateDir);
    }

    public function testRenderSimpleTemplate(): void
    {
        $template = 'simple.php';
        file_put_contents(
            $this->templateDir . '/' . $template,
            'Hello <?= $name ?>!'
        );

        $output = $this->engine->render($template, ['name' => 'World']);

        $this->assertEquals('Hello World!', $output);
    }

    public function testRenderWithMultipleVariables(): void
    {
        $template = 'multi.php';
        file_put_contents(
            $this->templateDir . '/' . $template,
            '<?= $greeting ?>, <?= $name ?>! You are <?= $age ?> years old.'
        );

        $output = $this->engine->render($template, [
            'greeting' => 'Hello',
            'name' => 'John',
            'age' => 30,
        ]);

        $this->assertEquals('Hello, John! You are 30 years old.', $output);
    }

    public function testTemplateNotFound(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Template not found');

        $this->engine->render('nonexistent.php');
    }

    public function testExists(): void
    {
        $template = 'exists.php';
        file_put_contents($this->templateDir . '/' . $template, 'Content');

        $this->assertTrue($this->engine->exists($template));
        $this->assertFalse($this->engine->exists('nonexistent.php'));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->engine->supports('template.php'));
        $this->assertFalse($this->engine->supports('template.twig'));
        $this->assertFalse($this->engine->supports('template.html'));
    }

    public function testEscapeHelper(): void
    {
        $template = 'escape.php';
        file_put_contents(
            $this->templateDir . '/' . $template,
            '<?= $this->escape($html) ?>'
        );

        $output = $this->engine->render($template, [
            'html' => '<script>alert("XSS")</script>',
        ]);

        $this->assertStringContainsString('&lt;script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
    }

    public function testCustomHelper(): void
    {
        $helper = new class implements HelperInterface {
            public function getName(): string
            {
                return 'upper';
            }

            public function __invoke(string $text): string
            {
                return strtoupper($text);
            }
        };

        $this->engine->addHelper('upper', $helper);

        $template = 'helper.php';
        file_put_contents(
            $this->templateDir . '/' . $template,
            '<?= $upper("hello") ?>'
        );

        $output = $this->engine->render($template);

        $this->assertEquals('HELLO', $output);
    }

    public function testOutputBufferingOnError(): void
    {
        $template = 'error.php';
        file_put_contents(
            $this->templateDir . '/' . $template,
            '<?php echo "Before error"; throw new \Exception("Error in template"); ?>'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error rendering template');

        // Should not output "Before error" due to output buffering
        $this->engine->render($template);
    }

    public function testTemplateIsolation(): void
    {
        $template = 'isolation.php';
        file_put_contents(
            $this->templateDir . '/' . $template,
            '<?= $name ?>'
        );

        // First render
        $output1 = $this->engine->render($template, ['name' => 'First']);

        // Second render should not have access to first render's variables
        $output2 = $this->engine->render($template, ['name' => 'Second']);

        $this->assertEquals('First', $output1);
        $this->assertEquals('Second', $output2);
    }

    public function testCircularDependencyDetection(): void
    {
        $template1 = 'circular1.php';
        $template2 = 'circular2.php';

        file_put_contents(
            $this->templateDir . '/' . $template1,
            '<?php $this->render("circular2.php"); ?>'
        );

        file_put_contents(
            $this->templateDir . '/' . $template2,
            '<?php $this->render("circular1.php"); ?>'
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Circular template reference');

        $this->engine->render($template1);
    }

    public function testAddExtensionAutomatically(): void
    {
        $template = 'auto-extension';
        file_put_contents($this->templateDir . '/auto-extension.php', 'Content');

        $output = $this->engine->render($template);

        $this->assertEquals('Content', $output);
    }

    public function testInvalidTemplateDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Template directory does not exist');

        new PhpEngine('/nonexistent/directory');
    }
}
