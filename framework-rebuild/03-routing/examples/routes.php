<?php

/**
 * Example route configuration file
 *
 * This file demonstrates how to define routes in a separate configuration file.
 * Usage: $router = Router::fromFile('examples/routes.php');
 */

return [
    'home' => [
        'path' => '/',
        'defaults' => [
            '_controller' => 'HomeController::index',
        ],
    ],

    'about' => [
        'path' => '/about',
        'defaults' => [
            '_controller' => 'HomeController::about',
        ],
    ],

    'contact' => [
        'path' => '/contact',
        'defaults' => [
            '_controller' => 'HomeController::contact',
        ],
        'methods' => ['GET', 'POST'],
    ],

    // Blog routes
    'blog_list' => [
        'path' => '/blog/{page}',
        'defaults' => [
            '_controller' => 'BlogController::list',
            'page' => 1,
        ],
        'requirements' => [
            'page' => '\d+',
        ],
    ],

    'blog_post' => [
        'path' => '/blog/{year}/{month}/{slug}',
        'defaults' => [
            '_controller' => 'BlogController::show',
        ],
        'requirements' => [
            'year' => '\d{4}',
            'month' => '\d{2}',
            'slug' => '[a-z0-9-]+',
        ],
    ],

    'blog_category' => [
        'path' => '/blog/category/{category}',
        'defaults' => [
            '_controller' => 'BlogController::category',
        ],
        'requirements' => [
            'category' => '[a-z-]+',
        ],
    ],

    // Article CRUD
    'article_list' => [
        'path' => '/articles',
        'defaults' => [
            '_controller' => 'ArticleController::list',
        ],
        'methods' => ['GET'],
    ],

    'article_show' => [
        'path' => '/articles/{id}',
        'defaults' => [
            '_controller' => 'ArticleController::show',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['GET'],
    ],

    'article_create_form' => [
        'path' => '/articles/new',
        'defaults' => [
            '_controller' => 'ArticleController::createForm',
        ],
        'methods' => ['GET'],
    ],

    'article_create' => [
        'path' => '/articles',
        'defaults' => [
            '_controller' => 'ArticleController::create',
        ],
        'methods' => ['POST'],
    ],

    'article_edit_form' => [
        'path' => '/articles/{id}/edit',
        'defaults' => [
            '_controller' => 'ArticleController::editForm',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['GET'],
    ],

    'article_update' => [
        'path' => '/articles/{id}',
        'defaults' => [
            '_controller' => 'ArticleController::update',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['PUT', 'PATCH'],
    ],

    'article_delete' => [
        'path' => '/articles/{id}',
        'defaults' => [
            '_controller' => 'ArticleController::delete',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['DELETE'],
    ],

    // API routes
    'api_users_list' => [
        'path' => '/api/users',
        'defaults' => [
            '_controller' => 'Api\UserController::list',
            '_format' => 'json',
        ],
        'methods' => ['GET'],
    ],

    'api_users_create' => [
        'path' => '/api/users',
        'defaults' => [
            '_controller' => 'Api\UserController::create',
            '_format' => 'json',
        ],
        'methods' => ['POST'],
    ],

    'api_users_show' => [
        'path' => '/api/users/{id}',
        'defaults' => [
            '_controller' => 'Api\UserController::show',
            '_format' => 'json',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['GET'],
    ],

    'api_users_update' => [
        'path' => '/api/users/{id}',
        'defaults' => [
            '_controller' => 'Api\UserController::update',
            '_format' => 'json',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['PUT', 'PATCH'],
    ],

    'api_users_delete' => [
        'path' => '/api/users/{id}',
        'defaults' => [
            '_controller' => 'Api\UserController::delete',
            '_format' => 'json',
        ],
        'requirements' => [
            'id' => '\d+',
        ],
        'methods' => ['DELETE'],
    ],

    // Nested resource: User posts
    'api_user_posts' => [
        'path' => '/api/users/{userId}/posts',
        'defaults' => [
            '_controller' => 'Api\PostController::list',
            '_format' => 'json',
        ],
        'requirements' => [
            'userId' => '\d+',
        ],
        'methods' => ['GET'],
    ],

    'api_user_post_show' => [
        'path' => '/api/users/{userId}/posts/{postId}',
        'defaults' => [
            '_controller' => 'Api\PostController::show',
            '_format' => 'json',
        ],
        'requirements' => [
            'userId' => '\d+',
            'postId' => '\d+',
        ],
        'methods' => ['GET'],
    ],

    // Search
    'search' => [
        'path' => '/search',
        'defaults' => [
            '_controller' => 'SearchController::search',
        ],
        'methods' => ['GET'],
    ],

    // User authentication (example)
    'login' => [
        'path' => '/login',
        'defaults' => [
            '_controller' => 'SecurityController::login',
        ],
        'methods' => ['GET', 'POST'],
    ],

    'logout' => [
        'path' => '/logout',
        'defaults' => [
            '_controller' => 'SecurityController::logout',
        ],
        'methods' => ['GET'],
    ],

    'register' => [
        'path' => '/register',
        'defaults' => [
            '_controller' => 'SecurityController::register',
        ],
        'methods' => ['GET', 'POST'],
    ],

    // Profile
    'profile' => [
        'path' => '/profile',
        'defaults' => [
            '_controller' => 'ProfileController::show',
        ],
        'methods' => ['GET'],
    ],

    'profile_edit' => [
        'path' => '/profile/edit',
        'defaults' => [
            '_controller' => 'ProfileController::edit',
        ],
        'methods' => ['GET', 'POST'],
    ],
];
