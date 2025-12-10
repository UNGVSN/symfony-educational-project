# Dependency Injection Container - Architecture

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                      Application                             │
│                         (Kernel)                             │
└────────────────────────┬────────────────────────────────────┘
                         │
                         ▼
┌─────────────────────────────────────────────────────────────┐
│                  ContainerBuilder                            │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Service Definitions                               │     │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐          │     │
│  │  │Definition│ │Definition│ │Definition│  ...     │     │
│  │  └──────────┘ └──────────┘ └──────────┘          │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Compiler Passes                                   │     │
│  │  ┌─────────────────┐  ┌─────────────────┐        │     │
│  │  │  AutowirePass   │  │ResolveReferences│  ...   │     │
│  │  └─────────────────┘  └─────────────────┘        │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│                      compile()                               │
│                         │                                    │
└─────────────────────────┼────────────────────────────────────┘
                          ▼
┌─────────────────────────────────────────────────────────────┐
│                    Container (Runtime)                       │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Service Instances (cached)                        │     │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────┐          │     │
│  │  │ Service  │ │ Service  │ │ Service  │  ...     │     │
│  │  └──────────┘ └──────────┘ └──────────┘          │     │
│  └────────────────────────────────────────────────────┘     │
│                                                              │
│  ┌────────────────────────────────────────────────────┐     │
│  │  Parameters                                        │     │
│  │  app.name, database.host, ...                     │     │
│  └────────────────────────────────────────────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

## Component Relationships

```
┌──────────────────┐
│ ContainerBuilder │──────┐
└────────┬─────────┘      │ extends
         │                │
         │ has many       ▼
         │         ┌──────────┐
         ├────────►│Container │
         │         └──────────┘
         │                ▲
         │                │ implements
         │                │
         │         ┌──────────────────┐
         │         │ContainerInterface│◄──── PSR-11
         │         └──────────────────┘
         │
         │ has many
         ▼
  ┌────────────┐
  │ Definition │───────┐
  └────────────┘       │ has many
         │             │
         │ has many    ▼
         │       ┌───────────┐
         └──────►│ Reference │
                 └───────────┘

┌──────────────────────┐
│CompilerPassInterface │◄──────┐
└──────────────────────┘       │ implements
         ▲                     │
         │ implements           │
         │                     │
    ┌────┴────┐          ┌─────┴──────────┐
    │         │          │                │
┌───────────┐ │  ┌───────────────────┐   │
│AutowirePass│ └──│ResolveReferencesPass│ │
└───────────┘    └───────────────────┘   │
                                          │
                            (custom passes can be added)
```

## Class Hierarchy

```
ContainerInterface (PSR-11)
    │
    ├── get(string $id): mixed
    ├── has(string $id): bool
    ├── set(string $id, mixed $service)
    ├── getParameter(string $name): mixed
    ├── hasParameter(string $name): bool
    └── setParameter(string $name, mixed $value)
              ▲
              │ implements
              │
         Container
              │
              ├── $services: array
              ├── $parameters: array
              ├── $loading: array (circular detection)
              │
              └── protected createService(string $id): mixed
                            ▲
                            │ extends
                            │
                    ContainerBuilder
                            │
                            ├── $definitions: array
                            ├── $aliases: array
                            ├── $compilerPasses: array
                            ├── $compiled: bool
                            │
                            ├── register(string $id, ?string $class): Definition
                            ├── setDefinition(string $id, Definition $def)
                            ├── setAlias(string $alias, string $id)
                            ├── addCompilerPass(CompilerPassInterface $pass)
                            ├── compile(): void
                            ├── findTaggedServiceIds(string $tag): array
                            └── autowire(string $class): array
```

## Service Creation Flow

