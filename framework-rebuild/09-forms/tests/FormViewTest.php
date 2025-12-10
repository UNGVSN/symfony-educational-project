<?php

declare(strict_types=1);

namespace App\Tests;

use App\Form\FormView;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the FormView.
 */
class FormViewTest extends TestCase
{
    public function testFormViewCreation(): void
    {
        $view = new FormView(['foo' => 'bar']);

        $this->assertSame('bar', $view->vars['foo']);
    }

    public function testArrayAccess(): void
    {
        $view = new FormView();

        $view['foo'] = 'bar';
        $this->assertTrue(isset($view['foo']));
        $this->assertSame('bar', $view['foo']);

        unset($view['foo']);
        $this->assertFalse(isset($view['foo']));
    }

    public function testGetSetVar(): void
    {
        $view = new FormView();

        $view->setVar('name', 'John');
        $this->assertTrue($view->hasVar('name'));
        $this->assertSame('John', $view->getVar('name'));
        $this->assertNull($view->getVar('non_existent'));
        $this->assertSame('default', $view->getVar('non_existent', 'default'));
    }

    public function testChildren(): void
    {
        $parent = new FormView();
        $child1 = new FormView();
        $child2 = new FormView();

        $parent->addChild('name', $child1);
        $parent->addChild('email', $child2);

        $this->assertCount(2, $parent);
        $this->assertTrue($parent->hasChild('name'));
        $this->assertTrue($parent->hasChild('email'));
        $this->assertSame($child1, $parent->getChild('name'));
        $this->assertSame($child2, $parent->getChild('email'));
        $this->assertNull($parent->getChild('non_existent'));
    }

    public function testParentReference(): void
    {
        $parent = new FormView();
        $child = new FormView();

        $parent->addChild('child', $child);

        $this->assertSame($parent, $child->parent);
    }

    public function testIterator(): void
    {
        $view = new FormView();
        $child1 = new FormView(['value' => 'foo']);
        $child2 = new FormView(['value' => 'bar']);

        $view->addChild('field1', $child1);
        $view->addChild('field2', $child2);

        $children = [];
        foreach ($view as $name => $child) {
            $children[$name] = $child;
        }

        $this->assertSame(['field1' => $child1, 'field2' => $child2], $children);
    }

    public function testGetChildren(): void
    {
        $view = new FormView();
        $child1 = new FormView();
        $child2 = new FormView();

        $view->addChild('name', $child1);
        $view->addChild('email', $child2);

        $children = $view->getChildren();

        $this->assertCount(2, $children);
        $this->assertSame($child1, $children['name']);
        $this->assertSame($child2, $children['email']);
    }

    public function testComplexViewHierarchy(): void
    {
        // Create a view hierarchy:
        // user (root)
        //   ├── name
        //   └── address
        //       ├── street
        //       └── city

        $userView = new FormView(['id' => 'user', 'value' => null]);
        $nameView = new FormView(['id' => 'user_name', 'value' => 'John']);
        $addressView = new FormView(['id' => 'user_address', 'value' => null]);
        $streetView = new FormView(['id' => 'user_address_street', 'value' => '123 Main St']);
        $cityView = new FormView(['id' => 'user_address_city', 'value' => 'NYC']);

        $userView->addChild('name', $nameView);
        $userView->addChild('address', $addressView);
        $addressView->addChild('street', $streetView);
        $addressView->addChild('city', $cityView);

        // Test hierarchy
        $this->assertSame($userView, $nameView->parent);
        $this->assertSame($userView, $addressView->parent);
        $this->assertSame($addressView, $streetView->parent);
        $this->assertSame($addressView, $cityView->parent);

        // Test access
        $this->assertSame($nameView, $userView->getChild('name'));
        $this->assertSame($addressView, $userView->getChild('address'));
        $this->assertSame($streetView, $addressView->getChild('street'));
        $this->assertSame($cityView, $addressView->getChild('city'));

        // Test counts
        $this->assertCount(2, $userView);
        $this->assertCount(2, $addressView);
        $this->assertCount(0, $nameView);
    }
}
