<?php

declare(strict_types=1);

namespace App\Tests;

use App\Form\OptionsResolver;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the OptionsResolver.
 */
class OptionsResolverTest extends TestCase
{
    private OptionsResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new OptionsResolver();
    }

    public function testSetDefaults(): void
    {
        $this->resolver->setDefaults([
            'foo' => 'bar',
            'baz' => 42,
        ]);

        $resolved = $this->resolver->resolve([]);

        $this->assertSame('bar', $resolved['foo']);
        $this->assertSame(42, $resolved['baz']);
    }

    public function testResolveOverridesDefaults(): void
    {
        $this->resolver->setDefaults([
            'foo' => 'bar',
        ]);

        $resolved = $this->resolver->resolve([
            'foo' => 'overridden',
        ]);

        $this->assertSame('overridden', $resolved['foo']);
    }

    public function testSetRequired(): void
    {
        $this->resolver->setRequired(['foo']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The required option "foo" is missing');

        $this->resolver->resolve([]);
    }

    public function testRequiredWithDefault(): void
    {
        $this->resolver->setRequired(['foo']);
        $this->resolver->setDefaults(['foo' => 'default']);

        $resolved = $this->resolver->resolve([]);

        $this->assertSame('default', $resolved['foo']);
    }

    public function testUndefinedOption(): void
    {
        $this->resolver->setDefaults(['foo' => 'bar']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The option "baz" does not exist');

        $this->resolver->resolve(['baz' => 'value']);
    }

    public function testSetDefined(): void
    {
        $this->resolver->setDefined(['foo']);

        $resolved = $this->resolver->resolve(['foo' => 'bar']);

        $this->assertSame('bar', $resolved['foo']);
    }

    public function testSetAllowedTypes(): void
    {
        $this->resolver->setDefaults(['foo' => 'bar']);
        $this->resolver->setAllowedTypes('foo', 'string');

        $resolved = $this->resolver->resolve(['foo' => 'valid']);

        $this->assertSame('valid', $resolved['foo']);
    }

    public function testInvalidType(): void
    {
        $this->resolver->setDefaults(['foo' => 'bar']);
        $this->resolver->setAllowedTypes('foo', 'string');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('expected to be of type "string"');

        $this->resolver->resolve(['foo' => 123]);
    }

    public function testMultipleAllowedTypes(): void
    {
        $this->resolver->setDefaults(['foo' => null]);
        $this->resolver->setAllowedTypes('foo', ['string', 'int']);

        $resolved1 = $this->resolver->resolve(['foo' => 'string']);
        $resolved2 = $this->resolver->resolve(['foo' => 123]);

        $this->assertSame('string', $resolved1['foo']);
        $this->assertSame(123, $resolved2['foo']);
    }

    public function testNullableType(): void
    {
        $this->resolver->setDefaults(['foo' => null]);
        $this->resolver->setAllowedTypes('foo', '?string');

        $resolved1 = $this->resolver->resolve(['foo' => null]);
        $resolved2 = $this->resolver->resolve(['foo' => 'value']);

        $this->assertNull($resolved1['foo']);
        $this->assertSame('value', $resolved2['foo']);
    }

    public function testSetAllowedValues(): void
    {
        $this->resolver->setDefaults(['foo' => 'bar']);
        $this->resolver->setAllowedValues('foo', ['bar', 'baz', 'qux']);

        $resolved = $this->resolver->resolve(['foo' => 'baz']);

        $this->assertSame('baz', $resolved['foo']);
    }

    public function testInvalidValue(): void
    {
        $this->resolver->setDefaults(['foo' => 'bar']);
        $this->resolver->setAllowedValues('foo', ['bar', 'baz']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('is invalid. Accepted values are');

        $this->resolver->resolve(['foo' => 'invalid']);
    }

    public function testBooleanType(): void
    {
        $this->resolver->setDefaults(['flag' => false]);
        $this->resolver->setAllowedTypes('flag', 'bool');

        $resolved = $this->resolver->resolve(['flag' => true]);

        $this->assertTrue($resolved['flag']);
    }

    public function testArrayType(): void
    {
        $this->resolver->setDefaults(['items' => []]);
        $this->resolver->setAllowedTypes('items', 'array');

        $resolved = $this->resolver->resolve(['items' => [1, 2, 3]]);

        $this->assertSame([1, 2, 3], $resolved['items']);
    }

    public function testObjectType(): void
    {
        $this->resolver->setDefaults(['obj' => null]);
        $this->resolver->setAllowedTypes('obj', \stdClass::class);

        $obj = new \stdClass();
        $resolved = $this->resolver->resolve(['obj' => $obj]);

        $this->assertSame($obj, $resolved['obj']);
    }

    public function testComplexConfiguration(): void
    {
        $this->resolver->setDefaults([
            'required_field' => true,
            'optional_field' => 'default',
        ]);

        $this->resolver->setRequired(['name']);
        $this->resolver->setDefined(['extra']);
        $this->resolver->setAllowedTypes('required_field', 'bool');
        $this->resolver->setAllowedTypes('name', 'string');
        $this->resolver->setAllowedValues('optional_field', ['default', 'custom']);

        $resolved = $this->resolver->resolve([
            'name' => 'test',
            'optional_field' => 'custom',
            'extra' => 'value',
        ]);

        $this->assertSame('test', $resolved['name']);
        $this->assertTrue($resolved['required_field']);
        $this->assertSame('custom', $resolved['optional_field']);
        $this->assertSame('value', $resolved['extra']);
    }
}
