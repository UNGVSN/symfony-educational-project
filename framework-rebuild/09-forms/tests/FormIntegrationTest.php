<?php

declare(strict_types=1);

namespace App\Tests;

use App\Form\FormFactory;
use App\Form\AbstractType;
use App\Form\FormBuilder;
use App\Form\OptionsResolver;
use App\Form\FormError;
use App\Form\Extension\Core\Type\TextType;
use App\Form\Extension\Core\Type\EmailType;
use App\Form\Extension\Core\Type\PasswordType;
use App\Form\Extension\Core\Type\SubmitType;
use App\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the complete form system.
 */
class FormIntegrationTest extends TestCase
{
    private FormFactory $formFactory;

    protected function setUp(): void
    {
        $this->formFactory = new FormFactory();
    }

    /**
     * Tests the complete form lifecycle: create → submit → validate → getData
     */
    public function testCompleteFormLifecycle(): void
    {
        // 1. Create form
        $form = $this->formFactory->createBuilder()
            ->add('username', TextType::class, ['required' => true])
            ->add('email', EmailType::class, ['required' => true])
            ->add('password', PasswordType::class, ['required' => true])
            ->getForm();

        $this->assertFalse($form->isSubmitted());
        $this->assertFalse($form->isValid());

        // 2. Submit data
        $form->submit([
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'secret123',
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());

        // 3. Get data
        $data = $form->getData();

        $this->assertIsArray($data);
        $this->assertSame('johndoe', $data['username']);
        $this->assertSame('john@example.com', $data['email']);
        $this->assertSame('secret123', $data['password']);
    }

    /**
     * Tests form handling with HTTP request
     */
    public function testFormWithHttpRequest(): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('name', TextType::class)
            ->add('email', EmailType::class)
            ->getForm();

        $request = Request::create(
            query: [],
            request: [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
            ],
            server: ['REQUEST_METHOD' => 'POST']
        );

        $form->handleRequest($request);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());

