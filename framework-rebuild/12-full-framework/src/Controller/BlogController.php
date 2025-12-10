<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use App\Repository\PostRepository;

/**
 * Blog Controller
 *
 * Handles blog-related pages.
 */
class BlogController
{
    public function __construct(
        private Environment $twig,
        private PostRepository $repository
    ) {}

    /**
     * Blog index - list all posts.
     */
    public function index(Request $request): Response
    {
        $posts = $this->repository->findAll();

        $html = $this->twig->render('blog/index.html.twig', [
            'posts' => $posts,
        ]);

        return new Response($html);
    }

    /**
     * Show a single blog post.
     */
    public function show(int $id, Request $request): Response
    {
        $post = $this->repository->find($id);

        if (!$post) {
            return new Response('<h1>404 - Post Not Found</h1>', 404);
        }

        $html = $this->twig->render('blog/show.html.twig', [
            'post' => $post,
        ]);

        return new Response($html);
    }
}