```
User calls: $container->get('user.controller')
                    │
                    ▼
         ┌──────────────────────┐
         │ Is service cached?   │──Yes──► Return cached instance
         └──────────┬───────────┘
                    │ No
                    ▼
         ┌──────────────────────┐
         │ Circular dependency? │──Yes──► Throw CircularDependencyException
         └──────────┬───────────┘
                    │ No
                    ▼
         ┌──────────────────────┐
         │ Get Definition       │
         └──────────┬───────────┘
                    │
                    ▼
         ┌──────────────────────┐
         │ Has Factory?         │──Yes──┐
         └──────────┬───────────┘       │
                    │ No                 │
                    ▼                    ▼
         ┌──────────────────────┐  ┌─────────────┐
         │ Get Constructor Args │  │ Call Factory│
         └──────────┬───────────┘  └──────┬──────┘
                    │                     │
                    ▼                     │
         ┌──────────────────────┐        │
         │ Resolve Arguments    │        │
         │ (recursively)        │        │
         └──────────┬───────────┘        │
                    │                     │
                    ▼                     │
         ┌──────────────────────┐        │
         │ Instantiate Service  │◄───────┘
         └──────────┬───────────┘
                    │
                    ▼
         ┌──────────────────────┐
         │ Execute Method Calls │
         └──────────┬───────────┘
                    │
                    ▼
         ┌──────────────────────┐
         │ Cache Service        │
         └──────────┬───────────┘
                    │
                    ▼
         ┌──────────────────────┐
         │ Return Service       │
         └──────────────────────┘
```

## Compilation Process

```
    ContainerBuilder created
              │
              ▼
    ┌─────────────────────┐
    │ Register Services   │
    │ - register()        │
    │ - setDefinition()   │
    │ - setAlias()        │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────┐
    │ Set Parameters      │
    │ - setParameter()    │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────┐
    │ Add Compiler Passes │
    │ - AutowirePass      │
    │ - ResolveRefs       │
    │ - Custom passes     │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────┐
    │   compile()         │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────────────┐
    │ Execute Compiler Passes     │
    │                             │
    │ Pass 1: AutowirePass        │
    │ - Read type hints           │
    │ - Match to services         │
    │ - Set arguments             │
    │         │                   │
    │         ▼                   │
    │ Pass 2: ResolveReferences   │
    │ - Validate references       │
    │ - Check service exists      │
    │ - Check circular deps       │
    └─────────┬───────────────────┘
              │
              ▼
    ┌─────────────────────┐
    │ Container Frozen    │
    │ (cannot modify)     │
    └─────────────────────┘
```

## Autowiring Process

```
Service with autowiring enabled
              │
              ▼
    ┌─────────────────────┐
    │ Get Class           │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────┐
    │ Use Reflection      │
    │ Get Constructor     │
    └─────────┬───────────┘
              │
              ▼
    ┌─────────────────────┐
    │ Get Parameters      │
    └─────────┬───────────┘
              │
              ▼
    For each parameter:
    ┌──────────────────────────┐
    │ Get Type Hint            │
    └──────────┬───────────────┘
               │
               ▼
    ┌──────────────────────────┐
    │ Is Built-in Type?        │──Yes──► Skip or use default
    └──────────┬───────────────┘
               │ No
               ▼
    ┌──────────────────────────┐
    │ Find Service by Type     │
    │ - Check exact match      │
    │ - Check interface        │
    │ - Check parent class     │
    └──────────┬───────────────┘
               │
               ├──Found──────► Add Reference
               │
               ├──Not Found──┐
               │             │
               ▼             ▼
    ┌──────────────┐  ┌────────────────┐
    │Has Default?  │  │ Is Nullable?   │
    └──────┬───────┘  └────────┬───────┘
           │                   │
           │ Yes               │ Yes
           ▼                   ▼
    ┌──────────────┐  ┌────────────────┐
    │Use Default   │  │ Use null       │
    └──────────────┘  └────────────────┘
           │                   │
           └───────┬───────────┘
                   │ No to both
                   ▼
          ┌────────────────┐
          │ Throw Exception│
          └────────────────┘
```

## Tagged Services Processing

