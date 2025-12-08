# Symfony Fast Track Exercises

Hands-on exercises following Symfony's official "The Fast Track" book to build a complete Guestbook application.

---

## Overview

This section contains progressive exercises that guide you through building a fully-featured web application with Symfony. Each chapter builds upon the previous one, teaching you real-world development skills.

## Project: Guestbook Application

Throughout these exercises, you'll build a conference guestbook application with:
- User comments and feedback
- Admin moderation panel
- Email notifications
- API endpoints
- Asynchronous processing
- Caching and performance optimization
- Multi-language support
- Production deployment

---

## Chapter Overview

### Getting Started (Chapters 1-5)

| Chapter | Topic | Skills Learned |
|---------|-------|----------------|
| 01 | Environment Setup | PHP, Composer, Symfony CLI |
| 02 | Project Introduction | Project planning, requirements |
| 03 | Production Deployment | Platform.sh, Docker, deployment |
| 04 | Development Methodology | Git workflow, branching strategy |
| 05 | Debugging | Profiler, error handling, logging |

### Core Development (Chapters 6-11)

| Chapter | Topic | Skills Learned |
|---------|-------|----------------|
| 06 | Controllers | Routes, controllers, responses |
| 07 | Database Setup | Doctrine, PostgreSQL, configuration |
| 08 | Entity Design | ORM mapping, relationships |
| 09 | Admin Backend | EasyAdmin, CRUD operations |
| 10 | UI with Twig | Templates, inheritance, assets |
| 11 | Git Branching | Feature branches, code review |

### Feature Development (Chapters 12-20)

| Chapter | Topic | Skills Learned |
|---------|-------|----------------|
| 12 | Events | Event listeners, subscribers |
| 13 | Doctrine Lifecycle | Callbacks, entity events |
| 14 | Forms | Form types, validation, handling |
| 15 | Security | Authentication, authorization |
| 16 | API Integration | HTTP client, spam detection |
| 17 | Testing | PHPUnit, functional tests |
| 18 | Async Processing | Messenger, queues |
| 19 | Workflows | State machines, transitions |
| 20 | Email Notifications | Mailer, templates |

### Production Features (Chapters 21-28)

| Chapter | Topic | Skills Learned |
|---------|-------|----------------|
| 21 | Caching | HTTP cache, application cache |
| 22 | Frontend Build | Webpack Encore, Stimulus |
| 23 | Image Processing | LiipImagine, thumbnails |
| 24 | Scheduled Tasks | Cron, scheduler component |
| 25 | Notifications | Multi-channel notifications |
| 26 | REST API | API Platform, serialization |
| 27 | SPA Frontend | JavaScript, fetch API |
| 28 | Internationalization | Translations, localization |

### Advanced Topics (Chapters 29-32)

| Chapter | Topic | Skills Learned |
|---------|-------|----------------|
| 29 | Performance | Profiling, optimization |
| 30 | Symfony Internals | Kernel, bundles, container |
| 31 | Redis Sessions | Session storage, caching |
| 32 | Message Queues | RabbitMQ, async processing |

---

## Exercise Structure

Each chapter directory contains:

```
{chapter}/
├── README.md              # Learning objectives and instructions
├── CONCEPTS.md            # Theory and explanations
├── steps/
│   ├── step-01.md         # Step-by-step instructions
│   ├── step-02.md
│   └── ...
├── starter/               # Starting code (if applicable)
├── solution/              # Reference implementation
└── QUESTIONS.md           # Knowledge check questions
```

---

## Git Workflow for Exercises

### Starting a Chapter

```bash
# Create chapter branch
git checkout main
git checkout -b chapter-06-controllers

# Work through steps
git add .
git commit -m "feat(chapter-06): complete step 01 - basic controller"
```

### Completing a Chapter

```bash
# Merge to main when done
git checkout main
git merge chapter-06-controllers

# Tag completion
git tag -a chapter-06-complete -m "Completed Chapter 06: Controllers"

# Continue to next chapter
git checkout -b chapter-07-database
```

---

## Prerequisites

Before starting, ensure you have:

- [ ] PHP 8.2+ installed
- [ ] Composer 2.x installed
- [ ] Symfony CLI installed
- [ ] PostgreSQL 15+ or Docker
- [ ] Git configured
- [ ] Code editor (VS Code or PhpStorm)

Run the setup verification:

```bash
symfony check:requirements
```

---

## Getting Started

1. Start with [Chapter 01: Environment Setup](./01-environment/README.md)
2. Create the project following the instructions
3. Commit your progress after each step
4. Complete the questions at the end of each chapter
5. Compare with the solution if stuck

---

## Tips for Success

### Take Your Time
Each chapter introduces new concepts. Make sure you understand before moving on.

### Type the Code
Don't copy-paste. Typing helps you learn and catch errors.

### Read Error Messages
Symfony provides helpful error messages. Read them carefully.

### Use the Profiler
The web debug toolbar and profiler are your best debugging tools.

### Commit Often
Make small, focused commits as you complete each step.

### Ask Questions
If stuck, check the Symfony documentation or community forums.

---

## Resources

- [The Fast Track Book (Official)](https://symfony.com/doc/6.4/the-fast-track/en/index.html)
- [Symfony Documentation](https://symfony.com/doc/current/index.html)
- [SymfonyCasts](https://symfonycasts.com/)
- [Symfony Slack](https://symfony.com/slack)

---

*Happy coding! Build something amazing with Symfony.*
