# Security Component Architecture

## System Overview

```
┌─────────────────────────────────────────────────────────────────────────┐
│                          HTTP REQUEST                                   │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         FIREWALL LAYER                                  │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ Firewall                                                         │  │
│  │  - Pattern: '^/admin'                                            │  │
│  │  - Authenticators: [FormLoginAuthenticator]                      │  │
│  │  - TokenStorage: TokenStorage                                    │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
          ┌──────────────────┐            ┌──────────────────┐
          │ Pattern Matches? │            │ Already          │
          │      YES         │            │ Authenticated?   │
          └──────────────────┘            │      NO          │
                    │                     └──────────────────┘
                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                     AUTHENTICATION LAYER                                │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ FormLoginAuthenticator                                           │  │
│  │                                                                  │  │
│  │  1. supports(Request) → bool                                     │  │
│  │  2. authenticate(Request) → Passport                             │  │
│  │  3. createToken(Passport) → Token                                │  │
│  │  4. onAuthenticationSuccess() → Response                         │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
          ┌──────────────────┐            ┌──────────────────┐
          │ Extract          │            │ Load User        │
          │ Credentials      │────────────│ from Provider    │
          └──────────────────┘            └──────────────────┘
                                                    │
                                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         USER PROVIDER LAYER                             │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ InMemoryUserProvider                                             │  │
│  │                                                                  │  │
│  │  loadUserByIdentifier(string) → UserInterface                    │  │
│  │                                                                  │  │
│  │  Users:                                                          │  │
│  │  - admin@example.com → User(password, [ROLE_ADMIN])             │  │
│  │  - user@example.com  → User(password, [ROLE_USER])              │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
          ┌──────────────────────────────────────┐
          │ Verify Password                       │
          │ password_verify($plain, $hash)        │
          └──────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
          ┌──────────────────┐            ┌──────────────────┐
          │ SUCCESS          │            │ FAILURE          │
          │ Create Token     │            │ Throw Exception  │
          └──────────────────┘            └──────────────────┘
                    │
                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                         TOKEN STORAGE LAYER                             │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ TokenStorage                                                     │  │
│  │                                                                  │  │
│  │  Current Token: UsernamePasswordToken                            │  │
│  │    - User: admin@example.com                                     │  │
│  │    - Roles: [ROLE_ADMIN, ROLE_USER]                              │  │
│  │    - Firewall: main                                              │  │
│  │    - Authenticated: true                                         │  │
│  └──────────────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          CONTROLLER                                     │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                      AUTHORIZATION LAYER                                │
│                                                                         │
│  ┌──────────────────────────────────────────────────────────────────┐  │
│  │ AccessDecisionManager                                            │  │
│  │  - Strategy: AFFIRMATIVE                                         │  │
│  │  - Voters: [RoleVoter, ArticleVoter]                             │  │
│  └──────────────────────────────────────────────────────────────────┘  │
│                                                                         │
│  decide(Token, ['ROLE_ADMIN'], $subject)                                │
│                                                                         │
│  ┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐     │
│  │   RoleVoter      │  │  ArticleVoter    │  │  CustomVoter     │     │
│  │                  │  │                  │  │                  │     │
│  │  vote() → 1      │  │  vote() → 0      │  │  vote() → -1     │     │
│  │  (GRANTED)       │  │  (ABSTAIN)       │  │  (DENIED)        │     │
│  └──────────────────┘  └──────────────────┘  └──────────────────┘     │
│                                                                         │
│  Aggregate: 1 grant, 0 abstain, 1 deny                                 │
│  Strategy AFFIRMATIVE: At least one GRANT → ACCESS GRANTED              │
└─────────────────────────────────────────────────────────────────────────┘
                                    │
                    ┌───────────────┴───────────────┐
                    │                               │
                    ▼                               ▼
          ┌──────────────────┐            ┌──────────────────┐
          │ ACCESS GRANTED   │            │ ACCESS DENIED    │
          │ Continue to      │            │ Throw            │
          │ Response         │            │ AccessDenied     │
          └──────────────────┘            │ Exception        │
                    │                     └──────────────────┘
                    ▼
┌─────────────────────────────────────────────────────────────────────────┐
│                          HTTP RESPONSE                                  │
└─────────────────────────────────────────────────────────────────────────┘
```

