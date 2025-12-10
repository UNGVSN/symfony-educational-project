<?php

declare(strict_types=1);

namespace Tests\Console\Input;

use Console\Input\InputOption;
use PHPUnit\Framework\TestCase;

/**
 * Test for InputOption
 */
class InputOptionTest extends TestCase
{
    public function testConstructor(): void
    {
        $option = new InputOption('verbose', 'v', InputOption::VALUE_NONE, 'Verbose output');

        $this->assertEquals('verbose', $option->getName());
        $this->assertEquals('v', $option->getShortcut());
        $this->assertEquals('Verbose output', $option->getDescription());
        $this->assertFalse($option->acceptsValue());
    }

    public function testRequiredValueOption(): void
    {
        $option = new InputOption('env', null, InputOption::VALUE_REQUIRED, 'Environment');

        $this->assertTrue($option->acceptsValue());
        $this->assertTrue($option->isValueRequired());
        $this->assertFalse($option->isValueOptional());
    }

    public function testOptionalValueOption(): void
    {
        $option = new InputOption('iterations', 'i', InputOption::VALUE_OPTIONAL, 'Iterations', 1);

        $this->assertTrue($option->acceptsValue());
        $this->assertFalse($option->isValueRequired());
        $this->assertTrue($option->isValueOptional());
        $this->assertEquals(1, $option->getDefault());
    }

    public function testArrayOption(): void
    {
        $option = new InputOption('exclude', null, InputOption::VALUE_IS_ARRAY, 'Exclude patterns', []);

        $this->assertTrue($option->isArray());
        $this->assertEquals([], $option->getDefault());
    }

    public function testValueNoneCannotHaveDefault(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot set a default value when using VALUE_NONE mode');

        new InputOption('force', 'f', InputOption::VALUE_NONE, 'Force', true);
    }

    public function testArrayOptionMustHaveArrayDefault(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Default value for array option must be an array');

        new InputOption('exclude', null, InputOption::VALUE_IS_ARRAY, 'Exclude', 'not-an-array');
    }
}
