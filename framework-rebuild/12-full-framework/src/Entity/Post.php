<?php

namespace App\Entity;

/**
 * Post entity
 *
 * Represents a blog post.
 * In a real application, this would use Doctrine ORM.
 */
class Post
{
    private int $id;
    private string $title;
    private string $slug;
    private string $content;
    private \DateTimeInterface $createdAt;
    private string $author;

    public function __construct(
        int $id,
        string $title,
        string $slug,
        string $content,
        \DateTimeInterface $createdAt,
        string $author = 'Anonymous'
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->slug = $slug;
        $this->content = $content;
        $this->createdAt = $createdAt;
        $this->author = $author;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getExcerpt(int $length = 200): string
    {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }

        return substr($this->content, 0, $length) . '...';
    }
}
