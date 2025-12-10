<?php

declare(strict_types=1);

namespace Tests\Command;

use Command\GreetCommand;
use Console\Input\ArrayInput;
use Console\Output\BufferedOutput;
use PHPUnit\Framework\TestCase;

/**
 * Test for GreetCommand
 */
class GreetCommandTest extends TestCase
{
    public function testExecuteWithBasicName(): void
    {
        $command = new GreetCommand();

        $input = new ArrayInput([
            'name' => 'John',
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Hello, John!', $output->getContent());
    }

    public function testExecuteWithUppercase(): void
    {
        $command = new GreetCommand();

        $input = new ArrayInput([
            'name' => 'John',
            '--uppercase' => true,
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('HELLO, JOHN!', $output->getContent());
    }

    public function testExecuteWithYell(): void
    {
        $command = new GreetCommand();

        $input = new ArrayInput([
            'name' => 'John',
            '--yell' => true,
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Hello, John!!!', $output->getContent());
    }

    public function testExecuteWithLastName(): void
    {
        $command = new GreetCommand();

        $input = new ArrayInput([
            'name' => 'John',
            'last-name' => 'Doe',
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Hello, John Doe!', $output->getContent());
    }

    public function testExecuteWithMultipleIterations(): void
    {
        $command = new GreetCommand();

        $input = new ArrayInput([
            'name' => 'World',
            '--iterations' => 3,
        ]);

        $output = new BufferedOutput();

        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);

        // Should contain the greeting multiple times
        $content = $output->getContent();
        $count = substr_count($content, 'Hello, World!');
        $this->assertGreaterThanOrEqual(3, $count);
    }
}
