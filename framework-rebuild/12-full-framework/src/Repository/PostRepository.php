<?php

namespace App\Repository;

use App\Entity\Post;

/**
 * Post Repository
 *
 * In a real application, this would use Doctrine ORM to query the database.
 * For this demo, we use in-memory data.
 */
class PostRepository
{
    private array $posts;

    public function __construct()
    {
        // Initialize with sample data
        $this->posts = [
            1 => new Post(
                1,
                'Understanding Symfony HttpKernel',
                'understanding-symfony-httpkernel',
                'The HttpKernel component is the heart of Symfony. It handles the HTTP request/response cycle through a series of events, allowing for great flexibility and extensibility. In this post, we explore how it works and why it\'s so powerful.',
                new \DateTime('2024-01-15'),
                'John Doe'
            ),
            2 => new Post(
                2,
                'Dependency Injection Explained',
                'dependency-injection-explained',
                'Dependency Injection is a design pattern that allows us to write more maintainable and testable code. Instead of creating dependencies inside our classes, we inject them from the outside. This simple concept has profound implications for application architecture.',
                new \DateTime('2024-01-20'),
                'Jane Smith'
            ),
            3 => new Post(
                3,
                'Building Your Own Framework',
                'building-your-own-framework',
                'Building a framework from scratch is one of the best ways to understand how modern web frameworks work. In this series, we\'ve recreated many of Symfony\'s core components, learning about HTTP, routing, dependency injection, and more along the way.',
                new \DateTime('2024-01-25'),
                'Framework Team'
            ),
        ];
    }

    /**
     * Find a post by ID.
     */
    public function find(int $id): ?Post
    {
        return $this->posts[$id] ?? null;
    }

    /**
     * Find a post by slug.
     */
    public function findBySlug(string $slug): ?Post
    {
        foreach ($this->posts as $post) {
            if ($post->getSlug() === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Find all posts.
     *
     * @return Post[]
     */
    public function findAll(): array
    {
        return array_values($this->posts);
    }

    /**
     * Add a post (for demo purposes).
     */
    public function add(Post $post): void
    {
        $this->posts[$post->getId()] = $post;
    }
}
