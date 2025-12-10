<?php

declare(strict_types=1);

namespace Tests\Console\Input;

use Console\Input\InputArgument;
use PHPUnit\Framework\TestCase;

/**
 * Test for InputArgument
 */
class InputArgumentTest extends TestCase
{
    public function testConstructor(): void
    {
        $argument = new InputArgument('name', InputArgument::REQUIRED, 'User name');

        $this->assertEquals('name', $argument->getName());
        $this->assertEquals('User name', $argument->getDescription());
        $this->assertTrue($argument->isRequired());
        $this->assertFalse($argument->isArray());
    }

    public function testOptionalArgument(): void
    {
        $argument = new InputArgument('name', InputArgument::OPTIONAL, 'User name', 'default');

        $this->assertFalse($argument->isRequired());
        $this->assertEquals('default', $argument->getDefault());
    }

    public function testArrayArgument(): void
    {
        $argument = new InputArgument('names', InputArgument::IS_ARRAY, 'User names', []);

        $this->assertTrue($argument->isArray());
        $this->assertEquals([], $argument->getDefault());
    }

    public function testRequiredArgumentCannotHaveDefault(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot set a default value for required argument');

        new InputArgument('name', InputArgument::REQUIRED, 'User name', 'default');
    }

    public function testArrayArgumentMustHaveArrayDefault(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Default value for array argument must be an array');

        new InputArgument('names', InputArgument::IS_ARRAY, 'User names', 'not-an-array');
    }
}
