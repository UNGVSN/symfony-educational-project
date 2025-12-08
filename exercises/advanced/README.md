# Advanced Exercises

Challenging exercises for experienced Symfony developers.

---

## Prerequisites

- Completed intermediate exercises
- Deep understanding of Symfony architecture
- Experience with async processing
- DevOps fundamentals

---

## Exercise List

### Exercise 1: Microservices Communication

**Objective:** Build a microservices architecture.

**Tasks:**
1. Create two separate Symfony applications
2. Implement service discovery
3. Use RabbitMQ for messaging
4. Implement circuit breaker pattern
5. Add distributed tracing

**Architecture:**
```
┌─────────────┐       ┌─────────────┐
│   Service A │◄─────►│  RabbitMQ   │
│  (Orders)   │       └──────┬──────┘
└─────────────┘              │
                             ▼
                      ┌─────────────┐
                      │   Service B │
                      │  (Payments) │
                      └─────────────┘
```

---

### Exercise 2: Custom Bundle Development

**Objective:** Create a reusable Symfony bundle.

**Tasks:**
1. Design bundle architecture
2. Create semantic configuration
3. Implement compiler passes
4. Add Flex recipe
5. Write documentation
6. Publish to Packagist

**Bundle Structure:**
```
AcmeNotificationBundle/
├── src/
│   ├── AcmeNotificationBundle.php
│   ├── DependencyInjection/
│   │   ├── AcmeNotificationExtension.php
│   │   └── Configuration.php
│   ├── Service/
│   ├── Event/
│   └── Resources/
│       └── config/
├── config/
│   └── services.yaml
├── tests/
└── README.md
```

---

### Exercise 3: Performance Optimization

**Objective:** Optimize a slow application.

**Tasks:**
1. Profile with Blackfire
2. Optimize Doctrine queries
3. Implement lazy loading
4. Add opcache preloading
5. Configure Varnish cache
6. Achieve sub-100ms response times

---

### Exercise 4: Real-time Features

**Objective:** Add real-time capabilities.

**Tasks:**
1. Set up Mercure hub
2. Implement live notifications
3. Build real-time chat
4. Add presence indicators
5. Handle connection failures

---

### Exercise 5: Multi-tenancy

**Objective:** Build a multi-tenant SaaS application.

**Tasks:**
1. Implement tenant identification
2. Configure database per tenant
3. Handle tenant isolation
4. Build tenant management
5. Implement billing integration

---

### Exercise 6: Custom Framework

**Objective:** Build a micro-framework using Symfony components.

**Tasks:**
1. Use HttpFoundation for request/response
2. Implement routing with Routing component
3. Add dependency injection container
4. Create event dispatcher
5. Build controller resolver

**Minimal Framework:**
```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();
$routes->add('hello', new Route('/hello/{name}', [
    '_controller' => function(string $name) {
        return new Response("Hello, $name!");
    }
]));

$framework = new Framework($routes);
$response = $framework->handle(Request::createFromGlobals());
$response->send();
```

---

## Capstone Project: E-Commerce Platform

Build a production-ready e-commerce platform:

### Core Features
- Product catalog with categories
- Shopping cart and checkout
- Payment processing (Stripe)
- Order management
- Inventory tracking
- Customer accounts

### Advanced Features
- Multi-vendor marketplace
- Recommendation engine
- Search with Elasticsearch
- Analytics dashboard
- Mobile API

### Technical Requirements
- Domain-driven design
- Event sourcing for orders
- CQRS pattern
- GraphQL API
- Kubernetes deployment

### Architecture
```
┌────────────────────────────────────────────────┐
│                   API Gateway                   │
└───────────────────┬────────────────────────────┘
                    │
        ┌───────────┼───────────┐
        ▼           ▼           ▼
┌───────────┐ ┌───────────┐ ┌───────────┐
│  Catalog  │ │   Order   │ │  Payment  │
│  Service  │ │  Service  │ │  Service  │
└─────┬─────┘ └─────┬─────┘ └─────┬─────┘
      │             │             │
      ▼             ▼             ▼
┌───────────┐ ┌───────────┐ ┌───────────┐
│PostgreSQL │ │ EventStore│ │  Stripe   │
└───────────┘ └───────────┘ └───────────┘
```

---

## Assessment Criteria

| Criteria | Points |
|----------|--------|
| Architecture Design | 30% |
| Code Quality | 20% |
| Testing | 20% |
| Performance | 15% |
| Documentation | 15% |

---

## Certification Readiness

After completing advanced exercises, you should be ready for:
- Symfony Certification Exam
- Senior Developer positions
- Technical architecture roles

---

## Resources

- [Symfony Internals](https://symfony.com/doc/current/components.html)
- [Design Patterns in PHP](https://designpatternsphp.readthedocs.io/)
- [Domain-Driven Design](https://martinfowler.com/tags/domain%20driven%20design.html)
- [Event Sourcing](https://martinfowler.com/eaaDev/EventSourcing.html)