## Component Relationships

```
┌─────────────────────────────────────────────────────────────────────┐
│                                                                     │
│  Firewall ─────uses────→ Authenticator                              │
│     │                         │                                     │
│     │                         │                                     │
│     │                         └──uses──→ UserProvider               │
│     │                         │              │                      │
│     │                         │              │                      │
│     │                         │              └──returns──→ User     │
│     │                         │                                     │
│     │                         └──creates──→ Token                   │
│     │                                         │                     │
│     │                                         │                     │
│     └──stores──→ TokenStorage ←──stores──────┘                     │
│                                                                     │
│                                                                     │
│  Controller ───uses───→ AccessDecisionManager                       │
│                               │                                     │
│                               │                                     │
│                               └──uses──→ Voter[]                    │
│                               │              │                      │
│                               │              │                      │
│                               └──gets──→ Token from TokenStorage    │
│                                                                     │
└─────────────────────────────────────────────────────────────────────┘
```

## Class Hierarchy

```
UserInterface
    └── User

UserProviderInterface
    └── InMemoryUserProvider

TokenInterface
    └── AbstractToken
            └── UsernamePasswordToken

TokenStorageInterface
    └── TokenStorage

AuthenticatorInterface
    └── FormLoginAuthenticator

VoterInterface
    ├── RoleVoter
    ├── RoleHierarchyVoter
    └── Voter (abstract)
            └── [CustomVoter]

AccessDecisionManagerInterface
    └── AccessDecisionManager

Exception
    └── RuntimeException
            ├── AuthenticationException
            │       ├── UserNotFoundException
            │       └── BadCredentialsException
            └── AccessDeniedException
```

## Data Flow Diagrams

### Login Flow

```
User Form                  Application               Security System
    │                          │                          │
    │  POST /login             │                          │
    │  username=admin          │                          │
    │  password=secret         │                          │
    ├──────────────────────────►                          │
    │                          │                          │
    │                          │  Request                 │
    │                          ├─────────────────────────►│
    │                          │                          │
    │                          │                      ┌───┴───┐
    │                          │                      │Firewall│
    │                          │                      └───┬───┘
    │                          │                          │
    │                          │                      ┌───┴────────┐
    │                          │                      │Authenticator│
    │                          │                      └───┬────────┘
    │                          │                          │
    │                          │                      ┌───┴────────┐
    │                          │                      │UserProvider│
    │                          │                      └───┬────────┘
    │                          │                          │
    │                          │                    Verify Password
    │                          │                          │
    │                          │                      ┌───┴───┐
    │                          │                      │ Token │
    │                          │                      └───┬───┘
    │                          │                          │
    │                          │                      ┌───┴────────┐
    │                          │                      │TokenStorage│
    │                          │                      └───┬────────┘
    │                          │                          │
    │                          │  Response (Redirect)     │
    │                          │◄─────────────────────────┤
    │                          │                          │
    │  Redirect to /dashboard  │                          │
    │◄──────────────────────────                          │
    │                          │                          │
```

### Authorization Flow

