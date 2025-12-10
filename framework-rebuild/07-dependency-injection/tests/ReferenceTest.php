<?php

declare(strict_types=1);

namespace App\Tests;

use App\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;

class ReferenceTest extends TestCase
{
    public function testConstructorSetsId(): void
    {
        $reference = new Reference('test.service');

        $this->assertEquals('test.service', $reference->getId());
    }

    public function testDefaultInvalidBehavior(): void
    {
        $reference = new Reference('test.service');

        $this->assertEquals(Reference::EXCEPTION_ON_INVALID_REFERENCE, $reference->getInvalidBehavior());
    }

    public function testIgnoreOnInvalidBehavior(): void
    {
        $reference = new Reference('test.service', Reference::IGNORE_ON_INVALID_REFERENCE);

        $this->assertEquals(Reference::IGNORE_ON_INVALID_REFERENCE, $reference->getInvalidBehavior());
    }

    public function testNullOnInvalidBehavior(): void
    {
        $reference = new Reference('test.service', Reference::NULL_ON_INVALID_REFERENCE);

        $this->assertEquals(Reference::NULL_ON_INVALID_REFERENCE, $reference->getInvalidBehavior());
    }

    public function testToString(): void
    {
        $reference = new Reference('test.service');

        $this->assertEquals('test.service', (string) $reference);
    }
}
