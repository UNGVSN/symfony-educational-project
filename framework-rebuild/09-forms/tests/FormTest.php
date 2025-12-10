<?php

declare(strict_types=1);

namespace App\Tests;

use App\Form\Form;
use App\Form\FormError;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the Form class.
 */
class FormTest extends TestCase
{
    public function testFormCreation(): void
    {
        $form = new Form('user');

        $this->assertSame('user', $form->getName());
        $this->assertFalse($form->isSubmitted());
        $this->assertFalse($form->isValid());
    }

    public function testFormSubmission(): void
    {
        $form = new Form('user');
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $form->submit($data);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
    }

    public function testFormWithChildren(): void
    {
        $form = new Form('user');
        $nameField = new Form('name');
        $emailField = new Form('email');

        $form->add($nameField);
        $form->add($emailField);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('email'));
        $this->assertSame($nameField, $form->get('name'));
        $this->assertSame($emailField, $form->get('email'));
        $this->assertCount(2, $form->all());
    }

    public function testFormDataBinding(): void
    {
        $form = new Form('user');
        $form->add(new Form('name'));
        $form->add(new Form('email'));

        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $form->submit($data);

        $result = $form->getData();

        $this->assertIsArray($result);
        $this->assertSame('John Doe', $result['name']);
        $this->assertSame('john@example.com', $result['email']);
    }

    public function testFormHandleRequest(): void
    {
        $form = new Form('user');
        $form->add(new Form('name'));

        $request = Request::create(
            query: [],
            request: ['user' => ['name' => 'John']],
            server: ['REQUEST_METHOD' => 'POST']
        );

        $form->handleRequest($request);

        $this->assertTrue($form->isSubmitted());
        $this->assertSame(['name' => 'John'], $form->getData());
    }

    public function testFormValidation(): void
    {
        $form = new Form('user');
        $form->submit(['name' => 'John']);

        // Initially valid
        $this->assertTrue($form->isValid());

        // Add error
        $error = new FormError('Name is too short');
        $form->addError($error);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors());
    }

    public function testFormWithNestedErrors(): void
    {
        $form = new Form('user');
        $nameField = new Form('name');

        $form->add($nameField);
        $form->submit(['name' => 'J']);

        $nameField->addError(new FormError('Too short'));

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->getErrors(true));
    }

    public function testFormCreateView(): void
    {
        $form = new Form('user', ['required' => true, 'label' => 'User Form']);
        $form->setData('John Doe');

        $view = $form->createView();

        $this->assertSame('user', $view->vars['name']);
        $this->assertSame('user', $view->vars['id']);
        $this->assertTrue($view->vars['required']);
        $this->assertSame('User Form', $view->vars['label']);
        $this->assertSame('John Doe', $view->vars['value']);
    }

    public function testFormWithObjectData(): void
    {
        $form = new Form('user', ['data_class' => User::class]);
        $form->add(new Form('name'));
        $form->add(new Form('email'));

        $user = new User();
        $user->setName('Initial Name');
        $user->setEmail('initial@example.com');

        $form->setData($user);

        // Children should receive data from object
        $this->assertSame('Initial Name', $form->get('name')->getData());
        $this->assertSame('initial@example.com', $form->get('email')->getData());

        // Submit new data
        $form->submit(['name' => 'Updated Name', 'email' => 'updated@example.com']);

        $result = $form->getData();

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('Updated Name', $result->getName());
        $this->assertSame('updated@example.com', $result->getEmail());
    }

    public function testFormOptions(): void
    {
        $options = [
            'required' => true,
            'disabled' => false,
            'custom_option' => 'custom_value',
        ];

        $form = new Form('test', $options);

        $this->assertSame($options, $form->getOptions());
        $this->assertTrue($form->getOption('required'));
        $this->assertFalse($form->getOption('disabled'));
        $this->assertSame('custom_value', $form->getOption('custom_option'));
        $this->assertNull($form->getOption('non_existent'));
        $this->assertSame('default', $form->getOption('non_existent', 'default'));
    }

    public function testFormNotValidWhenNotSubmitted(): void
    {
        $form = new Form('test');

        $this->assertFalse($form->isValid());
    }

    public function testFormMethodHandling(): void
    {
        $form = new Form('user', ['method' => 'GET']);
        $form->add(new Form('query'));

        $request = Request::create(
            query: ['user' => ['query' => 'search term']],
            request: [],
            server: ['REQUEST_METHOD' => 'GET']
        );

        $form->handleRequest($request);

        $this->assertTrue($form->isSubmitted());
        $this->assertSame(['query' => 'search term'], $form->getData());
    }
}

/**
 * Simple User class for testing.
 */
class User
{
    private ?string $name = null;
    private ?string $email = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }
}
