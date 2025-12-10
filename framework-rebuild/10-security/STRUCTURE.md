# Chapter 10: Security - File Structure

## Overview

This chapter implements a complete security system with authentication and authorization components.

## Directory Structure

```
10-security/
├── README.md                          # Comprehensive guide (765 lines)
├── QUICK_REFERENCE.md                 # Quick reference guide
├── STRUCTURE.md                       # This file
├── run_tests.php                      # Test runner script
│
├── src/Security/
│   ├── Core/
│   │   ├── User/
│   │   │   ├── UserInterface.php              # User contract
│   │   │   ├── User.php                       # Basic user implementation
│   │   │   ├── UserProviderInterface.php      # User provider contract
│   │   │   └── InMemoryUserProvider.php       # In-memory user storage
│   │   │
│   │   ├── Authentication/
│   │   │   ├── Token/
│   │   │   │   ├── TokenInterface.php         # Security token contract
│   │   │   │   ├── AbstractToken.php          # Base token implementation
│   │   │   │   └── UsernamePasswordToken.php  # Username/password token
│   │   │   ├── TokenStorageInterface.php      # Token storage contract
│   │   │   └── TokenStorage.php               # Token storage implementation
│   │   │
│   │   ├── Authorization/
│   │   │   ├── Voter/
│   │   │   │   ├── VoterInterface.php         # Voter contract
│   │   │   │   ├── Voter.php                  # Abstract voter
│   │   │   │   ├── RoleVoter.php              # Role-based voting
│   │   │   │   └── RoleHierarchyVoter.php     # Role hierarchy voting
│   │   │   ├── AccessDecisionManagerInterface.php
│   │   │   └── AccessDecisionManager.php      # Aggregates voter decisions
│   │   │
│   │   └── Exception/
│   │       ├── AuthenticationException.php     # Base auth exception
│   │       ├── UserNotFoundException.php       # User not found
│   │       ├── BadCredentialsException.php     # Invalid credentials
│   │       └── AccessDeniedException.php       # Authorization failed
│   │
│   └── Http/
│       ├── Authenticator/
│       │   ├── Passport/
│       │   │   └── Passport.php               # Authentication passport
│       │   ├── AuthenticatorInterface.php     # Authenticator contract
│       │   └── FormLoginAuthenticator.php     # Form login handler
│       │
│       └── Firewall/
│           └── Firewall.php                   # Authentication entry point
│
├── examples/
│   ├── basic_login.php                # Basic authentication example
│   ├── authorization_example.php      # Authorization and voters example
│   └── complete_example.php           # Full application example
│
└── tests/
    ├── UserTest.php                   # User and provider tests
    └── AuthorizationTest.php          # Authorization tests
```

## Component Count

- **Core Interfaces**: 7
- **Core Classes**: 12
- **HTTP Components**: 4
- **Exception Classes**: 4
- **Examples**: 3
- **Tests**: 2
- **Total PHP Files**: 32
- **Documentation Files**: 3

## Key Features Implemented

### Authentication
- [x] User interface and implementation
- [x] User provider (in-memory)
- [x] Security tokens
- [x] Token storage
- [x] Form login authenticator
- [x] Firewall system
- [x] Password hashing with PHP 8.2+

### Authorization
- [x] Voter system
- [x] Role-based access control
- [x] Role hierarchy
- [x] Custom voters for domain objects
- [x] Access decision manager
- [x] Multiple strategies (affirmative, consensus, unanimous)

### Security Flow
- [x] Request → Firewall → Authenticator → Token
- [x] Token → Voter → Access Decision
- [x] Exception handling
- [x] Success/failure callbacks

## Usage Examples

### Run All Tests
```bash
php run_tests.php
```

### Run Individual Examples
```bash
# Basic authentication
php examples/basic_login.php

# Authorization with voters
php examples/authorization_example.php

# Complete application
php examples/complete_example.php
```

### Run Individual Tests
```bash
php tests/UserTest.php
php tests/AuthorizationTest.php
```

## Learning Path

1. **Start with README.md**: Complete theory and architecture
2. **Study Core Interfaces**: UserInterface, TokenInterface, VoterInterface
3. **Run basic_login.php**: See authentication in action
4. **Run authorization_example.php**: Understand voters
5. **Run complete_example.php**: Full application flow
6. **Run Tests**: Verify understanding
7. **Read QUICK_REFERENCE.md**: Common patterns

## Integration Points

This chapter integrates with:
- Chapter 1: HTTP Foundation (Request/Response)
- Chapter 2: Front Controller (Request handling)
- Chapter 3: Routing (Protected routes)
- Chapter 9: Forms (Login forms, CSRF protection)

## Next Chapters

After mastering security, you can:
- Add session management
- Implement remember me functionality
- Add OAuth/JWT authentication
- Integrate with Doctrine for database users
- Build two-factor authentication
- Add API token authentication

## Performance Notes

- InMemoryUserProvider: O(1) user lookup with caching
- RoleVoter: O(n) where n = number of roles
- RoleHierarchyVoter: O(n*m) where n = roles, m = hierarchy depth
- AccessDecisionManager: O(v) where v = number of voters

## Security Considerations

- Passwords are hashed using modern algorithms (Bcrypt, Argon2id)
- Tokens are cleared on logout
- Credentials are erased after authentication
- HTTPS should be used in production
- CSRF protection should be implemented for forms
- Session IDs should be regenerated on login
- Rate limiting should be added for login attempts

## File Sizes

- README.md: 765 lines (comprehensive guide)
- Complete example: ~350 lines (full application demo)
- Authorization example: ~300 lines (voter demonstrations)
- Basic login: ~180 lines (authentication basics)

## Code Quality

- PHP 8.2+ features used throughout
- Full type hints on all methods
- Comprehensive DocBlocks
- Follows PSR-12 coding standards
- No external dependencies
- Educational comments included
