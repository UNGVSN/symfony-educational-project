<?php

declare(strict_types=1);

namespace FrameworkRebuild\HttpFoundation\Tests;

use FrameworkRebuild\HttpFoundation\ParameterBag;
use PHPUnit\Framework\TestCase;

/**
 * Test cases for the ParameterBag class.
 */
class ParameterBagTest extends TestCase
{
    public function testConstructorWithEmptyArray(): void
    {
        $bag = new ParameterBag();
        $this->assertSame([], $bag->all());
    }

    public function testConstructorWithParameters(): void
    {
        $params = ['foo' => 'bar', 'number' => 42];
        $bag = new ParameterBag($params);

        $this->assertSame($params, $bag->all());
    }

    public function testAll(): void
    {
        $params = ['a' => 1, 'b' => 2, 'c' => 3];
        $bag = new ParameterBag($params);

        $this->assertSame($params, $bag->all());
    }

    public function testKeys(): void
    {
        $bag = new ParameterBag(['foo' => 'bar', 'baz' => 'qux']);

        $this->assertSame(['foo', 'baz'], $bag->keys());
    }

    public function testGet(): void
    {
        $bag = new ParameterBag(['name' => 'John', 'age' => 30]);

        $this->assertSame('John', $bag->get('name'));
        $this->assertSame(30, $bag->get('age'));
        $this->assertNull($bag->get('nonexistent'));
    }

    public function testGetWithDefault(): void
    {
        $bag = new ParameterBag(['foo' => 'bar']);

        $this->assertSame('bar', $bag->get('foo'));
        $this->assertSame('default', $bag->get('nonexistent', 'default'));
        $this->assertSame(123, $bag->get('missing', 123));
    }

    public function testSet(): void
    {
        $bag = new ParameterBag();
        $bag->set('key', 'value');

        $this->assertSame('value', $bag->get('key'));

        $bag->set('key', 'new value');
        $this->assertSame('new value', $bag->get('key'));
    }

    public function testHas(): void
    {
        $bag = new ParameterBag(['exists' => 'yes']);

        $this->assertTrue($bag->has('exists'));
        $this->assertFalse($bag->has('does-not-exist'));
    }

    public function testHasWithNullValue(): void
    {
        $bag = new ParameterBag(['null-value' => null]);

        // has() should return true even if value is null
        $this->assertTrue($bag->has('null-value'));
    }

    public function testRemove(): void
    {
        $bag = new ParameterBag(['key' => 'value']);

        $this->assertTrue($bag->has('key'));

        $bag->remove('key');

        $this->assertFalse($bag->has('key'));
        $this->assertNull($bag->get('key'));
    }

    public function testGetInt(): void
    {
        $bag = new ParameterBag([
            'string-number' => '42',
            'int-number' => 100,
            'float-number' => 3.14,
            'text' => 'not a number',
        ]);

        $this->assertSame(42, $bag->getInt('string-number'));
        $this->assertSame(100, $bag->getInt('int-number'));
        $this->assertSame(3, $bag->getInt('float-number'));
        $this->assertSame(0, $bag->getInt('text'));
        $this->assertSame(0, $bag->getInt('nonexistent'));
    }

    public function testGetIntWithDefault(): void
    {
        $bag = new ParameterBag();

        $this->assertSame(99, $bag->getInt('nonexistent', 99));
    }

    public function testGetBoolean(): void
    {
        $bag = new ParameterBag([
            'true-string' => 'true',
            'false-string' => 'false',
            'one' => 1,
            'zero' => 0,
            'empty-string' => '',
            'non-empty' => 'yes',
        ]);

        $this->assertTrue($bag->getBoolean('true-string'));
        $this->assertTrue($bag->getBoolean('false-string')); // Non-empty string is truthy
        $this->assertTrue($bag->getBoolean('one'));
        $this->assertFalse($bag->getBoolean('zero'));
        $this->assertFalse($bag->getBoolean('empty-string'));
        $this->assertTrue($bag->getBoolean('non-empty'));
        $this->assertFalse($bag->getBoolean('nonexistent'));
    }

    public function testGetBooleanWithDefault(): void
    {
        $bag = new ParameterBag();

        $this->assertTrue($bag->getBoolean('nonexistent', true));
        $this->assertFalse($bag->getBoolean('nonexistent', false));
    }

    public function testGetString(): void
    {
        $bag = new ParameterBag([
            'string' => 'hello',
            'number' => 42,
            'float' => 3.14,
            'bool' => true,
        ]);

        $this->assertSame('hello', $bag->getString('string'));
        $this->assertSame('42', $bag->getString('number'));
        $this->assertSame('3.14', $bag->getString('float'));
        $this->assertSame('1', $bag->getString('bool'));
        $this->assertSame('', $bag->getString('nonexistent'));
    }

