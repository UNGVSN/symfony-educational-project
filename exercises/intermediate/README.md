# Intermediate Exercises

Practice exercises for developers with Symfony experience.

---

## Prerequisites

- Completed beginner exercises
- Understanding of Symfony fundamentals
- Experience with Doctrine ORM
- Basic testing knowledge

---

## Exercise List

### Exercise 1: REST API Development

**Objective:** Build a complete REST API for a blog.

**Tasks:**
1. Create entities: Post, Comment, Author
2. Implement API endpoints:
   - `GET /api/posts` - List with pagination
   - `GET /api/posts/{id}` - Single post with comments
   - `POST /api/posts` - Create (authenticated)
   - `PUT /api/posts/{id}` - Update (author only)
   - `DELETE /api/posts/{id}` - Delete (admin only)
3. Add JWT authentication
4. Implement request validation
5. Add rate limiting

**Git Workflow:**
```bash
git checkout -b exercise/intermediate-01-rest-api
git commit -m "feat(api): add Post entity and repository"
git commit -m "feat(api): implement CRUD endpoints"
git commit -m "feat(api): add JWT authentication"
git commit -m "test(api): add functional tests"
```

---

### Exercise 2: Event-Driven Architecture

**Objective:** Implement event-driven features.

**Tasks:**
1. Create custom events (UserRegistered, OrderPlaced)
2. Implement event subscribers
3. Send welcome email on registration
4. Create audit log on entity changes
5. Dispatch events from services

---

### Exercise 3: Service Architecture

**Objective:** Design a proper service layer.

**Tasks:**
1. Create service interfaces
2. Implement multiple payment gateways
3. Use service tags for extensibility
4. Create a compiler pass
5. Implement the decorator pattern

---

### Exercise 4: Advanced Forms

**Objective:** Master complex form scenarios.

**Tasks:**
1. Create multi-step wizard form
2. Implement dynamic form fields
3. Add custom form type
4. Create data transformer
5. Build form theme

---

### Exercise 5: Caching Strategy

**Objective:** Implement comprehensive caching.

**Tasks:**
1. Configure HTTP caching headers
2. Implement application cache
3. Use cache tags for invalidation
4. Add Redis cache adapter
5. Profile and optimize

---

### Exercise 6: Testing Suite

**Objective:** Build a comprehensive test suite.

**Tasks:**
1. Unit tests for services
2. Functional tests for controllers
3. Integration tests for repositories
4. Mock external services
5. Achieve 80% code coverage

---

## Project: Task Management System

Build a complete task management application:

### Features
- User authentication and authorization
- Projects with team members
- Tasks with status workflow
- Comments and attachments
- Email notifications
- API for mobile app

### Requirements
- Use Symfony best practices
- Implement proper security
- Write comprehensive tests
- Document API endpoints
- Deploy to production

### Git Structure
```
feature/user-authentication
feature/project-management
feature/task-workflow
feature/file-attachments
feature/notifications
feature/api
```

---

## Assessment Criteria

| Criteria | Points |
|----------|--------|
| Architecture | 25% |
| Functionality | 25% |
| Testing | 20% |
| Performance | 15% |
| Documentation | 15% |

---

## Next Steps

After completing intermediate exercises:
- [Advanced Exercises](../advanced/README.md)
- [Certification Preparation](../../topics/README.md)
