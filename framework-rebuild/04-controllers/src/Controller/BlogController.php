<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BlogController demonstrates route parameter handling.
 *
 * This controller shows:
 * - Route parameters automatically mapped to method arguments
 * - Type casting (int, string) for route parameters
 * - Combining Request injection with route parameters
 * - JSON responses for API endpoints
 * - Optional parameters with default values
 */
class BlogController extends AbstractController
{
    /**
     * In-memory blog posts for demonstration.
     */
    private array $posts = [
        1 => ['id' => 1, 'title' => 'First Post', 'author' => 'John Doe', 'content' => 'This is the first blog post.'],
        2 => ['id' => 2, 'title' => 'Second Post', 'author' => 'Jane Smith', 'content' => 'Another interesting post.'],
        3 => ['id' => 3, 'title' => 'Third Post', 'author' => 'Bob Johnson', 'content' => 'More content here.'],
        42 => ['id' => 42, 'title' => 'The Answer', 'author' => 'Douglas Adams', 'content' => 'Life, universe, and everything.'],
    ];

    /**
     * List all blog posts.
     *
     * Route: /blog
     * Method: GET
     *
     * @return Response
     */
    public function index(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Blog Posts</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .post {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .post h2 { margin-top: 0; color: #007bff; }
        .post .meta { color: #666; font-size: 0.9em; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Blog Posts</h1>
    <p><a href="/">← Back to Home</a></p>
HTML;

        foreach ($this->posts as $post) {
            $html .= sprintf(
                '<div class="post"><h2><a href="/blog/%d">%s</a></h2><div class="meta">By %s</div><p>%s</p></div>',
                $post['id'],
                htmlspecialchars($post['title']),
                htmlspecialchars($post['author']),
                htmlspecialchars($post['content'])
            );
        }

        $html .= '</body></html>';

        return $this->html($html);
    }

    /**
     * Show a single blog post.
     *
     * Route: /blog/{id}
     * Method: GET
     *
     * Demonstrates:
     * - Route parameter {id} automatically injected
     * - Type casting to int
     *
     * @param int $id The blog post ID (from route parameter)
     * @return Response
     */
    public function show(int $id): Response
    {
        if (!isset($this->posts[$id])) {
            return $this->notFound('Blog post not found');
        }

        $post = $this->posts[$id];

        $html = sprintf(
            <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>%s</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        .meta { color: #666; margin: 10px 0; }
        .content { line-height: 1.6; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>%s</h1>
    <div class="meta">By %s</div>
    <div class="content">%s</div>
    <p><a href="/blog">← Back to Blog</a> | <a href="/">Home</a></p>
</body>
</html>
HTML,
            htmlspecialchars($post['title']),
            htmlspecialchars($post['title']),
            htmlspecialchars($post['author']),
            nl2br(htmlspecialchars($post['content']))
        );

        return $this->html($html);
    }

    /**
     * Edit a blog post.
     *
     * Route: /blog/{id}/edit
     * Method: GET, POST
     *
     * Demonstrates:
     * - Combining Request injection with route parameters
     * - Request comes first, then route parameters
     * - Handling different HTTP methods
     *
     * @param Request $request The HTTP request
     * @param int $id The blog post ID
     * @return Response
     */
    public function edit(Request $request, int $id): Response
    {
        if (!isset($this->posts[$id])) {
            return $this->notFound('Blog post not found');
        }

        $post = $this->posts[$id];

        if ($request->isMethod('POST')) {
            // In a real app, you'd update the database here
            $title = $request->request->get('title');
            $content = $request->request->get('content');

            return $this->json([
                'status' => 'success',
                'message' => "Post #{$id} would be updated",
                'data' => [
                    'id' => $id,
                    'title' => $title,
                    'content' => $content,
                ],
            ]);
        }

        $html = sprintf(
            <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit: %s</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        form { display: flex; flex-direction: column; gap: 15px; }
        input, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        button { padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Edit Post #%d</h1>
    <form method="POST">
        <input type="text" name="title" value="%s" required>
        <textarea name="content" rows="10" required>%s</textarea>
        <button type="submit">Save Changes</button>
    </form>
    <p><a href="/blog/%d">← Back to Post</a> | <a href="/blog">Blog List</a></p>
</body>
</html>
HTML,
            htmlspecialchars($post['title']),
            $id,
            htmlspecialchars($post['title']),
            htmlspecialchars($post['content']),
            $id
        );

        return $this->html($html);
    }

    /**
     * Delete a blog post.
     *
     * Route: /blog/{id}/delete
     * Method: POST
     *
     * @param int $id The blog post ID
     * @return Response
     */
    public function delete(int $id): Response
    {
        if (!isset($this->posts[$id])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Blog post not found',
            ], 404);
        }

        // In a real app, you'd delete from database
        return $this->json([
            'status' => 'success',
            'message' => "Post #{$id} would be deleted",
        ]);
    }

    /**
     * API endpoint: List all posts as JSON.
     *
     * Route: /api/posts
     * Method: GET
     *
     * Demonstrates:
     * - JSON response for API
     * - Optional query parameters
     *
     * @param Request $request For accessing query parameters
     * @return Response
     */
    public function apiList(Request $request): Response
    {
        $limit = $request->query->get('limit', 10);
        $posts = array_slice($this->posts, 0, (int)$limit, true);

        return $this->json([
            'total' => count($this->posts),
            'limit' => $limit,
            'posts' => array_values($posts),
        ]);
    }

    /**
     * API endpoint: Get a single post as JSON.
     *
     * Route: /api/post/{id}
     * Method: GET
     *
     * @param int $id The blog post ID
     * @return Response
     */
    public function apiShow(int $id): Response
    {
        if (!isset($this->posts[$id])) {
            return $this->json([
                'status' => 'error',
                'message' => 'Post not found',
            ], 404);
        }

        return $this->json([
            'status' => 'success',
            'data' => $this->posts[$id],
        ]);
    }

    /**
     * Search blog posts.
     *
     * Route: /blog/search
     * Method: GET
     *
     * Demonstrates:
     * - Optional parameters with default values
     * - Query string parameter handling
     *
     * @param Request $request For accessing query parameters
     * @param string $q Search query (optional)
     * @return Response
     */
    public function search(Request $request, string $q = ''): Response
    {
        $query = $request->query->get('q', $q);
        $results = [];

        if ($query) {
            foreach ($this->posts as $post) {
                if (
                    stripos($post['title'], $query) !== false ||
                    stripos($post['content'], $query) !== false
                ) {
                    $results[] = $post;
                }
            }
        }

        return $this->json([
            'query' => $query,
            'count' => count($results),
            'results' => $results,
        ]);
    }
}
