# Front Controller Architecture

This document provides visual diagrams and architectural explanations of the Front Controller pattern.

## Traditional Multi-File Architecture (The Old Way)

```
┌─────────────────────────────────────────────────────┐
│                    Web Server                        │
└─────────────────────────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┬───────────┐
         │               │               │           │
         ▼               ▼               ▼           ▼
    ┌────────┐      ┌─────────┐    ┌──────────┐  ┌─────────┐
    │index.php│      │about.php│    │products/ │  │contact  │
    │         │      │         │    │ list.php │  │.php     │
    │session_ │      │session_ │    │session_  │  │session_ │
    │start()  │      │start()  │    │start()   │  │start()  │
    │require..│      │require..│    │require.. │  │require..│
    │db.php   │      │db.php   │    │db.php    │  │db.php   │
    └────────┘      └─────────┘    └──────────┘  └─────────┘

Problems:
• Code duplication in every file
• Inconsistent initialization
• Ugly URLs: /products.php?id=123
• Hard to add global features
• No central error handling
```

## Front Controller Architecture (Modern Way)

```
┌─────────────────────────────────────────────────────┐
│                 Web Server (Apache/Nginx)            │
│                                                       │
│  All requests → URL Rewriting → public/index.php    │
└─────────────────────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │  public/index.php    │
              │  (Front Controller)  │
              │                      │
              │  • Load autoloader   │
              │  • Create Request    │
              │  • Create Framework  │
              │  • Handle request    │
              │  • Send response     │
              └──────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │   Framework Class    │
              │                      │
              │   • Route matching   │
              │   • Dispatch action  │
              │   • Return Response  │
              └──────────────────────┘
                         │
         ┌───────────────┼───────────────┬───────────┐
         │               │               │           │
         ▼               ▼               ▼           ▼
    ┌─────────┐    ┌──────────┐   ┌──────────┐  ┌─────────┐
    │ home    │    │ about    │   │ products │  │ contact │
    │ Action()│    │ Action() │   │ Action() │  │ Action()│
    └─────────┘    └──────────┘   └──────────┘  └─────────┘

Benefits:
✓ Single entry point
✓ No code duplication
✓ Clean URLs: /products/123
✓ Easy to add middleware
✓ Central error handling
```

## Request Flow Diagram

```
User Types URL: http://example.com/products/42
                        │
                        ▼
┌───────────────────────────────────────────────────────┐
│                 Step 1: Web Server                     │
│                                                        │
│  Apache/Nginx receives request                        │
│  URL: /products/42                                     │
└───────────────────────────────────────────────────────┘
                        │
                        ▼
┌───────────────────────────────────────────────────────┐
│              Step 2: URL Rewriting                     │
│                                                        │
│  Check: Is /products/42 a real file?     → No         │
│  Check: Is /products/42 a directory?     → No         │
│  Action: Rewrite to /index.php                        │
│          (Preserve REQUEST_URI = /products/42)        │
└───────────────────────────────────────────────────────┘
                        │
                        ▼
┌───────────────────────────────────────────────────────┐
│         Step 3: Front Controller (index.php)          │
│                                                        │
│  1. require vendor/autoload.php                       │
│  2. $request = Request::createFromGlobals()           │
│     → method = 'GET'                                  │
│     → uri = '/products/42'                            │
│  3. $framework = new Framework()                      │
│  4. $response = $framework->handle($request)          │
│  5. $response->send()                                 │
└───────────────────────────────────────────────────────┘
                        │
                        ▼
┌───────────────────────────────────────────────────────┐
│          Step 4: Framework Routing                     │
│                                                        │
│  URI: /products/42                                     │
│  Match pattern: /products/(\d+)                       │
│  Extract: $id = '42'                                  │
│  Call: productDetailAction($request, '42')            │
└───────────────────────────────────────────────────────┘
                        │
                        ▼
┌───────────────────────────────────────────────────────┐
│         Step 5: Action Method Execution                │
│                                                        │
│  private function productDetailAction($request, $id)  │
│  {                                                     │
│      // Fetch product from database                   │
│      // Render HTML                                   │
│      return new Response($html, 200);                 │
│  }                                                     │
└───────────────────────────────────────────────────────┘
                        │
                        ▼
┌───────────────────────────────────────────────────────┐
│            Step 6: Response Sent                       │
│                                                        │
│  $response->send() does:                              │
│  1. http_response_code(200)                           │
│  2. header('Content-Type: text/html; charset=UTF-8')  │
│  3. echo $content                                     │
└───────────────────────────────────────────────────────┘
                        │
                        ▼
                  User sees page!
```

