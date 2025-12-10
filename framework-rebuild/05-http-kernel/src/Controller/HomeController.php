<?php

namespace App\Controller;

use Framework\HttpFoundation\Request;
use Framework\HttpFoundation\Response;
use Framework\HttpFoundation\JsonResponse;

/**
 * HomeController - Example controller
 */
class HomeController
{
    /**
     * Home page.
     */
    public function index(Request $request): Response
    {
        return new Response('<h1>Welcome to the HTTP Kernel!</h1>');
    }

    /**
     * About page.
     */
    public function about(): Response
    {
        $content = <<<HTML
        <h1>About the HTTP Kernel</h1>
        <p>The HTTP Kernel is the heart of the framework.</p>
        <p>It transforms Requests into Responses.</p>
        HTML;

        return new Response($content);
    }

    /**
     * Product detail page with route parameters.
     */
    public function product(Request $request, int $id): Response
    {
        return new Response(
            sprintf('<h1>Product #%d</h1><p>Details for product %d</p>', $id, $id)
        );
    }

    /**
     * API endpoint that returns JSON.
     *
     * Notice: This returns an array, not a Response!
     * The kernel.view event will convert it to JsonResponse.
     */
    public function apiProducts(): array
    {
        return [
            'products' => [
                ['id' => 1, 'name' => 'Product 1', 'price' => 19.99],
                ['id' => 2, 'name' => 'Product 2', 'price' => 29.99],
                ['id' => 3, 'name' => 'Product 3', 'price' => 39.99],
            ],
            'total' => 3,
        ];
    }

    /**
     * Error example - throws exception.
     */
    public function error(): Response
    {
        throw new \RuntimeException('This is a test exception!');
    }

    /**
     * Sub-request example - renders fragments.
     */
    public function dashboard(Request $request): Response
    {
        // In a real app, you'd use HttpKernel to make sub-requests
        // For now, just show a simple response
        $content = <<<HTML
        <h1>Dashboard</h1>
        <div class="widget">User Widget</div>
        <div class="widget">Stats Widget</div>
        HTML;

        return new Response($content);
    }
}
