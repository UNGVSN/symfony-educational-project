# Symfony Doctrine Integration

## Overview and Purpose

Doctrine is the default ORM (Object-Relational Mapping) library for Symfony applications. It provides a powerful database abstraction layer and enables developers to work with databases using object-oriented PHP code instead of SQL. The Doctrine integration in Symfony includes:

- **ORM** - Object-Relational Mapping for entities
- **DBAL** - Database Abstraction Layer
- **Migrations** - Version control for database schema
- **Fixtures** - Load test data into databases
- **Query Builder** - Fluent interface for building queries
- **Repository Pattern** - Organize database queries

## Key Classes and Interfaces

### Core Classes

- `Doctrine\ORM\EntityManagerInterface` - Central access point for ORM functionality
- `Doctrine\ORM\EntityRepository` - Repository for querying entities
- `Doctrine\ORM\QueryBuilder` - Build complex queries
- `Doctrine\DBAL\Connection` - Direct database connection
- `Doctrine\Persistence\ManagerRegistry` - Access to entity managers

### Key Interfaces

- `Doctrine\ORM\EntityManagerInterface` - Entity manager operations
- `Doctrine\Persistence\ObjectRepository` - Repository interface
- `Doctrine\Common\EventSubscriber` - Listen to Doctrine events

## Common Use Cases

### 1. Entity Definition with Attributes

```php
<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
#[ORM\Index(name: 'published_idx', columns: ['published_at'])]
#[ORM\HasLifecycleCallbacks]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $content = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isPublished = false;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'posts')]
    private ?Category $category = null;

    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post', orphanRemoval: true)]
    private Collection $comments;

    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts')]
    #[ORM\JoinTable(name: 'post_tags')]
    private Collection $tags;

    public function __construct()
    {
        $this->comments = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTimeImmutable();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function isPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): static
    {
        $this->isPublished = $isPublished;
        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;
        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(Comment $comment): static
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setPost($this);
        }
        return $this;
    }

    public function removeComment(Comment $comment): static
    {
        if ($this->comments->removeElement($comment)) {
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }
        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }
        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        $this->tags->removeElement($tag);
        return $this;
    }
}
```

### 2. Custom Repository with Query Builder

```php
<?php

namespace App\Repository;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function findPublished(): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.publishedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByAuthor(User $author): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.author = :author')
            ->setParameter('author', $author)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySearchQuery(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.title LIKE :query OR p.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->getQuery()
            ->getResult();
    }

    public function findWithComments(int $id): ?Post
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->addSelect('c')
            ->andWhere('p.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecentWithAuthorAndCategory(int $limit = 10): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.author', 'a')
            ->join('p.category', 'c')
            ->addSelect('a', 'c')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countByCategory(int $categoryId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.category = :categoryId')
            ->andWhere('p.isPublished = :published')
            ->setParameter('categoryId', $categoryId)
            ->setParameter('published', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByTagNames(array $tagNames): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.tags', 't')
            ->andWhere('t.name IN (:tags)')
            ->setParameter('tags', $tagNames)
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->groupBy('p.id')
            ->having('COUNT(DISTINCT t.id) = :tagCount')
            ->setParameter('tagCount', count($tagNames))
            ->getQuery()
            ->getResult();
    }

    public function findPopularPosts(int $limit = 5): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.comments', 'c')
            ->addSelect('COUNT(c.id) as HIDDEN commentCount')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->groupBy('p.id')
            ->orderBy('commentCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
```

### 3. Entity Manager Operations

```php
<?php

namespace App\Service;

use App\Entity\Post;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class PostService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function createPost(User $author, string $title, string $content): Post
    {
        $post = new Post();
        $post->setTitle($title);
        $post->setSlug($this->generateSlug($title));
        $post->setContent($content);
        $post->setAuthor($author);

        $this->entityManager->persist($post);
        $this->entityManager->flush();

        return $post;
    }

    public function updatePost(Post $post, array $data): void
    {
        if (isset($data['title'])) {
            $post->setTitle($data['title']);
            $post->setSlug($this->generateSlug($data['title']));
        }

        if (isset($data['content'])) {
            $post->setContent($data['content']);
        }

        // No need to call persist for existing entities
        $this->entityManager->flush();
    }

    public function publishPost(Post $post): void
    {
        $post->setIsPublished(true);
        $post->setPublishedAt(new \DateTimeImmutable());

        $this->entityManager->flush();
    }

    public function deletePost(Post $post): void
    {
        $this->entityManager->remove($post);
        $this->entityManager->flush();
    }

    public function bulkPublish(array $postIds): void
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->update(Post::class, 'p')
            ->set('p.isPublished', ':published')
            ->set('p.publishedAt', ':publishedAt')
            ->where($qb->expr()->in('p.id', ':ids'))
            ->setParameter('published', true)
            ->setParameter('publishedAt', new \DateTimeImmutable())
            ->setParameter('ids', $postIds)
            ->getQuery()
            ->execute();
    }

    private function generateSlug(string $title): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
}
```

