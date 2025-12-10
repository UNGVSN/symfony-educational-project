<?php

declare(strict_types=1);

namespace Tests\Console;

use Command\GreetCommand;
use Console\Application;
use Console\Command\Command;
use Console\Input\ArrayInput;
use Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;

/**
 * Test for Application
 */
class ApplicationTest extends TestCase
{
    public function testAddCommand(): void
    {
        $application = new Application();
        $command = new GreetCommand();

        $application->add($command);

        $this->assertTrue($application->has('app:greet'));
        $this->assertSame($command, $application->find('app:greet'));
    }

    public function testFindCommandThrowsExceptionWhenNotFound(): void
    {
        $application = new Application();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Command "non-existent" not found');

        $application->find('non-existent');
    }

    public function testGetName(): void
    {
        $application = new Application('My App', '1.0.0');

        $this->assertEquals('My App', $application->getName());
    }

    public function testGetVersion(): void
    {
        $application = new Application('My App', '1.0.0');

        $this->assertEquals('1.0.0', $application->getVersion());
    }

    public function testHasCommand(): void
    {
        $application = new Application();
        $command = new GreetCommand();

        $this->assertFalse($application->has('app:greet'));

        $application->add($command);

        $this->assertTrue($application->has('app:greet'));
    }

    public function testGetAllCommands(): void
    {
        $application = new Application();
        $command1 = new GreetCommand();

        $application->add($command1);

        $commands = $application->all();

        $this->assertIsArray($commands);
        $this->assertCount(2, $commands); // Command + its alias
        $this->assertArrayHasKey('app:greet', $commands);
    }
}