```
    Container has services with tags
              │
              ▼
    ┌─────────────────────────────┐
    │ findTaggedServiceIds('tag') │
    └─────────┬───────────────────┘
              │
              ▼
    Returns: [
        'service1' => [
            ['priority' => 10, 'event' => 'user.created']
        ],
        'service2' => [
            ['priority' => 5, 'event' => 'user.updated']
        ]
    ]
              │
              ▼
    ┌─────────────────────────────┐
    │ Custom Compiler Pass        │
    │ - Sort by priority          │
    │ - Register with dispatcher  │
    │ - Configure services        │
    └─────────────────────────────┘
```

## Memory Layout (Runtime)

```
Container Object
├── services: [
│   'service_container' => Container instance
│   'logger' => NullLogger instance
│   'database' => PDO instance
│   'user.repository' => UserRepository instance
│   'user.service' => UserService instance
│   'user.controller' => UserController instance
│   ...
│]
│
├── parameters: [
│   'app.name' => 'My Application'
│   'app.version' => '1.0.0'
│   'database.host' => 'localhost'
│   'database.port' => 3306
│   ...
│]
│
└── loading: [
    (empty when not creating services)
    (contains 'service.id' => true during creation)
]
```

## Error Handling Flow

```
    Service Request
         │
         ▼
    ┌─────────────────┐
    │ Service exists? │──No──► ServiceNotFoundException
    └────────┬────────┘
             │ Yes
             ▼
    ┌─────────────────┐
    │ In loading?     │──Yes──► CircularDependencyException
    └────────┬────────┘
             │ No
             ▼
    ┌─────────────────┐
    │ Is Synthetic?   │──Yes──► ServiceNotFoundException
    └────────┬────────┘         (must be set at runtime)
             │ No
             ▼
    ┌─────────────────┐
    │ Is Abstract?    │──Yes──► ServiceNotFoundException
    └────────┬────────┘         (cannot instantiate)
             │ No
             ▼
    ┌─────────────────┐
    │ Create Service  │
    └─────────────────┘
```

## Real-World Example Flow

```
User Request → Kernel
                 │
                 ▼
         Kernel::boot()
                 │
                 ▼
         buildContainer()
                 │
                 ├─► Load services.php
                 │   - Register services
                 │   - Set parameters
                 │
                 ├─► Add compiler passes
                 │   - AutowirePass
                 │   - ResolveReferencesPass
                 │
                 └─► compile()
                         │
                         ▼
                 Container ready
                         │
                         ▼
         handle('/users')
                 │
                 ▼
         $container->get('user.controller')
                 │
                 ├─► Resolve UserService dependency
                 │   │
                 │   ├─► Resolve UserRepository dependency
                 │   │   │
                 │   │   └─► Resolve PDO dependency
                 │   │       (create PDO)
                 │   │
                 │   └─► Create UserRepository with PDO
                 │
                 ├─► Resolve LoggerInterface dependency
                 │   (get 'logger' service)
                 │
                 └─► Create UserController
                     with UserService and Logger
                         │
                         ▼
                 Execute controller action
                         │
                         ▼
                 Return response
```

## Performance Characteristics

```
Operation                    | Time Complexity | Space Complexity
─────────────────────────────┼─────────────────┼─────────────────
get() - cached               | O(1)            | O(1)
get() - first access         | O(d)            | O(n)
set()                        | O(1)            | O(1)
has()                        | O(1)            | O(1)
register()                   | O(1)            | O(1)
compile()                    | O(n*m)          | O(n)
findTaggedServiceIds()       | O(n)            | O(k)

where:
d = dependency depth
n = number of services
m = number of compiler passes
k = number of tagged services
```

## Thread Safety

```
This implementation is NOT thread-safe for:
- Service creation
- Container modification
- Compilation

For thread-safe usage:
1. Compile container once
2. Use read-only operations (get, has)
3. Create separate container per thread
4. Use immutable services
```
