<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * HomeController demonstrates different controller patterns.
 *
 * This controller shows:
 * - Standard controller methods returning Response objects
 * - Using the AbstractController helper methods
 * - Request parameter injection
 * - Rendering simple HTML responses
 */
class HomeController extends AbstractController
{
    /**
     * Home page - demonstrates simple Response creation.
     *
     * Route: /
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Framework Rebuild</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .nav { margin: 20px 0; }
        .nav a {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .nav a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to Framework Rebuild - Chapter 04</h1>
        <p>This is a demonstration of controller resolution and argument handling.</p>

        <div class="nav">
            <h2>Try these examples:</h2>
            <a href="/">Home</a>
            <a href="/about">About</a>
            <a href="/blog">Blog List</a>
            <a href="/blog/1">Blog Post #1</a>
            <a href="/blog/42/edit">Edit Post #42</a>
            <a href="/api/posts">API Endpoint</a>
            <a href="/api/post/5">API Post #5</a>
        </div>

        <div>
            <h3>Controller Features Demonstrated:</h3>
            <ul>
                <li>Controller resolution (closures, class methods, invokable)</li>
                <li>Argument resolution (route parameters, Request injection)</li>
                <li>Response helpers (json, html, redirect)</li>
                <li>Type casting for route parameters</li>
            </ul>
        </div>
    </div>
</body>
</html>
HTML;

        return $this->html($html);
    }

    /**
     * About page - demonstrates HTML helper method.
     *
     * Route: /about
     * Method: GET
     *
     * @return Response
     */
    public function about(): Response
    {
        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>About</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
    </style>
</head>
<body>
    <h1>About This Framework</h1>
    <p>This is Chapter 04: Controllers - demonstrating controller resolution and argument injection.</p>
    <p><a href="/">← Back to Home</a></p>
</body>
</html>
HTML;

        return $this->html($html);
    }

    /**
     * Contact form - demonstrates Request injection.
     *
     * Route: /contact
     * Method: GET, POST
     *
     * @param Request $request The HTTP request (automatically injected)
     * @return Response
     */
    public function contact(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $name = $request->request->get('name');
            $email = $request->request->get('email');
            $message = $request->request->get('message');

            // In a real app, you'd process the form here
            $response = [
                'status' => 'success',
                'message' => 'Form submitted successfully!',
                'data' => [
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                ],
            ];

            return $this->json($response);
        }

        $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Contact</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
        }
        form { display: flex; flex-direction: column; gap: 15px; }
        input, textarea { padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { padding: 10px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Contact Us</h1>
    <form method="POST">
        <input type="text" name="name" placeholder="Your Name" required>
        <input type="email" name="email" placeholder="Your Email" required>
        <textarea name="message" rows="5" placeholder="Your Message" required></textarea>
        <button type="submit">Send Message</button>
    </form>
    <p><a href="/">← Back to Home</a></p>
</body>
</html>
HTML;

        return $this->html($html);
    }

    /**
     * Redirect example - demonstrates redirect helper.
     *
     * Route: /old-page
     * Method: GET
     *
     * @return Response
     */
    public function oldPage(): Response
    {
        return $this->redirect('/', 301);
    }

    /**
     * API health check - demonstrates JSON response.
     *
     * Route: /api/health
     * Method: GET
     *
     * @return Response
     */
    public function apiHealth(): Response
    {
        return $this->json([
            'status' => 'ok',
            'timestamp' => time(),
            'version' => '1.0.0',
        ]);
    }
}
