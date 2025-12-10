# Build Your Own Symfony

This project guides you through recreating the Symfony framework from scratch, piece by piece. By building your own framework, you'll gain a deep understanding of how Symfony works internally and become a better developer.

## Overview

Symfony is one of the most popular PHP frameworks, powering millions of websites and applications. But have you ever wondered how it actually works under the hood? This educational project takes you on a journey to rebuild Symfony's core components from scratch.

You'll start with the basics (handling HTTP requests and responses) and progressively build up to a full-featured framework with routing, dependency injection, templating, and more.

## Learning Objectives

By completing this project, you will understand:

- **HTTP Foundation**: How request and response objects abstract PHP's superglobals
- **Front Controller Pattern**: Why modern frameworks use a single entry point
- **Routing**: How URLs are matched to controllers
- **Controller Resolution**: How the framework determines which code to execute
- **HTTP Kernel**: The heart of Symfony - how it processes requests into responses
- **Event Dispatcher**: How event-driven architecture enables extensibility
- **Dependency Injection**: How services are managed and injected throughout the application
- **Templating**: How views are rendered and separated from business logic
- **Form Handling**: How complex form processing is abstracted
- **Security**: How authentication and authorization work
- **Console Component**: How CLI applications are structured
- **Framework Integration**: How all components work together

## Prerequisites

Before starting this project, you should have:

- **PHP 8.2 or higher** installed on your system
- **Composer** for dependency management
- **Solid understanding of Object-Oriented Programming** (classes, interfaces, inheritance, etc.)
- **Basic knowledge of HTTP** (requests, responses, headers, status codes)
- **Familiarity with design patterns** (helpful but not required)
- **Text editor or IDE** (PHPStorm, VS Code, etc.)

## Project Structure

```
framework-rebuild/
├── README.md                    # This file
├── 01-http-foundation/          # Request/Response objects
├── 02-front-controller/         # Single entry point
├── 03-routing/                  # URL matching
├── 04-controllers/              # Controller resolution
├── 05-http-kernel/              # The heart of Symfony
├── 06-event-dispatcher/         # Event system
├── 07-dependency-injection/     # Service container
├── 08-templating/               # View layer
├── 09-forms/                    # Form handling
├── 10-security/                 # Authentication/Authorization
├── 11-console/                  # CLI application
├── 12-full-framework/           # Putting it all together
└── src/                         # The rebuilt framework code
    └── Framework/               # Our custom framework namespace
        ├── Http/                # HTTP components
        ├── Routing/             # Routing components
        ├── DependencyInjection/ # DI container
        ├── EventDispatcher/     # Event system
        └── ...                  # Other components
```

## How to Use This Guide

This is an **incremental learning project**. Each chapter builds upon the previous one:

1. **Start with Chapter 01** and work through each chapter in order
2. **Read the chapter's README** to understand what you'll build
3. **Follow the instructions** to implement the functionality
4. **Test your code** to ensure it works correctly
5. **Move to the next chapter** once you understand the current one

Each chapter contains:
- A detailed explanation of the component
- Step-by-step implementation instructions
- Code examples and explanations
- Exercises to reinforce learning
- Tests to validate your implementation

### Tips for Success

- Don't rush - take time to understand each concept
- Experiment with the code - break it, fix it, modify it
- Compare your implementation with Symfony's actual source code
- Write tests for your components
- Refactor as you learn better approaches

## Chapters Overview

### 01. HTTP Foundation
Build request and response objects to abstract PHP's superglobals and provide an object-oriented interface to HTTP.

### 02. Front Controller
Create a single entry point for all requests, replacing the traditional "one PHP file per page" approach.

### 03. Routing
Implement a URL routing system that maps URLs to specific handlers.

### 04. Controllers
Add controller resolution to execute the appropriate code for each route.

### 05. HTTP Kernel
Build the kernel - the central component that orchestrates the entire request/response cycle.

### 06. Event Dispatcher
Implement an event system that allows different parts of the application to communicate.

### 07. Dependency Injection
Create a service container to manage object creation and dependencies.

### 08. Templating
Add a templating engine to separate presentation logic from business logic.

### 09. Forms
Build a form handling system for processing user input.

### 10. Security
Implement authentication and authorization systems.

### 11. Console
Create a CLI application framework for running commands.

### 12. Full Framework
Integrate all components into a complete, working framework.

## Inspiration and References

This project is inspired by:

- **Fabien Potencier's "Create Your Own Framework"** series - A seminal work that demonstrates building a framework on top of Symfony components
- **The Symfony Framework** itself - We'll study how real-world components are designed
- **Educational best practices** - Learning by building is one of the most effective ways to truly understand a system

### Recommended Reading

- [Create your own PHP Framework](https://symfony.com/doc/current/create_framework/index.html) by Fabien Potencier
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [PHP: The Right Way](https://phptherightway.com/)
- Design Patterns: Elements of Reusable Object-Oriented Software (Gang of Four)

## Philosophy

This project follows these principles:

1. **Simplicity First**: Start with the simplest implementation, then add complexity
2. **Learn by Doing**: Writing code is more valuable than reading about it
3. **Understand the Why**: Focus on understanding why things are designed a certain way
4. **Compare with Reality**: See how your implementation compares to Symfony's
5. **Iterate and Improve**: Refactor as you learn better patterns

## Getting Started

Ready to begin? Head over to **[01-http-foundation](./01-http-foundation/)** to start building your framework!

## License

This educational project is provided for learning purposes. Symfony is a registered trademark of Fabien Potencier.

---

**Note**: This is a learning exercise. For production applications, always use the official Symfony framework or other well-tested frameworks. The goal here is education, not to replace existing tools.
