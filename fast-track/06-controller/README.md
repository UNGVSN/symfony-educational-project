# Chapter 06: Creating Controllers

Learn to create Symfony controllers that handle HTTP requests and generate responses.

---

## Learning Objectives

By the end of this chapter, you will:
- Create controllers using the maker bundle
- Define routes with attributes
- Handle request parameters
- Generate different response types
- Render Twig templates

---

## Concepts

### What is a Controller?

A controller is a PHP callable (usually a class method) that:
1. Receives an HTTP Request
2. Processes business logic
3. Returns an HTTP Response

```
Browser Request → Routing → Controller → Response → Browser
```

### The Request-Response Cycle

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

public function index(Request $request): Response
{
    // Process request
    $name = $request->query->get('name', 'World');

    // Return response
    return new Response("Hello, $name!");
}
```

---

## Step 1: Generate a Controller

Use the maker bundle to generate a controller:

```bash
php bin/console make:controller ConferenceController
```

This creates two files:
- `src/Controller/ConferenceController.php`
- `templates/conference/index.html.twig`

---

## Step 2: Examine the Generated Controller

```php
// src/Controller/ConferenceController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConferenceController extends AbstractController
{
    #[Route('/conference', name: 'app_conference')]
    public function index(): Response
    {
        return $this->render('conference/index.html.twig', [
            'controller_name' => 'ConferenceController',
        ]);
    }
}
```

---

## Step 3: Create the Homepage

Modify the controller to create a homepage:

```php
// src/Controller/ConferenceController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConferenceController extends AbstractController
{
    #[Route('/', name: 'homepage')]
    public function index(): Response
    {
        return $this->render('conference/index.html.twig', [
            'conferences' => [],
        ]);
    }
}
```

Update the template:

```twig
{# templates/conference/index.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Conference Guestbook{% endblock %}

{% block body %}
<h1>Give your feedback!</h1>

{% if conferences %}
    <h2>Conferences</h2>
    <ul>
        {% for conference in conferences %}
            <li>{{ conference }}</li>
        {% endfor %}
    </ul>
{% else %}
    <p>No conferences scheduled yet.</p>
{% endif %}
{% endblock %}
```

---

## Step 4: Add Route with Parameter

Create a route that accepts a conference slug:

```php
#[Route('/conference/{slug}', name: 'conference')]
public function show(string $slug): Response
{
    return $this->render('conference/show.html.twig', [
        'conference' => $slug,
    ]);
}
```

Create the template:

```twig
{# templates/conference/show.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}{{ conference }} Conference{% endblock %}

{% block body %}
<h1>{{ conference }} Conference</h1>

<p>Comments will appear here...</p>

<a href="{{ path('homepage') }}">← Back to conferences</a>
{% endblock %}
```

---

## Step 5: Test Your Routes

### Using the Browser

1. Start the server: `symfony server:start`
2. Visit: `https://127.0.0.1:8000/`
3. Visit: `https://127.0.0.1:8000/conference/paris-2024`

### Using the Console

```bash
# List all routes
php bin/console debug:router

# Match a specific URL
php bin/console router:match /conference/paris-2024
```

---

## Step 6: Handle Different Response Types

### JSON Response

```php
#[Route('/api/conferences', name: 'api_conferences')]
public function apiIndex(): Response
{
    $conferences = [
        ['city' => 'Paris', 'year' => 2024],
        ['city' => 'Berlin', 'year' => 2024],
    ];

    return $this->json($conferences);
}
```

### Redirect Response

```php
#[Route('/old-conference', name: 'old_conference')]
public function oldConference(): Response
{
    // Redirect to homepage
    return $this->redirectToRoute('homepage');

    // Redirect with parameters
    // return $this->redirectToRoute('conference', ['slug' => 'paris-2024']);

    // Redirect to URL
    // return $this->redirect('https://example.com');
}
```

---

## Step 7: Access Request Data

```php
use Symfony\Component\HttpFoundation\Request;

#[Route('/search', name: 'search')]
public function search(Request $request): Response
{
    // Query parameters (?q=symfony)
    $query = $request->query->get('q', '');

    // Check request method
    if ($request->isMethod('POST')) {
        // Handle POST data
        $data = $request->request->get('data');
    }

    return $this->render('search/results.html.twig', [
        'query' => $query,
    ]);
}
```

---

## Git Workflow

```bash
# Create feature branch
git checkout -b chapter-06-controllers

# Stage changes
git add src/Controller/ConferenceController.php
git commit -m "feat(controller): add ConferenceController with homepage"

git add templates/conference/
git commit -m "feat(templates): add conference templates"

# Complete the chapter
git add .
git commit -m "feat(chapter-06): complete controller chapter"

# Merge to develop
git checkout develop
git merge chapter-06-controllers
```

---

## Exercise: Create Additional Endpoints

1. Create a route `/about` that displays an about page
2. Create an API endpoint `/api/conference/{slug}` that returns conference details as JSON
3. Create a route with optional parameter `/conferences/{year?}` where year defaults to current year

### Solution

```php
// About page
#[Route('/about', name: 'about')]
public function about(): Response
{
    return $this->render('conference/about.html.twig');
}

// API endpoint
#[Route('/api/conference/{slug}', name: 'api_conference')]
public function apiShow(string $slug): Response
{
    return $this->json([
        'slug' => $slug,
        'city' => ucfirst(explode('-', $slug)[0]),
        'year' => (int) explode('-', $slug)[1],
    ]);
}

// Optional parameter
#[Route('/conferences/{year}', name: 'conferences_by_year', defaults: ['year' => null])]
public function byYear(?int $year): Response
{
    $year = $year ?? (int) date('Y');

    return $this->render('conference/by_year.html.twig', [
        'year' => $year,
    ]);
}
```

---

## Questions

1. What is the purpose of extending `AbstractController`?
2. How do you define a route using PHP attributes?
3. What is the difference between `$this->json()` and `$this->render()`?
4. How do you access query parameters from the URL?
5. What happens when you redirect to a route that doesn't exist?

### Answers

1. `AbstractController` provides helper methods like `render()`, `json()`, `redirectToRoute()`, and access to services.

2. Use the `#[Route()]` attribute above a controller method:
   ```php
   #[Route('/path', name: 'route_name')]
   ```

3. `$this->json()` returns a `JsonResponse` with JSON-encoded data. `$this->render()` renders a Twig template and returns an HTML response.

4. Use `$request->query->get('param_name')` or `$request->query->get('param_name', 'default')`.

5. Symfony throws a `RouteNotFoundException` exception, resulting in a 500 error in production.

---

## Next Step

Proceed to [Chapter 07: Setting up a Database](../07-database/README.md) to configure Doctrine ORM.