### 4. DQL (Doctrine Query Language)

```php
<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\ORM\EntityManagerInterface;

class PostQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    public function findPostsByDQL(string $authorEmail): array
    {
        $dql = 'SELECT p, a, c
                FROM App\Entity\Post p
                JOIN p.author a
                LEFT JOIN p.comments c
                WHERE a.email = :email
                AND p.isPublished = :published
                ORDER BY p.publishedAt DESC';

        return $this->entityManager
            ->createQuery($dql)
            ->setParameter('email', $authorEmail)
            ->setParameter('published', true)
            ->getResult();
    }

    public function getStatisticsByDQL(): array
    {
        $dql = 'SELECT
                    c.name as category,
                    COUNT(p.id) as postCount,
                    AVG(SIZE(p.comments)) as avgComments
                FROM App\Entity\Post p
                JOIN p.category c
                WHERE p.isPublished = :published
                GROUP BY c.id
                HAVING COUNT(p.id) > 5
                ORDER BY postCount DESC';

        return $this->entityManager
            ->createQuery($dql)
            ->setParameter('published', true)
            ->getResult();
    }
}
```

### 5. Native SQL Queries

```php
<?php

namespace App\Repository;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

class AdvancedQueryService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Connection $connection
    ) {
    }

    public function executeNativeQuery(): array
    {
        $sql = '
            SELECT p.*, u.email as author_email, COUNT(c.id) as comment_count
            FROM posts p
            INNER JOIN users u ON p.author_id = u.id
            LEFT JOIN comments c ON c.post_id = p.id
            WHERE p.is_published = :published
            GROUP BY p.id
            ORDER BY comment_count DESC
            LIMIT :limit
        ';

        $rsm = new \Doctrine\ORM\Query\ResultSetMapping();
        $rsm->addEntityResult(\App\Entity\Post::class, 'p');
        $rsm->addFieldResult('p', 'id', 'id');
        $rsm->addFieldResult('p', 'title', 'title');
        $rsm->addScalarResult('author_email', 'authorEmail');
        $rsm->addScalarResult('comment_count', 'commentCount');

        return $this->entityManager
            ->createNativeQuery($sql, $rsm)
            ->setParameter('published', true)
            ->setParameter('limit', 10)
            ->getResult();
    }

    public function executeDBALQuery(): array
    {
        return $this->connection->fetchAllAssociative(
            'SELECT * FROM posts WHERE is_published = :published ORDER BY created_at DESC',
            ['published' => true]
        );
    }

    public function executeComplexDBAL(): int
    {
        return $this->connection->executeStatement(
            'UPDATE posts SET view_count = view_count + 1 WHERE id = :id',
            ['id' => 123]
        );
    }
}
```

### 6. Doctrine Lifecycle Callbacks

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();

        if ($this->slug === null && $this->title !== null) {
            $this->slug = $this->generateSlug($this->title);
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PostLoad]
    public function onPostLoad(): void
    {
        // Called after entity is loaded from database
    }

    #[ORM\PreRemove]
    public function onPreRemove(): void
    {
        // Called before entity is removed
    }

    private function generateSlug(string $title): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
    }
}
```

### 7. Event Subscribers and Listeners

```php
<?php

namespace App\EventListener;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::prePersist, entity: Post::class)]
#[AsEntityListener(event: Events::postPersist, entity: Post::class)]
class PostListener
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function prePersist(Post $post, PrePersistEventArgs $args): void
    {
        // Execute before inserting post into database
        $this->logger->info('About to persist post', ['title' => $post->getTitle()]);
    }

    public function postPersist(Post $post, PostPersistEventArgs $args): void
    {
        // Execute after post has been inserted
        $this->logger->info('Post persisted', ['id' => $post->getId()]);
    }
}

// Event Subscriber approach
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class TimestampSubscriber
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (method_exists($entity, 'setCreatedAt')) {
            $entity->setCreatedAt(new \DateTimeImmutable());
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if (method_exists($entity, 'setUpdatedAt')) {
            $entity->setUpdatedAt(new \DateTimeImmutable());
        }
    }
}
```

### 8. Migrations

```php
<?php