```
Controller               AccessDecisionManager           Voters
    │                            │                          │
    │  decide(token,             │                          │
    │         ['ROLE_ADMIN'])    │                          │
    ├───────────────────────────►│                          │
    │                            │                          │
    │                            │  vote(token,             │
    │                            │       null,              │
    │                            │       ['ROLE_ADMIN'])    │
    │                            ├─────────────────────────►│
    │                            │                          │
    │                            │                     ┌────┴────┐
    │                            │                     │RoleVoter│
    │                            │                     └────┬────┘
    │                            │                          │
    │                            │  ACCESS_GRANTED (1)      │
    │                            │◄─────────────────────────┤
    │                            │                          │
    │                            │  vote(...)               │
    │                            ├─────────────────────────►│
    │                            │                          │
    │                            │                     ┌────┴────┐
    │                            │                     │PostVoter│
    │                            │                     └────┬────┘
    │                            │                          │
    │                            │  ACCESS_ABSTAIN (0)      │
    │                            │◄─────────────────────────┤
    │                            │                          │
    │                       Aggregate:                      │
    │                       GRANTED=1                       │
    │                       DENIED=0                        │
    │                       ABSTAIN=1                       │
    │                            │                          │
    │                       Apply Strategy:                 │
    │                       AFFIRMATIVE                     │
    │                       → true                          │
    │                            │                          │
    │  true                      │                          │
    │◄───────────────────────────┤                          │
    │                            │                          │
    │  Continue...               │                          │
    │                            │                          │
```

## Voter Decision Matrix

### RoleVoter

| User Roles | Requested Role | Vote Result |
|-----------|----------------|-------------|
| [ROLE_ADMIN] | ROLE_ADMIN | GRANTED (1) |
| [ROLE_ADMIN] | ROLE_USER | DENIED (-1) |
| [ROLE_USER] | ROLE_ADMIN | DENIED (-1) |
| [ROLE_USER] | EDIT_POST | ABSTAIN (0) |

### RoleHierarchyVoter (with hierarchy)

```
Hierarchy:
ROLE_ADMIN → [ROLE_EDITOR, ROLE_USER]
ROLE_EDITOR → [ROLE_USER]
```

| User Roles | Requested Role | Vote Result |
|-----------|----------------|-------------|
| [ROLE_ADMIN] | ROLE_ADMIN | GRANTED (1) |
| [ROLE_ADMIN] | ROLE_EDITOR | GRANTED (1) |
| [ROLE_ADMIN] | ROLE_USER | GRANTED (1) |
| [ROLE_EDITOR] | ROLE_ADMIN | DENIED (-1) |
| [ROLE_EDITOR] | ROLE_USER | GRANTED (1) |
| [ROLE_USER] | ROLE_EDITOR | DENIED (-1) |

### CustomVoter (PostVoter)

| User | Action | Post Author | Vote Result |
|------|--------|-------------|-------------|
| Author | view | Author | GRANTED (1) |
| Author | edit | Author | GRANTED (1) |
| Author | delete | Author | DENIED (-1) |
| Editor | edit | Anyone | GRANTED (1) |
| Admin | delete | Anyone | GRANTED (1) |
| User | edit | Other | DENIED (-1) |

## Strategy Comparison

### AFFIRMATIVE Strategy

```
Voters: [RoleVoter, PostVoter]
Results: [GRANTED, ABSTAIN]

Decision: GRANTED (at least one grant)
```

### CONSENSUS Strategy

```
Voters: [RoleVoter, PostVoter, CustomVoter]
Results: [GRANTED, GRANTED, DENIED]

Counts: GRANTED=2, DENIED=1
Decision: GRANTED (majority grants)
```

### UNANIMOUS Strategy

```
Voters: [RoleVoter, PostVoter]
Results: [GRANTED, GRANTED]

Decision: GRANTED (all grant or abstain)

---

Voters: [RoleVoter, PostVoter]
Results: [GRANTED, DENIED]

Decision: DENIED (at least one deny)
```

## State Transitions

### Authentication State Machine

