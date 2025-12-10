<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Home Controller
 *
 * Handles the home page of the application.
 */
class HomeController
{
    public function __construct(
        private Environment $twig
    ) {}

    /**
     * Home page action.
     */
    public function index(Request $request): Response
    {
        $html = $this->twig->render('home/index.html.twig', [
            'title' => 'Welcome to Our Framework',
            'message' => 'This is a complete framework built from scratch!',
        ]);

        return new Response($html);
    }
}