// Create migration: php bin/console make:migration
// Execute migrations: php bin/console doctrine:migrations:migrate

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250101120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create posts table with indexes and foreign keys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE posts (
            id INT AUTO_INCREMENT NOT NULL,
            author_id INT NOT NULL,
            category_id INT DEFAULT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            content LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            is_published TINYINT(1) NOT NULL,
            INDEX IDX_posts_author (author_id),
            INDEX IDX_posts_category (category_id),
            INDEX published_idx (published_at),
            UNIQUE INDEX UNIQ_posts_slug (slug),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE posts
            ADD CONSTRAINT FK_posts_author FOREIGN KEY (author_id)
            REFERENCES users (id)');

        $this->addSql('ALTER TABLE posts
            ADD CONSTRAINT FK_posts_category FOREIGN KEY (category_id)
            REFERENCES categories (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_posts_author');
        $this->addSql('ALTER TABLE posts DROP FOREIGN KEY FK_posts_category');
        $this->addSql('DROP TABLE posts');
    }
}
```

### 9. Data Fixtures

```php
<?php

namespace App\DataFixtures;

use App\Entity\Post;
use App\Entity\User;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class PostFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $user = $this->getReference(UserFixtures::ADMIN_USER_REFERENCE);
        $category = $this->getReference(CategoryFixtures::TECH_CATEGORY_REFERENCE);

        for ($i = 1; $i <= 20; $i++) {
            $post = new Post();
            $post->setTitle('Post Title ' . $i);
            $post->setSlug('post-title-' . $i);
            $post->setContent('This is the content of post ' . $i);
            $post->setAuthor($user);
            $post->setCategory($category);

            if ($i % 2 === 0) {
                $post->setIsPublished(true);
                $post->setPublishedAt(new \DateTimeImmutable('-' . $i . ' days'));
            }

            $manager->persist($post);

            // Create reference for other fixtures
            $this->addReference('post_' . $i, $post);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }
}

// Load fixtures: php bin/console doctrine:fixtures:load
```

### 10. Pagination

```php
<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PostRepository extends ServiceEntityRepository
{
    public function findPaginated(int $page = 1, int $limit = 10): Paginator
    {
        $query = $this->createQueryBuilder('p')
            ->andWhere('p.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('p.publishedAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        return new Paginator($query);
    }
}

// Controller usage
use Doctrine\ORM\Tools\Pagination\Paginator;

public function index(PostRepository $repository, int $page = 1): Response
{
    $paginator = $repository->findPaginated($page);

    return $this->render('post/index.html.twig', [
        'posts' => $paginator,
        'totalItems' => count($paginator),
        'currentPage' => $page,
        'itemsPerPage' => 10,
    ]);
}
```

### 11. Embeddable Objects

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
class Address
{
    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column(length: 100)]
    private ?string $city = null;

    #[ORM\Column(length: 20)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 100)]
    private ?string $country = null;

    // Getters and setters...
}

#[ORM\Entity]
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Embedded(class: Address::class)]
    private Address $address;

    public function __construct()
    {
        $this->address = new Address();
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
```

### 12. Doctrine Configuration

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
        profiling_collect_backtrace: '%kernel.debug%'

        # Connection options
        options:
            1002: 'SET sql_mode=(SELECT REPLACE(@@sql_mode, "ONLY_FULL_GROUP_BY", ""))'

        # Server version
        server_version: '8.0'

        # Charset
        charset: utf8mb4
        default_table_options:
            charset: utf8mb4
            collate: utf8mb4_unicode_ci

    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true

        mappings:
            App:
                type: attribute
                is_bundle: false
                dir: '%kernel.project_dir%/src/Entity'
                prefix: 'App\Entity'
                alias: App

        # Query hints
        dql:
            string_functions:
                MATCH: App\Doctrine\MatchAgainst

        # Second level cache
        second_level_cache:
            enabled: true
            region_cache_driver:
                type: pool
                pool: doctrine.system_cache_pool

when@prod:
    doctrine:
        orm:
            auto_generate_proxy_classes: false
            proxy_dir: '%kernel.build_dir%/doctrine/orm/Proxies'
            query_cache_driver:
                type: pool
                pool: doctrine.query_cache_pool
            result_cache_driver:
                type: pool
                pool: doctrine.result_cache_pool

framework:
    cache:
        pools:
            doctrine.result_cache_pool:
                adapter: cache.app
            doctrine.system_cache_pool:
                adapter: cache.system
            doctrine.query_cache_pool:
                adapter: cache.app
```

## Links to Official Documentation

- [Doctrine ORM](https://symfony.com/doc/current/doctrine.html)
- [Databases and Doctrine](https://symfony.com/doc/current/the-fast-track/en/10-doctrine.html)
- [Doctrine Configuration Reference](https://symfony.com/doc/current/reference/configuration/doctrine.html)
- [Doctrine Migrations](https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html)
- [Doctrine Fixtures](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html)
- [Association Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/association-mapping.html)
- [Query Builder](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/query-builder.html)
- [DQL Reference](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/dql-doctrine-query-language.html)
- [Events and Listeners](https://symfony.com/doc/current/doctrine/events.html)
- [Performance Optimization](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/improving-performance.html)