## Class Diagram

```
┌─────────────────────────────────────┐
│           Request                    │
├─────────────────────────────────────┤
│ - method: string                    │
│ - uri: string                       │
│ - query: array                      │
│ - request: array                    │
│ - server: array                     │
├─────────────────────────────────────┤
│ + createFromGlobals(): Request      │
│ + getMethod(): string               │
│ + getUri(): string                  │
│ + getQuery(?string): mixed          │
│ + getRequest(?string): mixed        │
└─────────────────────────────────────┘
                 │
                 │ passed to
                 ▼
┌─────────────────────────────────────┐
│          Framework                   │
├─────────────────────────────────────┤
│                                      │
├─────────────────────────────────────┤
│ + handle(Request): Response         │
│ - homeAction(Request): Response     │
│ - aboutAction(Request): Response    │
│ - productAction(Request, id):       │
│   Response                          │
│ - notFoundAction(Request): Response │
└─────────────────────────────────────┘
                 │
                 │ returns
                 ▼
┌─────────────────────────────────────┐
│           Response                   │
├─────────────────────────────────────┤
│ - content: string                   │
│ - statusCode: int                   │
│ - headers: array                    │
├─────────────────────────────────────┤
│ + setContent(string): self          │
│ + setStatusCode(int): self          │
│ + setHeader(string, string): self   │
│ + send(): void                      │
│ + json(mixed, int): Response        │
│ + redirect(string, int): Response   │
└─────────────────────────────────────┘
```

## Sequence Diagram: Handling a Request

```
 User      WebServer   index.php   Request   Framework   Response
  │            │           │          │          │           │
  │  GET       │           │          │          │           │
  │ /products/ │           │          │          │           │
  │    42      │           │          │          │           │
  ├───────────>│           │          │          │           │
  │            │           │          │          │           │
  │            │  rewrite  │          │          │           │
  │            │    to     │          │          │           │
  │            │ index.php │          │          │           │
  │            ├──────────>│          │          │           │
  │            │           │          │          │           │
  │            │           │ create   │          │           │
  │            │           ├─────────>│          │           │
  │            │           │          │          │           │
  │            │           │ createFromGlobals() │           │
  │            │           │<─────────┤          │           │
  │            │           │          │          │           │
  │            │           │  new Framework()    │           │
  │            │           ├────────────────────>│           │
  │            │           │          │          │           │
  │            │           │   handle(request)   │           │
  │            │           ├────────────────────>│           │
  │            │           │          │          │           │
  │            │           │          │   route  │           │
  │            │           │          │  to      │           │
  │            │           │          │  action  │           │
  │            │           │          │          │           │
  │            │           │          │    create│           │
  │            │           │          │  Response│           │
  │            │           │          │<─────────┼──────────>│
  │            │           │          │  return  │           │
  │            │           │<─────────────────────┤           │
  │            │           │          │          │           │
  │            │           │  send()  │          │           │
  │            │           ├─────────────────────────────────>│
  │            │           │          │          │           │
  │            │           │          │          │    output │
  │            │<──────────┴──────────┴──────────┴───────────┤
  │            │           │          │          │           │
  │<───────────┤           │          │          │           │
  │            │           │          │          │           │
  │   Page     │           │          │          │           │
  │ Displayed  │           │          │          │           │
```

## URL Rewriting Flow

### Apache (.htaccess)

```
Request: http://example.com/products/42

                    ↓
┌────────────────────────────────────────┐
│  RewriteEngine On                      │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│  RewriteCond %{REQUEST_FILENAME} !-f   │
│  Is /products/42 a file? → NO          │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│  RewriteCond %{REQUEST_FILENAME} !-d   │
│  Is /products/42 a directory? → NO     │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│  RewriteRule ^(.*)$ index.php [QSA,L]  │
│  Rewrite to: index.php                 │
│  Keep: REQUEST_URI = /products/42      │
└────────────────────────────────────────┘
                    ↓
              Execute index.php
```

### Nginx (try_files)

```
Request: http://example.com/products/42

                    ↓
┌────────────────────────────────────────┐
│  location / {                          │
│    try_files $uri $uri/ /index.php...  │
│  }                                      │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│  Try: Serve /products/42 as file       │
│  Result: Not found                     │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│  Try: Serve /products/42/ as directory │
│  Result: Not found                     │
└────────────────────────────────────────┘
                    ↓
┌────────────────────────────────────────┐
│  Fallback: /index.php$is_args$args     │
│  Execute: index.php                    │
│  Keep: REQUEST_URI = /products/42      │
└────────────────────────────────────────┘
                    ↓
              Execute index.php
```