```
┌──────────────┐
│ Anonymous    │
│ (No Token)   │
└──────┬───────┘
       │
       │ Login Request
       ▼
┌──────────────┐
│ Authenticating│
│              │
└──────┬───────┘
       │
       │ Success / Failure
       │
       ├─────────────┐
       │             │
       ▼             ▼
┌──────────────┐  ┌──────────────┐
│ Authenticated│  │ Anonymous    │
│ (Has Token)  │  │ (Failed)     │
└──────┬───────┘  └──────────────┘
       │
       │ Logout
       ▼
┌──────────────┐
│ Anonymous    │
└──────────────┘
```

### Authorization State Machine

```
┌──────────────┐
│ Request      │
│ Permission   │
└──────┬───────┘
       │
       │ Check Token
       ▼
┌──────────────┐
│ Get Token    │
│ from Storage │
└──────┬───────┘
       │
       ├──────────────┐
       │              │
       ▼              ▼
  Token Exists   No Token
       │              │
       │              ▼
       │         ┌──────────────┐
       │         │ Access       │
       │         │ Denied       │
       │         └──────────────┘
       │
       │ Ask Voters
       ▼
┌──────────────┐
│ Vote         │
│ Collection   │
└──────┬───────┘
       │
       │ Apply Strategy
       ▼
┌──────────────┐
│ Final        │
│ Decision     │
└──────┬───────┘
       │
       ├──────────────┐
       │              │
       ▼              ▼
┌──────────────┐  ┌──────────────┐
│ Access       │  │ Access       │
│ Granted      │  │ Denied       │
└──────────────┘  └──────────────┘
```

## Memory Structure

### Token Storage

```
TokenStorage
├── token: UsernamePasswordToken
│   ├── user: User
│   │   ├── identifier: "admin@example.com"
│   │   ├── password: "$2y$10$..."
│   │   ├── roles: ["ROLE_ADMIN", "ROLE_USER"]
│   │   └── attributes: {"name": "Admin"}
│   ├── firewallName: "main"
│   ├── roles: ["ROLE_ADMIN", "ROLE_USER"]
│   ├── authenticated: true
│   └── attributes: {}
```

### AccessDecisionManager

```
AccessDecisionManager
├── voters: [
│   ├── RoleVoter
│   ├── RoleHierarchyVoter
│   │   └── hierarchy: {
│   │       "ROLE_ADMIN": ["ROLE_EDITOR", "ROLE_USER"],
│   │       "ROLE_EDITOR": ["ROLE_USER"]
│   │   }
│   └── PostVoter
│ ]
├── strategy: "affirmative"
├── allowIfAllAbstain: false
└── allowIfEqualGrantedDenied: true
```

## Performance Characteristics

| Operation | Time Complexity | Space Complexity |
|-----------|----------------|------------------|
| Load User (InMemory) | O(1) | O(n) users |
| Verify Password | O(1) | O(1) |
| Store Token | O(1) | O(1) |
| Role Check | O(n) roles | O(1) |
| Role Hierarchy Check | O(n*m) | O(n*m) |
| Voter Decision | O(v) voters | O(1) |
| Access Decision | O(v*a) | O(1) |

Where:
- n = number of roles
- m = hierarchy depth
- v = number of voters
- a = number of attributes

## Security Model

```
┌─────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                          │
├─────────────────────────────────────────────────────────────┤
│ 1. Transport Security (HTTPS)                               │
├─────────────────────────────────────────────────────────────┤
│ 2. Firewall (Pattern Matching)                              │
├─────────────────────────────────────────────────────────────┤
│ 3. Authentication (Who are you?)                            │
│    - Credentials Extraction                                 │
│    - User Loading                                           │
│    - Password Verification                                  │
│    - Token Creation                                         │
├─────────────────────────────────────────────────────────────┤
│ 4. Authorization (What can you do?)                         │
│    - Voter-based Decisions                                  │
│    - Role Checking                                          │
│    - Object-level Permissions                               │
├─────────────────────────────────────────────────────────────┤
│ 5. Application Logic                                        │
└─────────────────────────────────────────────────────────────┘
```

This architecture provides defense in depth with multiple security layers working together to protect your application.