        $data = $form->getData();
        $this->assertSame('Jane Doe', $data['name']);
        $this->assertSame('jane@example.com', $data['email']);
    }

    /**
     * Tests custom form type with options
     */
    public function testCustomFormType(): void
    {
        $form = $this->formFactory->create(RegistrationFormType::class);

        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('password'));
        $this->assertTrue($form->has('agreeTerms'));

        $form->submit([
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'agreeTerms' => 'yes',
        ]);

        $this->assertTrue($form->isSubmitted());
        $this->assertTrue($form->isValid());
    }

    /**
     * Tests form with object data class binding
     */
    public function testFormWithDataClass(): void
    {
        $product = new Product();
        $product->setName('Laptop');
        $product->setPrice(999.99);

        $form = $this->formFactory->create(ProductFormType::class, $product);

        // Check initial data
        $this->assertSame('Laptop', $form->get('name')->getData());
        $this->assertSame(999.99, $form->get('price')->getData());

        // Update data
        $form->submit([
            'name' => 'Gaming Laptop',
            'price' => 1299.99,
        ]);

        $updatedProduct = $form->getData();

        $this->assertInstanceOf(Product::class, $updatedProduct);
        $this->assertSame('Gaming Laptop', $updatedProduct->getName());
        $this->assertSame(1299.99, $updatedProduct->getPrice());
    }

    /**
     * Tests nested forms (form within form)
     */
    public function testNestedForms(): void
    {
        $form = $this->formFactory->create(UserProfileFormType::class);

        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('address'));
        $this->assertTrue($form->get('address')->has('street'));
        $this->assertTrue($form->get('address')->has('city'));

        $form->submit([
            'username' => 'johndoe',
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'zipCode' => '10001',
            ],
        ]);

        $data = $form->getData();

        $this->assertSame('johndoe', $data['username']);
        $this->assertIsArray($data['address']);
        $this->assertSame('123 Main St', $data['address']['street']);
        $this->assertSame('New York', $data['address']['city']);
        $this->assertSame('10001', $data['address']['zipCode']);
    }

    /**
     * Tests form validation with errors
     */
    public function testFormValidationWithErrors(): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('email', EmailType::class)
            ->add('age', TextType::class)
            ->getForm();

        $form->submit([
            'email' => 'invalid-email',
            'age' => 'not-a-number',
        ]);

        // Add validation errors
        $form->get('email')->addError(new FormError('Invalid email format'));
        $form->get('age')->addError(new FormError('Must be a number'));

        $this->assertTrue($form->isSubmitted());
        $this->assertFalse($form->isValid());
        $this->assertCount(2, $form->getErrors(true));
    }

    /**
     * Tests form view creation
     */
    public function testFormViewCreation(): void
    {
        $form = $this->formFactory->createBuilder()
            ->add('name', TextType::class, [
                'label' => 'Full Name',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save',
            ])
            ->getForm();

        $view = $form->createView();

        $this->assertCount(2, $view->children);

        $nameView = $view->getChild('name');
        $this->assertSame('Full Name', $nameView->vars['label']);
        $this->assertTrue($nameView->vars['required']);
        $this->assertSame(['class' => 'form-control'], $nameView->vars['attr']);

        $submitView = $view->getChild('submit');
        $this->assertSame('Save', $submitView->vars['label']);
    }

    /**
     * Tests conditional form fields based on options
     */
    public function testConditionalFormFields(): void
    {
        // Form with newsletter field
        $form1 = $this->formFactory->create(
            ConditionalFormType::class,
            null,
            ['include_newsletter' => true]
        );

        $this->assertTrue($form1->has('email'));
        $this->assertTrue($form1->has('newsletter'));

        // Form without newsletter field
        $form2 = $this->formFactory->create(
            ConditionalFormType::class,
            null,
            ['include_newsletter' => false]
        );

        $this->assertTrue($form2->has('email'));
        $this->assertFalse($form2->has('newsletter'));
    }

    /**
     * Tests form type inheritance
     */
    public function testFormTypeInheritance(): void
    {
        $form = $this->formFactory->create(ExtendedFormType::class);

        // Should have base fields
        $this->assertTrue($form->has('base_field'));

        // And extended fields
        $this->assertTrue($form->has('extended_field'));
    }

    /**
     * Tests form with GET method
     */
    public function testFormWithGetMethod(): void
    {
        $form = $this->formFactory->createBuilder(null, null, ['method' => 'GET'])
            ->add('search', TextType::class)
            ->getForm();

        $request = Request::create(
            query: ['search' => 'query term'],
            request: [],
            server: ['REQUEST_METHOD' => 'GET']
        );

        $form->handleRequest($request);

        $this->assertTrue($form->isSubmitted());
        $this->assertSame(['search' => 'query term'], $form->getData());
    }
}

// ============================================================================
// Test Form Types
// ============================================================================

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class)
            ->add('email', EmailType::class)
            ->add('password', PasswordType::class)
            ->add('agreeTerms', TextType::class);
    }
}

class Product
{
    private ?string $name = null;
    private ?float $price = null;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): void
    {
        $this->price = $price;
    }
}

class ProductFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('price', TextType::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}

class AddressFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('street', TextType::class)
            ->add('city', TextType::class)
            ->add('zipCode', TextType::class);
    }
}

class UserProfileFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class)
            ->add('address', AddressFormType::class);
    }
}

class ConditionalFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('email', EmailType::class);

        if ($options['include_newsletter']) {
            $builder->add('newsletter', TextType::class);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'include_newsletter' => false,
        ]);
    }
}

class BaseFormType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        $builder->add('base_field', TextType::class);
    }
}

class ExtendedFormType extends BaseFormType
{
    public function buildForm(FormBuilder $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $builder->add('extended_field', TextType::class);
    }
}