## Comparison: Before vs After

### Before: Multiple Files

```
File Structure              URLs
──────────────            ─────────────────────
index.php          →      /index.php
about.php          →      /about.php
products.php       →      /products.php?id=42
contact.php        →      /contact.php

Each file:
┌──────────────────┐
│ session_start()  │  ← Duplicated
│ require db.php   │  ← Duplicated
│ require auth.php │  ← Duplicated
│ // page logic    │
└──────────────────┘
```

### After: Front Controller

```
File Structure              URLs
──────────────            ─────────────────────
public/index.php   →      /
  └→ Framework     →      /about
    └→ Actions     →      /products/42
                   →      /contact

Single initialization:
┌────────────────────────┐
│ public/index.php       │
│   autoload             │  ← Once
│   Request::create()    │  ← Once
│   Framework->handle()  │  ← Once
│     └→ Route           │
│        └→ Action       │
└────────────────────────┘
```

## Evolution Summary

```
Phase 1: Multiple Files
━━━━━━━━━━━━━━━━━━━━━━━
index.php + about.php + products.php + ...
├─ Pros: Simple to start
└─ Cons: Duplicated code, ugly URLs, hard to maintain


Phase 2: Shared Bootstrap
━━━━━━━━━━━━━━━━━━━━━━━
bootstrap.php + index.php + about.php + ...
├─ Pros: Shared initialization
└─ Cons: Still multiple entry points, ugly URLs


Phase 3: Naive Front Controller
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
index.php with if/else routing
├─ Pros: Single entry point, clean URLs
└─ Cons: All code in one file


Phase 4: Front Controller + Functions
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
index.php + controllers.php (functions)
├─ Pros: Separated logic
└─ Cons: Procedural, global state


Phase 5: OOP Framework (Current)
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
index.php + Request + Response + Framework
├─ Pros: Testable, maintainable, extensible
└─ Cons: More complex (but worth it!)


Future: Router + DI + Middleware
━━━━━━━━━━━━━━━━━━━━━━━━━━━━
(Coming in next chapters)
```

## Security Benefits

```
Without Front Controller:
═══════════════════════════════════════════
project/
├── index.php          ← Accessible
├── about.php          ← Accessible
├── config.php         ← DANGEROUS! Accessible if PHP disabled
├── db.php             ← DANGEROUS! Contains credentials
└── .env               ← DANGEROUS! Environment variables

All files in web root = Security risk


With Front Controller:
═══════════════════════════════════════════
project/
├── public/            ← Only this is web-accessible
│   └── index.php      ← Entry point
├── src/               ← NOT accessible via HTTP ✓
│   ├── Framework.php
│   ├── Request.php
│   └── Response.php
├── config/            ← NOT accessible via HTTP ✓
│   └── config.php
└── .env               ← NOT accessible via HTTP ✓

Application code outside web root = Secure
```

## Performance Considerations

```
Execution Time Breakdown:
─────────────────────────

Traditional Multi-File Approach:
┌─────────────────────────────────────┐
│ PHP Startup           │ ████░░░░░░ │ 40%
│ Bootstrap/Init        │ ████░░░░░░ │ 40%  ← Per request
│ Business Logic        │ ██░░░░░░░░ │ 20%
└─────────────────────────────────────┘
Every file initializes separately


Front Controller Approach:
┌─────────────────────────────────────┐
│ PHP Startup           │ ████░░░░░░ │ 40%
│ Bootstrap/Init        │ ██░░░░░░░░ │ 20%  ← Once per request
│ Routing               │ █░░░░░░░░░ │ 10%
│ Business Logic        │ ███░░░░░░░ │ 30%
└─────────────────────────────────────┘
Single initialization, efficient routing

Note: With OPcache enabled, startup cost is minimal.
Front controller overhead is negligible (~1-2ms).
```

## Summary

The Front Controller pattern provides:

1. **Single Entry Point**: All requests through one file
2. **Clean URLs**: `/products/42` instead of `/products.php?id=42`
3. **Centralized Logic**: Initialization, error handling, middleware in one place
4. **Better Security**: Application code outside web root
5. **Easier Maintenance**: One place to update global behavior
6. **Testable**: Request/Response objects can be mocked
7. **Extensible**: Easy to add features like logging, authentication, etc.

This is the foundation of all modern PHP frameworks!