    public function testGetStringWithDefault(): void
    {
        $bag = new ParameterBag();

        $this->assertSame('default', $bag->getString('nonexistent', 'default'));
    }

    public function testGetStringWithNonScalar(): void
    {
        $bag = new ParameterBag([
            'array' => ['a', 'b', 'c'],
            'object' => new \stdClass(),
        ]);

        // Should return default for non-scalar values
        $this->assertSame('', $bag->getString('array'));
        $this->assertSame('', $bag->getString('object'));
        $this->assertSame('fallback', $bag->getString('array', 'fallback'));
    }

    public function testGetStringWithStringableObject(): void
    {
        $stringable = new class {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        $bag = new ParameterBag(['obj' => $stringable]);

        $this->assertSame('stringable', $bag->getString('obj'));
    }

    public function testGetAlpha(): void
    {
        $bag = new ParameterBag([
            'mixed' => 'abc123def',
            'alpha' => 'abcdef',
            'numbers' => '123456',
            'special' => 'hello@world!',
        ]);

        $this->assertSame('abcdef', $bag->getAlpha('mixed'));
        $this->assertSame('abcdef', $bag->getAlpha('alpha'));
        $this->assertSame('', $bag->getAlpha('numbers'));
        $this->assertSame('helloworld', $bag->getAlpha('special'));
        $this->assertSame('', $bag->getAlpha('nonexistent'));
    }

    public function testGetAlnum(): void
    {
        $bag = new ParameterBag([
            'mixed' => 'abc123def',
            'alnum' => 'test123',
            'special' => 'hello@world!456',
        ]);

        $this->assertSame('abc123def', $bag->getAlnum('mixed'));
        $this->assertSame('test123', $bag->getAlnum('alnum'));
        $this->assertSame('helloworld456', $bag->getAlnum('special'));
        $this->assertSame('', $bag->getAlnum('nonexistent'));
    }

    public function testGetDigits(): void
    {
        $bag = new ParameterBag([
            'mixed' => 'abc123def456',
            'digits' => '123456',
            'alpha' => 'abcdef',
            'phone' => '+1-555-0123',
        ]);

        $this->assertSame('123456', $bag->getDigits('mixed'));
        $this->assertSame('123456', $bag->getDigits('digits'));
        $this->assertSame('', $bag->getDigits('alpha'));
        $this->assertSame('15550123', $bag->getDigits('phone'));
        $this->assertSame('', $bag->getDigits('nonexistent'));
    }

    public function testCount(): void
    {
        $bag = new ParameterBag();
        $this->assertSame(0, $bag->count());

        $bag->set('a', 1);
        $this->assertSame(1, $bag->count());

        $bag->set('b', 2);
        $bag->set('c', 3);
        $this->assertSame(3, $bag->count());

        $bag->remove('b');
        $this->assertSame(2, $bag->count());
    }

    public function testGetIterator(): void
    {
        $params = ['a' => 1, 'b' => 2, 'c' => 3];
        $bag = new ParameterBag($params);

        $iterator = $bag->getIterator();

        $this->assertInstanceOf(\ArrayIterator::class, $iterator);

        $result = [];
        foreach ($iterator as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame($params, $result);
    }

    public function testCanIterate(): void
    {
        $params = ['foo' => 'bar', 'baz' => 'qux'];
        $bag = new ParameterBag($params);

        $result = [];
        foreach ($bag as $key => $value) {
            $result[$key] = $value;
        }

        $this->assertSame($params, $result);
    }

    public function testTypeJuggling(): void
    {
        $bag = new ParameterBag([
            'zero-string' => '0',
            'zero-int' => 0,
            'false-bool' => false,
            'empty-string' => '',
        ]);

        // Test that getInt correctly converts different falsy values
        $this->assertSame(0, $bag->getInt('zero-string'));
        $this->assertSame(0, $bag->getInt('zero-int'));
        $this->assertSame(0, $bag->getInt('false-bool'));
        $this->assertSame(0, $bag->getInt('empty-string'));

        // Test that getBoolean correctly converts different falsy values
        $this->assertFalse($bag->getBoolean('zero-string')); // '0' is falsy
        $this->assertFalse($bag->getBoolean('zero-int'));
        $this->assertFalse($bag->getBoolean('false-bool'));
        $this->assertFalse($bag->getBoolean('empty-string'));

        // Test that getString preserves these values
        $this->assertSame('0', $bag->getString('zero-string'));
        $this->assertSame('0', $bag->getString('zero-int'));
        $this->assertSame('', $bag->getString('false-bool')); // false becomes empty string
        $this->assertSame('', $bag->getString('empty-string'));
    }
}
