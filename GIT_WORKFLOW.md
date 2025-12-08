# Professional Git Workflow Guide

A comprehensive guide to professional Git practices for Symfony development and learning exercises.

---

## Table of Contents

1. [Workflow Overview](#1-workflow-overview)
2. [Branch Naming Conventions](#2-branch-naming-conventions)
3. [Commit Message Standards](#3-commit-message-standards)
4. [Learning Exercise Workflow](#4-learning-exercise-workflow)
5. [Feature Development Workflow](#5-feature-development-workflow)
6. [GitFlow for Teams](#6-gitflow-for-teams)
7. [Code Review Process](#7-code-review-process)
8. [Release Management](#8-release-management)
9. [Troubleshooting](#9-troubleshooting)
10. [Quick Reference](#10-quick-reference)

---

## 1. Workflow Overview

### Single Developer (Learning)

```
main ─────●─────●─────●─────●─────●─────●─────●
           \       /   \       /   \       /
            ●─────●     ●─────●     ●─────●
          exercise-01  exercise-02  exercise-03
```

### Team Development (GitFlow)

```
main     ─────●───────────────────●───────────────●
              │                   │               │
hotfix        │               ●───●               │
              │              /                    │
release       │         ●───●─────────────────●───●
              │        /                          │
develop ─────●───●───●───●───●───●───●───●───●───●
              \     /     \     /     \     /
feature        ●───●       ●───●       ●───●
```

---

## 2. Branch Naming Conventions

### Format

```
<type>/<ticket-id>-<short-description>
```

### Branch Types

| Type | Purpose | Example |
|------|---------|---------|
| `feature/` | New features | `feature/AUTH-123-user-login` |
| `bugfix/` | Bug fixes | `bugfix/API-456-fix-null-response` |
| `hotfix/` | Production fixes | `hotfix/PROD-789-security-patch` |
| `release/` | Release preparation | `release/v2.1.0` |
| `exercise/` | Learning exercises | `exercise/01-routing-basics` |
| `topic/` | Study topics | `topic/dependency-injection` |
| `experiment/` | Experimental work | `experiment/new-cache-strategy` |
| `refactor/` | Code refactoring | `refactor/service-layer` |
| `docs/` | Documentation | `docs/api-documentation` |
| `test/` | Test additions | `test/integration-tests` |

### Examples

```bash
# Learning exercises
git checkout -b exercise/01-hello-symfony
git checkout -b exercise/02-routing-parameters
git checkout -b topic/forms-validation

# Feature development
git checkout -b feature/USER-101-registration-form
git checkout -b feature/BLOG-202-comment-system

# Bug fixes
git checkout -b bugfix/API-303-pagination-error
git checkout -b hotfix/SEC-404-xss-vulnerability
```

---

## 3. Commit Message Standards

### Conventional Commits Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

| Type | Description |
|------|-------------|
| `feat` | New feature |
| `fix` | Bug fix |
| `docs` | Documentation changes |
| `style` | Code style (formatting, semicolons) |
| `refactor` | Code refactoring |
| `perf` | Performance improvement |
| `test` | Adding/updating tests |
| `build` | Build system changes |
| `ci` | CI/CD changes |
| `chore` | Maintenance tasks |
| `revert` | Reverting changes |

### Scopes (Symfony-specific)

```
controller, service, entity, repository, form,
validator, security, twig, console, config,
migration, fixture, test, api, event
```

### Examples

```bash
# Feature commits
git commit -m "feat(controller): add PostController with CRUD actions"
git commit -m "feat(security): implement JWT authentication"
git commit -m "feat(form): create UserRegistrationType with validation"

# Bug fix commits
git commit -m "fix(repository): resolve N+1 query in findAllWithComments"
git commit -m "fix(validator): correct email constraint pattern"

# Documentation
git commit -m "docs(readme): add installation instructions"
git commit -m "docs(api): document REST endpoints"

# Refactoring
git commit -m "refactor(service): extract email logic to MailerService"
git commit -m "refactor(entity): use PHP 8 constructor promotion"

# Tests
git commit -m "test(controller): add functional tests for BlogController"
git commit -m "test(service): add unit tests for Calculator"

# Multi-line commit message
git commit -m "feat(security): implement voter-based authorization

- Add PostVoter for edit/delete permissions
- Update security.yaml with voter configuration
- Add tests for voter logic

Closes #123"
```

### Commit Message Rules

1. **Subject line**: Max 50 characters, imperative mood
2. **Body**: Wrap at 72 characters, explain what and why
3. **Footer**: Reference issues, breaking changes

```bash
# Good
git commit -m "feat(controller): add user profile endpoint"

# Bad
git commit -m "Added new endpoint for user profiles"
git commit -m "feat(controller): Add user profile endpoint that returns JSON data with user information including name, email, and avatar"
```

---

## 4. Learning Exercise Workflow

### Starting an Exercise

```bash
# 1. Ensure main is up to date
git checkout main
git pull origin main

# 2. Create exercise branch
git checkout -b exercise/01-routing-basics

# 3. Make initial commit
git add .
git commit -m "chore: start exercise 01 - routing basics"
```

### Working Through Steps

```bash
# Step 1: Basic setup
# ... make changes ...
git add .
git commit -m "feat(routing): add basic route configuration"

# Step 2: Add parameters
# ... make changes ...
git add .
git commit -m "feat(routing): add route parameters with requirements"

# Step 3: URL generation
# ... make changes ...
git add .
git commit -m "feat(routing): implement URL generation in controller"

# Step 4: Complete exercise
git add .
git commit -m "feat(routing): complete routing exercise with tests"
```

### Completing an Exercise

```bash
# 1. Ensure all changes are committed
git status

# 2. Switch to main
git checkout main

# 3. Merge exercise branch
git merge exercise/01-routing-basics

# 4. Tag completion (optional)
git tag -a exercise-01-complete -m "Completed routing basics exercise"

# 5. Delete exercise branch (optional)
git branch -d exercise/01-routing-basics

# 6. Start next exercise
git checkout -b exercise/02-controllers
```

### Exercise Progress Tracking

```bash
# View completed exercises
git tag -l "exercise-*"

# View exercise history
git log --oneline --graph exercise/01-routing-basics

# Compare with solution
git diff main..solution/01-routing-basics
```

---

## 5. Feature Development Workflow

### Starting a Feature

```bash
# 1. Update develop branch
git checkout develop
git pull origin develop

# 2. Create feature branch
git checkout -b feature/USER-101-user-registration

# 3. Initial commit
git commit --allow-empty -m "chore: start user registration feature"
```

### Development Cycle

```bash
# Regular commits during development
git add src/Entity/User.php
git commit -m "feat(entity): create User entity with validation"

git add src/Form/RegistrationType.php
git commit -m "feat(form): create RegistrationType form"

git add src/Controller/RegistrationController.php
git commit -m "feat(controller): add registration controller"

git add templates/registration/
git commit -m "feat(twig): add registration templates"

git add tests/
git commit -m "test(registration): add functional tests"
```

### Keeping Up to Date

```bash
# Option 1: Rebase (cleaner history)
git fetch origin
git rebase origin/develop

# Option 2: Merge (preserves history)
git fetch origin
git merge origin/develop

# Resolve conflicts if any
git add .
git rebase --continue
# or
git commit -m "merge: resolve conflicts with develop"
```

### Completing a Feature

```bash
# 1. Final rebase
git fetch origin
git rebase origin/develop

# 2. Run tests
php bin/phpunit

# 3. Push feature branch
git push origin feature/USER-101-user-registration

# 4. Create Pull Request (via GitHub/GitLab)

# 5. After PR approval and merge, clean up
git checkout develop
git pull origin develop
git branch -d feature/USER-101-user-registration
```

---

## 6. GitFlow for Teams

### Branch Structure

| Branch | Purpose | Merges From | Merges To |
|--------|---------|-------------|-----------|
| `main` | Production code | `release`, `hotfix` | - |
| `develop` | Integration | `feature`, `release`, `hotfix` | `release` |
| `feature/*` | New features | `develop` | `develop` |
| `release/*` | Release prep | `develop` | `main`, `develop` |
| `hotfix/*` | Production fixes | `main` | `main`, `develop` |

### Setting Up GitFlow

```bash
# Initialize GitFlow
git flow init

# Or manually create develop
git checkout -b develop
git push -u origin develop
```

### Feature Workflow

```bash
# Start feature
git flow feature start user-authentication
# Or: git checkout -b feature/user-authentication develop

# Work on feature...
git commit -m "feat(security): add authentication"

# Finish feature
git flow feature finish user-authentication
# Or:
git checkout develop
git merge --no-ff feature/user-authentication
git branch -d feature/user-authentication
```

### Release Workflow

```bash
# Start release
git flow release start v1.2.0
# Or: git checkout -b release/v1.2.0 develop

# Bump version, update changelog
git commit -m "chore: bump version to 1.2.0"

# Finish release
git flow release finish v1.2.0
# Or:
git checkout main
git merge --no-ff release/v1.2.0
git tag -a v1.2.0 -m "Release version 1.2.0"
git checkout develop
git merge --no-ff release/v1.2.0
git branch -d release/v1.2.0
```

### Hotfix Workflow

```bash
# Start hotfix
git flow hotfix start security-patch
# Or: git checkout -b hotfix/security-patch main

# Fix the issue
git commit -m "fix(security): patch XSS vulnerability"

# Finish hotfix
git flow hotfix finish security-patch
# Or:
git checkout main
git merge --no-ff hotfix/security-patch
git tag -a v1.2.1 -m "Security patch"
git checkout develop
git merge --no-ff hotfix/security-patch
git branch -d hotfix/security-patch
```

---

## 7. Code Review Process

### Creating a Pull Request

```bash
# Push your branch
git push origin feature/my-feature

# Create PR with GitHub CLI
gh pr create --title "feat: add user authentication" \
  --body "## Summary
- Implemented JWT authentication
- Added login/logout endpoints
- Created user provider

## Testing
- [ ] Unit tests pass
- [ ] Functional tests pass
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Documentation updated
- [ ] No security vulnerabilities"
```

### PR Template

Create `.github/PULL_REQUEST_TEMPLATE.md`:

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix (non-breaking change fixing an issue)
- [ ] New feature (non-breaking change adding functionality)
- [ ] Breaking change (fix or feature causing existing functionality to change)
- [ ] Documentation update

## How Has This Been Tested?
Describe the tests you ran

## Checklist
- [ ] My code follows the project style guidelines
- [ ] I have performed a self-review
- [ ] I have commented complex code
- [ ] I have updated documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests proving my fix/feature works
- [ ] New and existing tests pass locally
- [ ] Any dependent changes have been merged

## Screenshots (if applicable)

## Related Issues
Closes #(issue number)
```

### Review Checklist

**Code Quality:**
- [ ] Code is readable and well-organized
- [ ] No code duplication
- [ ] Proper error handling
- [ ] No hardcoded values

**Symfony Best Practices:**
- [ ] Services are properly injected
- [ ] Controllers are thin
- [ ] Proper use of attributes
- [ ] Security considerations addressed

**Testing:**
- [ ] Unit tests for new code
- [ ] Functional tests for endpoints
- [ ] Edge cases covered

**Documentation:**
- [ ] PHPDoc comments where needed
- [ ] README updated if necessary
- [ ] API documentation current

---

## 8. Release Management

### Semantic Versioning

```
MAJOR.MINOR.PATCH

1.0.0 → 1.0.1 (patch: bug fixes)
1.0.1 → 1.1.0 (minor: new features, backward compatible)
1.1.0 → 2.0.0 (major: breaking changes)
```

### Creating a Release

```bash
# 1. Create release branch
git checkout -b release/v1.2.0 develop

# 2. Update version
# Edit composer.json, package.json, etc.
git commit -m "chore: bump version to 1.2.0"

# 3. Update CHANGELOG.md
git commit -m "docs: update changelog for v1.2.0"

# 4. Merge to main
git checkout main
git merge --no-ff release/v1.2.0

# 5. Tag the release
git tag -a v1.2.0 -m "Release version 1.2.0

## Features
- User authentication system
- API rate limiting

## Bug Fixes
- Fixed pagination issue
- Resolved cache invalidation

## Breaking Changes
- None"

# 6. Merge back to develop
git checkout develop
git merge --no-ff release/v1.2.0

# 7. Push everything
git push origin main develop --tags

# 8. Clean up
git branch -d release/v1.2.0
```

### CHANGELOG.md Format

```markdown
# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- New user profile page

### Changed
- Updated authentication flow

## [1.2.0] - 2024-01-15

### Added
- User registration system (#123)
- Email verification (#124)
- Password reset functionality (#125)

### Changed
- Improved form validation messages
- Updated dependencies

### Fixed
- Fixed N+1 query in post listing (#126)
- Resolved cache invalidation issue (#127)

### Security
- Updated symfony/security-bundle to patch CVE-XXXX

## [1.1.0] - 2024-01-01

### Added
- Blog post comments
- User avatars

[Unreleased]: https://github.com/user/repo/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/user/repo/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/user/repo/releases/tag/v1.1.0
```

---

## 9. Troubleshooting

### Undo Last Commit (Keep Changes)

```bash
git reset --soft HEAD~1
```

### Undo Last Commit (Discard Changes)

```bash
git reset --hard HEAD~1
```

### Fix Commit Message

```bash
# Last commit
git commit --amend -m "correct message"

# Older commit (interactive rebase)
git rebase -i HEAD~3
# Change 'pick' to 'reword' for the commit
```

### Recover Deleted Branch

```bash
# Find the commit
git reflog

# Recreate branch
git checkout -b recovered-branch abc1234
```

### Resolve Merge Conflicts

```bash
# During merge
git merge feature-branch
# CONFLICT!

# View conflicts
git status

# Edit conflicted files, then:
git add .
git commit -m "merge: resolve conflicts"

# Or abort merge
git merge --abort
```

### Squash Commits

```bash
# Squash last 3 commits
git rebase -i HEAD~3

# In editor, change 'pick' to 'squash' for commits to combine
pick abc1234 First commit
squash def5678 Second commit
squash ghi9012 Third commit
```

### Cherry-Pick Commits

```bash
# Apply specific commit to current branch
git cherry-pick abc1234

# Cherry-pick without committing
git cherry-pick --no-commit abc1234
```

### Stash Changes

```bash
# Save changes temporarily
git stash

# Save with message
git stash save "work in progress on feature X"

# List stashes
git stash list

# Apply most recent stash
git stash pop

# Apply specific stash
git stash apply stash@{2}

# Delete stash
git stash drop stash@{0}
```

---

## 10. Quick Reference

### Daily Commands

```bash
# Start day
git checkout develop
git pull origin develop

# Create feature
git checkout -b feature/my-feature

# Save progress
git add .
git commit -m "feat: description"

# Push work
git push origin feature/my-feature

# End day (if incomplete)
git stash save "WIP: feature description"
```

### Branch Commands

```bash
# List branches
git branch -a

# Delete local branch
git branch -d branch-name

# Delete remote branch
git push origin --delete branch-name

# Rename branch
git branch -m old-name new-name
```

### History Commands

```bash
# View log
git log --oneline --graph --all

# View file history
git log --follow -p -- path/to/file

# Search commits
git log --grep="keyword"

# View changes
git diff
git diff --staged
git diff branch1..branch2
```

### Useful Aliases

Add to `~/.gitconfig`:

```ini
[alias]
    # Shortcuts
    co = checkout
    br = branch
    ci = commit
    st = status

    # Logging
    lg = log --oneline --graph --decorate
    ll = log --pretty=format:'%C(yellow)%h%Creset %s %C(cyan)(%cr)%Creset %C(green)<%an>%Creset'

    # Branch management
    branches = branch -a
    remotes = remote -v

    # Undo
    undo = reset --soft HEAD~1
    unstage = reset HEAD --

    # Status
    s = status -sb

    # Diff
    d = diff
    ds = diff --staged

    # Feature workflow
    start = "!f() { git checkout develop && git pull && git checkout -b feature/$1; }; f"
    finish = "!f() { git checkout develop && git merge --no-ff $(git branch --show-current); }; f"
```

### .gitignore for Symfony

```gitignore
###> symfony/framework-bundle ###
/.env.local
/.env.local.php
/.env.*.local
/config/secrets/prod/prod.decrypt.private.php
/public/bundles/
/var/
/vendor/
###< symfony/framework-bundle ###

###> symfony/phpunit-bridge ###
.phpunit.result.cache
/phpunit.xml
###< symfony/phpunit-bridge ###

###> symfony/webpack-encore-bundle ###
/node_modules/
/public/build/
npm-debug.log
yarn-error.log
###< symfony/webpack-encore-bundle ###

# IDE
.idea/
.vscode/
*.swp
*.swo
*~

# OS
.DS_Store
Thumbs.db

# Local
/docker-compose.override.yml
/.php-cs-fixer.cache
```

---

*Happy coding with professional Git workflows!*
