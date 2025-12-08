# Beginner Exercises

Practice exercises for developers new to Symfony.

---

## Prerequisites

- Completed environment setup
- Basic PHP knowledge
- Understanding of HTTP concepts

---

## Exercise List

### Exercise 1: Hello World Controller

**Objective:** Create your first Symfony controller.

**Tasks:**
1. Create a new Symfony project
2. Generate a controller using `make:controller`
3. Create a route that returns "Hello, World!"
4. Add a route parameter for the name
5. Display the name in a Twig template

**Git Workflow:**
```bash
git checkout -b exercise/beginner-01-hello-world
# Complete tasks...
git add . && git commit -m "feat: complete hello world exercise"
git checkout main && git merge exercise/beginner-01-hello-world
```

---

### Exercise 2: Blog Entity

**Objective:** Create a Doctrine entity with relationships.

**Tasks:**
1. Create a `Post` entity with title, content, createdAt
2. Create a `Category` entity with name
3. Create a ManyToOne relationship (Post → Category)
4. Generate and run migrations
5. Create fixtures for sample data

---

### Exercise 3: Simple CRUD

**Objective:** Build a complete CRUD interface.

**Tasks:**
1. Create `Product` entity (name, price, description)
2. Create controller with index, show, new, edit, delete actions
3. Create Twig templates for each view
4. Add form validation
5. Implement flash messages

---

### Exercise 4: Contact Form

**Objective:** Create a working contact form with email.

**Tasks:**
1. Create a `ContactType` form class
2. Validate email and message fields
3. Send email using Symfony Mailer
4. Display success message
5. Implement CSRF protection

---

### Exercise 5: User Authentication

**Objective:** Set up basic authentication.

**Tasks:**
1. Create `User` entity implementing `UserInterface`
2. Configure security.yaml
3. Create registration form
4. Create login page
5. Protect routes with `IsGranted`

---

## Assessment Criteria

Each exercise is evaluated on:

| Criteria | Points |
|----------|--------|
| Functionality | 40% |
| Code Quality | 20% |
| Git Workflow | 20% |
| Documentation | 10% |
| Tests | 10% |

---

## Tips

1. **Read error messages** - Symfony provides helpful error information
2. **Use the profiler** - Debug toolbar shows valuable data
3. **Commit often** - Small commits make debugging easier
4. **Check documentation** - symfony.com has comprehensive guides

---

## Next Steps

After completing beginner exercises, proceed to:
- [Intermediate Exercises](../intermediate/README.md)
- [Fast Track Chapters](../../fast-track/README.md)
