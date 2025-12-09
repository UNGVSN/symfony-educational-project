# Controller Patterns

This directory contains production-ready Symfony controller examples using PHP 8.2+ attributes and Symfony 7.x+ syntax.

## Table of Contents

1. [Basic CRUD Controller](#basic-crud-controller)
2. [API Controller with JSON Responses](#api-controller-with-json-responses)
3. [File Upload Controller](#file-upload-controller)
4. [Invokable Controller](#invokable-controller)
5. [Controller with Dependency Injection](#controller-with-dependency-injection)

---

## Basic CRUD Controller

A standard CRUD controller for managing blog posts with all basic operations.

```php
<?php

namespace App\Controller;

use App\Entity\Post;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/posts')]
class PostController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PostRepository $postRepository,
    ) {
    }

    #[Route('', name: 'post_index', methods: ['GET'])]
    public function index(): Response
    {
        $posts = $this->postRepository->findBy(
            [],
            ['createdAt' => 'DESC']
        );

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/new', name: 'post_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_AUTHOR')]
    public function new(Request $request): Response
    {
        $post = new Post();
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post->setAuthor($this->getUser());

            $this->entityManager->persist($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Post created successfully!');

            return $this->redirectToRoute('post_show', [
                'id' => $post->getId()
            ]);
        }

        return $this->render('post/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'post_show', methods: ['GET'])]
    public function show(Post $post): Response
    {
        return $this->render('post/show.html.twig', [
            'post' => $post,
        ]);
    }

    #[Route('/{id}/edit', name: 'post_edit', methods: ['GET', 'POST'])]
    #[IsGranted('EDIT', subject: 'post')]
    public function edit(Request $request, Post $post): Response
    {
        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Post updated successfully!');

            return $this->redirectToRoute('post_show', [
                'id' => $post->getId()
            ]);
        }

        return $this->render('post/edit.html.twig', [
            'post' => $post,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'post_delete', methods: ['POST'])]
    #[IsGranted('DELETE', subject: 'post')]
    public function delete(Request $request, Post $post): Response
    {
        if ($this->isCsrfTokenValid('delete'.$post->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($post);
            $this->entityManager->flush();

            $this->addFlash('success', 'Post deleted successfully!');
        }

        return $this->redirectToRoute('post_index');
    }
}
```

**Key Features:**
- Constructor-based dependency injection
- Route attributes with HTTP method constraints
- Security attributes for authorization
- CSRF protection on delete operations
- Flash messages for user feedback
- Doctrine ORM integration

---

## API Controller with JSON Responses

Modern REST API controller with JSON responses and proper HTTP status codes.

```php
<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', format: 'json')]
class ProductApiController extends AbstractController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'api_product_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));

        $products = $this->productRepository->findBy(
            ['active' => true],
            ['createdAt' => 'DESC'],
            $limit,
            ($page - 1) * $limit
        );

        return $this->json($products, Response::HTTP_OK, [], [
            'groups' => ['product:list']
        ]);
    }

    #[Route('/{id}', name: 'api_product_show', methods: ['GET'])]
    public function show(Product $product): JsonResponse
    {
        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product:detail']
        ]);
    }

    #[Route('', name: 'api_product_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $product = $this->serializer->deserialize(
            $request->getContent(),
            Product::class,
            'json',
            ['groups' => ['product:write']]
        );

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_CREATED, [
            'Location' => $this->generateUrl('api_product_show', ['id' => $product->getId()])
        ], [
            'groups' => ['product:detail']
        ]);
    }

    #[Route('/{id}', name: 'api_product_update', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, Product $product): JsonResponse
    {
        $this->serializer->deserialize(
            $request->getContent(),
            Product::class,
            'json',
            [
                'object_to_populate' => $product,
                'groups' => ['product:write']
            ]
        );

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($product, Response::HTTP_OK, [], [
            'groups' => ['product:detail']
        ]);
    }

    #[Route('/{id}', name: 'api_product_delete', methods: ['DELETE'])]
    public function delete(Product $product): JsonResponse
    {
        $this->entityManager->remove($product);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
```

**Key Features:**
- RESTful endpoint design
- Serialization groups for different contexts
- Validation with detailed error responses
- Pagination support
- Proper HTTP status codes
- Location header on resource creation

---

## File Upload Controller

Secure file upload handling with validation and storage.

```php
<?php

namespace App\Controller;

use App\Entity\Document;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/documents')]
class DocumentUploadController extends AbstractController
{
    public function __construct(
        private readonly FileUploader $fileUploader,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('/upload', name: 'document_upload', methods: ['GET', 'POST'])]
    public function upload(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $uploadedFile = $request->files->get('document');

            // Validate the uploaded file
            $errors = $this->validator->validate($uploadedFile, [
                new Assert\NotNull(message: 'Please select a file to upload'),
                new Assert\File(
                    maxSize: '10M',
                    mimeTypes: [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'image/jpeg',
                        'image/png',
                    ],
                    mimeTypesMessage: 'Please upload a valid document (PDF, DOC, DOCX, JPG, PNG)'
                ),
            ]);

            if (count($errors) > 0) {
                $this->addFlash('error', $errors[0]->getMessage());
                return $this->redirectToRoute('document_upload');
            }

            try {
                // Upload the file
                $fileName = $this->fileUploader->upload($uploadedFile);

                // Create document entity
                $document = new Document();
                $document->setFileName($fileName);
                $document->setOriginalName($uploadedFile->getClientOriginalName());
                $document->setMimeType($uploadedFile->getMimeType());
                $document->setSize($uploadedFile->getSize());
                $document->setUploadedBy($this->getUser());

                $this->entityManager->persist($document);
                $this->entityManager->flush();

                $this->addFlash('success', 'File uploaded successfully!');

                return $this->redirectToRoute('document_show', [
                    'id' => $document->getId()
                ]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error uploading file: ' . $e->getMessage());
            }
        }

        return $this->render('document/upload.html.twig');
    }

    #[Route('/{id}/download', name: 'document_download', methods: ['GET'])]
    public function download(Document $document): Response
    {
        $filePath = $this->fileUploader->getTargetDirectory() . '/' . $document->getFileName();

        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('File not found');
        }

        return $this->file($filePath, $document->getOriginalName());
    }

    #[Route('/batch-upload', name: 'document_batch_upload', methods: ['POST'])]
    public function batchUpload(Request $request): Response
    {
        $uploadedFiles = $request->files->get('documents', []);
        $successCount = 0;
        $errors = [];

        foreach ($uploadedFiles as $uploadedFile) {
            if (!$uploadedFile instanceof UploadedFile) {
                continue;
            }

            try {
                $fileName = $this->fileUploader->upload($uploadedFile);

                $document = new Document();
                $document->setFileName($fileName);
                $document->setOriginalName($uploadedFile->getClientOriginalName());
                $document->setMimeType($uploadedFile->getMimeType());
                $document->setSize($uploadedFile->getSize());
                $document->setUploadedBy($this->getUser());

                $this->entityManager->persist($document);
                $successCount++;
            } catch (\Exception $e) {
                $errors[] = sprintf(
                    '%s: %s',
                    $uploadedFile->getClientOriginalName(),
                    $e->getMessage()
                );
            }
        }

        if ($successCount > 0) {
            $this->entityManager->flush();
            $this->addFlash('success', sprintf('Successfully uploaded %d file(s)', $successCount));
        }

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $this->addFlash('error', $error);
            }
        }

        return $this->redirectToRoute('document_index');
    }
}
```

**Key Features:**
- File validation (size, mime type)
- Secure file handling
- Batch upload support
- File download with original filename
- Error handling and user feedback
- Metadata storage in database

---

## Invokable Controller

Single-action controller using the `__invoke()` method for focused responsibilities.

```php
<?php

namespace App\Controller;

use App\Entity\Newsletter;
use App\Service\NewsletterService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/newsletter/subscribe', name: 'newsletter_subscribe', methods: ['POST'])]
class NewsletterSubscribeController extends AbstractController
{
    public function __construct(
        private readonly NewsletterService $newsletterService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $email = $request->request->get('email');

        // Validate email
        $errors = $this->validator->validate($email, [
            new Assert\NotBlank(message: 'Email is required'),
            new Assert\Email(message: 'Please provide a valid email address'),
        ]);

        if (count($errors) > 0) {
            $this->addFlash('error', $errors[0]->getMessage());
            return $this->redirectToRoute('home');
        }

        // Check if already subscribed
        $existingSubscriber = $this->entityManager
            ->getRepository(Newsletter::class)
            ->findOneBy(['email' => $email]);

        if ($existingSubscriber) {
            if ($existingSubscriber->isActive()) {
                $this->addFlash('info', 'You are already subscribed to our newsletter!');
            } else {
                $existingSubscriber->setActive(true);
                $this->entityManager->flush();
                $this->addFlash('success', 'Your newsletter subscription has been reactivated!');
            }
        } else {
            // Create new subscriber
            $subscriber = new Newsletter();
            $subscriber->setEmail($email);
            $subscriber->setToken(bin2hex(random_bytes(32)));

            $this->entityManager->persist($subscriber);
            $this->entityManager->flush();

            // Send confirmation email
            $this->newsletterService->sendConfirmationEmail($subscriber);

            $this->addFlash(
                'success',
                'Thank you for subscribing! Please check your email to confirm your subscription.'
            );
        }

        return $this->redirectToRoute('home');
    }
}
```

**Another Example - Export Report:**

```php
<?php

namespace App\Controller\Report;

use App\Service\ReportGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/reports/sales/export', name: 'report_sales_export')]
#[IsGranted('ROLE_MANAGER')]
class ExportSalesReportController extends AbstractController
{
    public function __construct(
        private readonly ReportGenerator $reportGenerator,
    ) {
    }

    public function __invoke(): Response
    {
        $report = $this->reportGenerator->generateSalesReport(
            new \DateTimeImmutable('first day of this month'),
            new \DateTimeImmutable('last day of this month')
        );

        $response = new Response($report->getContent());
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set(
            'Content-Disposition',
            sprintf('attachment; filename="sales_report_%s.csv"', date('Y-m-d'))
        );

        return $response;
    }
}
```

**Key Features:**
- Single responsibility principle
- Clean, focused action
- Ideal for simple operations
- Easy to test and maintain
- Self-documenting route structure

---

## Controller with Dependency Injection

Advanced controller demonstrating various dependency injection patterns.

```php
<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Service\Analytics\OrderAnalyticsService;
use App\Service\Cache\CacheManager;
use App\Service\Export\ExportServiceInterface;
use App\Service\Notification\NotificationServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\CacheInterface;

#[Route('/admin/orders')]
class OrderManagementController extends AbstractController
{
    public function __construct(
        // Repository injection
        private readonly OrderRepository $orderRepository,

        // Service injection
        private readonly OrderAnalyticsService $analyticsService,

        // Interface-based injection
        private readonly NotificationServiceInterface $notificationService,
        private readonly ExportServiceInterface $exportService,

        // Logger with channel
        #[Autowire(service: 'monolog.logger.order')]
        private readonly LoggerInterface $logger,

        // Cache injection
        private readonly CacheInterface $cache,

        // Parameter injection
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,

        #[Autowire('%app.orders.items_per_page%')]
        private readonly int $itemsPerPage,
    ) {
    }

    #[Route('', name: 'admin_order_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));

        // Use cache for expensive operations
        $orders = $this->cache->get(
            sprintf('admin_orders_page_%d', $page),
            function () use ($page) {
                return $this->orderRepository->findPaginated(
                    $page,
                    $this->itemsPerPage
                );
            }
        );

        $this->logger->info('Order list accessed', [
            'page' => $page,
            'user' => $this->getUser()?->getUserIdentifier(),
        ]);

        return $this->render('admin/order/index.html.twig', [
            'orders' => $orders,
            'currentPage' => $page,
        ]);
    }

    #[Route('/analytics', name: 'admin_order_analytics', methods: ['GET'])]
    public function analytics(): Response
    {
        $analytics = $this->analyticsService->getMonthlyAnalytics();

        return $this->render('admin/order/analytics.html.twig', [
            'analytics' => $analytics,
        ]);
    }

    #[Route('/{id}/notify', name: 'admin_order_notify', methods: ['POST'])]
    public function notify(int $id): Response
    {
        $order = $this->orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found');
        }

        try {
            $this->notificationService->sendOrderUpdate($order);

            $this->logger->info('Order notification sent', [
                'order_id' => $id,
                'customer' => $order->getCustomer()->getEmail(),
            ]);

            $this->addFlash('success', 'Notification sent successfully!');
        } catch (\Exception $e) {
            $this->logger->error('Failed to send order notification', [
                'order_id' => $id,
                'error' => $e->getMessage(),
            ]);

            $this->addFlash('error', 'Failed to send notification.');
        }

        return $this->redirectToRoute('admin_order_index');
    }

    #[Route('/export', name: 'admin_order_export', methods: ['GET'])]
    public function export(Request $request): Response
    {
        $format = $request->query->get('format', 'csv');
        $orders = $this->orderRepository->findAll();

        try {
            $exportData = $this->exportService->export($orders, $format);

            $this->logger->info('Orders exported', [
                'format' => $format,
                'count' => count($orders),
            ]);

            $response = new Response($exportData);
            $response->headers->set('Content-Type', $this->exportService->getMimeType($format));
            $response->headers->set(
                'Content-Disposition',
                sprintf('attachment; filename="orders_%s.%s"', date('Y-m-d'), $format)
            );

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Export failed', [
                'format' => $format,
                'error' => $e->getMessage(),
            ]);

            $this->addFlash('error', 'Export failed. Please try again.');
            return $this->redirectToRoute('admin_order_index');
        }
    }
}
```

**Key Features:**
- Constructor-based dependency injection
- Interface-based dependencies for flexibility
- Named logger injection for specific channels
- Parameter injection using `#[Autowire]`
- Cache integration for performance
- Comprehensive logging
- Type-safe dependencies with readonly properties

---

## Best Practices

1. **Use Constructor Injection**: Inject dependencies via constructor for better testability
2. **Readonly Properties**: Use `readonly` for injected dependencies in PHP 8.2+
3. **Type Declarations**: Always declare parameter and return types
4. **HTTP Method Constraints**: Specify allowed methods in route attributes
5. **Security Attributes**: Use `#[IsGranted]` for authorization checks
6. **Flash Messages**: Provide user feedback for all actions
7. **CSRF Protection**: Always validate CSRF tokens on state-changing operations
8. **Error Handling**: Use try-catch blocks and log errors appropriately
9. **Validation**: Validate input before processing
10. **Single Responsibility**: Keep controllers thin, delegate business logic to services

## Related Documentation

- [Symfony Controllers](https://symfony.com/doc/current/controller.html)
- [Routing with Attributes](https://symfony.com/doc/current/routing.html#creating-routes-as-attributes)
- [Security](https://symfony.com/doc/current/security.html)
- [Validation](https://symfony.com/doc/current/validation.html)
