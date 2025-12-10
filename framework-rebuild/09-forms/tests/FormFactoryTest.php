<?php

declare(strict_types=1);

namespace App\Tests;

use App\Form\FormFactory;
use App\Form\FormRegistry;
use App\Form\AbstractType;
use App\Form\FormBuilder;
use App\Form\OptionsResolver;
use App\Form\Extension\Core\Type\TextType;
use App\Form\Extension\Core\Type\EmailType;
use PHPUnit\Framework\TestCase;

/**
 * Tests for the FormFactory.
 */
class FormFactoryTest extends TestCase
{
    private FormFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new FormFactory();
    }

    public function testCreateSimpleForm(): void
    {
        $form = $this->factory->create(SimpleFormType::class);

        $this->assertSame('', $form->getName());
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('email'));
    }

    public function testCreateFormWithData(): void
    {
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $form = $this->factory->create(SimpleFormType::class, $data);

        $this->assertSame($data, $form->getData());
    }

    public function testCreateFormWithOptions(): void
    {
        $form = $this->factory->create(
            SimpleFormType::class,
            null,
            ['custom_option' => 'value']
        );

        $this->assertSame('value', $form->getOption('custom_option'));
    }

    public function testCreateBuilder(): void
    {
        $builder = $this->factory->createBuilder(SimpleFormType::class);

        $this->assertInstanceOf(FormBuilder::class, $builder);
        $this->assertTrue($builder->has('name'));
        $this->assertTrue($builder->has('email'));
    }

    public function testCreateNamedBuilder(): void
    {
        $builder = $this->factory->createNamedBuilder('user');

        $this->assertSame('user', $builder->getName());
    }

    public function testCreateBuilderWithoutType(): void
    {
        $builder = $this->factory->createBuilder();
        $builder->add('field1', TextType::class);
        $builder->add('field2', EmailType::class);

        $form = $builder->getForm();

        $this->assertTrue($form->has('field1'));
        $this->assertTrue($form->has('field2'));
    }

    public function testFormTypeOptionsResolution(): void
    {
        $form = $this->factory->create(FormWithDefaultsType::class);

        $this->assertSame('default_value', $form->getOption('option_with_default'));
        $this->assertTrue($form->getOption('required'));
    }

    public function testFormTypeInheritance(): void
    {
        $form = $this->factory->create(ChildFormType::class);

        // Should have parent's field
        $this->assertTrue($form->has('parent_field'));

        // And child's field
        $this->assertTrue($form->has('child_field'));
    }

    public function testGetRegistry(): void
    {
        $registry = $this->factory->getRegistry();

        $this->assertInstanceOf(FormRegistry::class, $registry);
    }
}

/**
 * Simple form type for testing.
 */
class SimpleFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('email', EmailType::class);
    }
}

/**
 * Form type with default options.
 */
class FormWithDefaultsType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        // Empty
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'option_with_default' => 'default_value',
            'required' => true,
        ]);
    }
}

/**
 * Parent form type for testing inheritance.
 */
class ParentFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('parent_field', TextType::class);
    }
}

/**
 * Child form type for testing inheritance.
 */
class ChildFormType extends ParentFormType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->add('child_field', TextType::class);
    }
}
