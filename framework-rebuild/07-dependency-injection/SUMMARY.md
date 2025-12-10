# Chapter 07: Dependency Injection Container - Summary

## What Was Built

A complete, production-ready Dependency Injection Container with:

### Core Components

1. **Container** (`Container.php`)
   - Service storage and retrieval
   - Parameter management
   - Circular dependency detection
   - Lazy service instantiation
   - PSR-11 compliant

2. **ContainerBuilder** (`ContainerBuilder.php`)
   - Service registration and definition
   - Alias management
   - Tagged service support
   - Compiler pass system
   - Autowiring support
   - Compilation and freezing

3. **Definition** (`Definition.php`)
   - Service class and arguments
   - Method calls (setter injection)
   - Tags and attributes
   - Factory support
   - Public/private, shared, lazy, synthetic flags
   - Inheritance support

4. **Reference** (`Reference.php`)
   - Service references
   - Invalid reference behavior handling
   - Support for optional dependencies

### Compiler Passes

1. **AutowirePass** (`AutowirePass.php`)
   - Automatic dependency resolution using Reflection
   - Type hint matching
   - Interface and parent class autowiring
   - Default value handling
   - Nullable type support

2. **ResolveReferencesPass** (`ResolveReferencesPass.php`)
   - Service reference validation
   - Missing dependency detection
   - Nested reference checking
   - Factory reference validation

### Exceptions

- `ContainerException` - Base container exception (PSR-11)
- `ServiceNotFoundException` - Missing service (PSR-11)
- `ParameterNotFoundException` - Missing parameter
- `CircularDependencyException` - Circular dependency detection
- `FrozenContainerException` - Modification after compilation

### Integration

- **Kernel** (`Kernel.php`) - Application kernel with integrated container
- **Configuration** - PHP and YAML service configuration examples
- **Examples** - Real-world service examples (Repository, Service, Controller, Factory)

### Testing

Comprehensive PHPUnit test suite covering:
- Container basic operations
- Service registration and retrieval
- Parameter management
- Autowiring functionality
- Compiler passes
- Integration scenarios
- Edge cases and error handling

## File Structure

```
07-dependency-injection/
├── README.md                          # Complete documentation
├── QUICKSTART.md                      # Quick start guide
├── EXERCISES.md                       # Practice exercises
├── SUMMARY.md                         # This file
├── composer.json                      # Dependencies and autoloading
├── phpunit.xml                        # PHPUnit configuration
├── .gitignore                         # Git ignore rules
│
├── src/
│   ├── DependencyInjection/
│   │   ├── ContainerInterface.php     # Container interface (PSR-11)
│   │   ├── Container.php              # Basic container implementation
│   │   ├── ContainerBuilder.php       # Builder with compilation
│   │   ├── Definition.php             # Service definition
│   │   ├── Reference.php              # Service reference
│   │   │
│   │   ├── Compiler/
│   │   │   ├── CompilerPassInterface.php
│   │   │   ├── AutowirePass.php       # Autowiring implementation
│   │   │   └── ResolveReferencesPass.php
│   │   │
│   │   └── Exception/
│   │       ├── ContainerException.php
│   │       ├── ServiceNotFoundException.php
│   │       ├── ParameterNotFoundException.php
│   │       ├── CircularDependencyException.php
│   │       └── FrozenContainerException.php
│   │
│   └── Kernel.php                     # Application kernel
│
├── config/
│   ├── services.php                   # PHP service configuration
│   └── services.yaml                  # YAML configuration reference
│
├── examples/
│   ├── demo.php                       # Interactive demo
│   ├── UserRepository.php             # Example repository
│   ├── UserService.php                # Example service
│   ├── UserController.php             # Example controller
│   ├── MailerFactory.php              # Example factory
│   └── Mailer.php                     # Example factory product
│
└── tests/
    ├── ContainerTest.php              # Container tests
    ├── ContainerBuilderTest.php       # Builder tests
    ├── DefinitionTest.php             # Definition tests
    ├── ReferenceTest.php              # Reference tests
    ├── AutowirePassTest.php           # Autowiring tests
    ├── ResolveReferencesPassTest.php  # Reference resolution tests
    └── IntegrationTest.php            # Integration tests
```

## Key Features

### 1. Service Management
- Service registration and retrieval
- Singleton pattern (shared services)
- Service aliases
- Public/private services
- Synthetic services (runtime injection)
- Abstract services (templates)

### 2. Dependency Injection
- Constructor injection
- Setter injection (method calls)
- Automatic dependency resolution (autowiring)
- Manual dependency wiring
- Factory-based creation

### 3. Configuration
- Parameters for configuration values
- Parameter placeholders (%parameter%)
- Service references (@service.id)
- Tagged services for grouping
- PHP and YAML configuration

### 4. Compilation
- Two-phase container (build + runtime)
- Compiler pass system
- Service definition optimization
- Reference resolution and validation
- Container freezing after compilation

### 5. Autowiring
- PHP 8.2+ Reflection-based
- Type hint matching
- Interface and parent class resolution
- Default value support
- Nullable type handling
- Clear error messages

### 6. Error Handling
- PSR-11 compliant exceptions
- Circular dependency detection
- Missing service/parameter detection
- Frozen container protection
- Helpful error messages

## Usage Examples

### Basic Service Registration

```php
$container = new ContainerBuilder();
$container->register('logger', NullLogger::class);
$container->compile();
$logger = $container->get('logger');
```

### Dependency Injection

```php
$container->register('database', PDO::class)
    ->setArguments(['mysql:host=localhost', 'user', 'pass']);

$container->register('repository', UserRepository::class)
    ->setArguments([new Reference('database')]);
```

### Autowiring

```php
$container->register('user.controller', UserController::class)
    ->setAutowired(true);

$container->addCompilerPass(new AutowirePass());
$container->compile();
```

### Tagged Services

```php
$container->register('listener', UserListener::class)
    ->addTag('event.listener', ['event' => 'user.created']);

$listeners = $container->findTaggedServiceIds('event.listener');
```

### Factory Pattern

```php
$container->register('factory', MailerFactory::class);
$container->register('mailer', Mailer::class)
    ->setFactory([new Reference('factory'), 'create']);
```

## Learning Objectives Achieved

1. ✅ Understanding dependency injection principles
2. ✅ Implementing a service container from scratch
3. ✅ Building service definitions and references
4. ✅ Creating compiler passes for processing
5. ✅ Implementing autowiring with Reflection
6. ✅ Managing service lifecycle and scope
7. ✅ Detecting and handling circular dependencies
8. ✅ Writing comprehensive tests for DI container
9. ✅ Integrating container with application kernel
10. ✅ Using tagged services for automatic registration

## Testing Coverage

- ✅ Container basic operations
- ✅ Service registration and retrieval
- ✅ Parameter management
- ✅ Service definitions
- ✅ References and aliases
- ✅ Autowiring functionality
- ✅ Compiler passes
- ✅ Tagged services
- ✅ Factory pattern
- ✅ Method calls
- ✅ Integration scenarios
- ✅ Error handling
- ✅ Edge cases

## Next Steps

1. **Extend Functionality**
   - Service decoration
   - Scoped services
   - Lazy proxy generation
   - Container dumping to PHP code

2. **Optimization**
   - Service preloading
   - Cached container generation
   - Service map optimization
   - Memory usage reduction

3. **Advanced Features**
   - Service locator pattern
   - Service subscribers
   - Conditional services
   - Container extensions/bundles

4. **Integration**
   - Event dispatcher integration
   - Console command registration
   - HTTP kernel integration
   - Routing integration

## Real-World Applications

This container can be used for:

1. **Web Applications** - Manage controllers, services, repositories
2. **CLI Tools** - Wire console commands and dependencies
3. **APIs** - Build RESTful services with proper DI
4. **Microservices** - Small, focused services with clear dependencies
5. **Testing** - Easy mocking and dependency injection in tests

## Best Practices Demonstrated

1. **PSR-11 Compliance** - Standard container interface
2. **Type Safety** - PHP 8.2+ type hints and readonly properties
3. **SOLID Principles** - Single responsibility, dependency inversion
4. **Error Handling** - Proper exceptions and error messages
5. **Documentation** - Comprehensive docblocks and README
6. **Testing** - Full test coverage with PHPUnit
7. **Examples** - Real-world usage examples

## Performance Characteristics

- **Service Creation**: O(1) for already instantiated services
- **First Access**: O(n) where n = dependency depth
- **Memory**: One instance per shared service
- **Compilation**: One-time cost, optimizes runtime performance

## Comparison with Symfony

This implementation includes:
- ✅ Basic container functionality
- ✅ Service definitions
- ✅ Autowiring
- ✅ Compiler passes
- ✅ Tagged services
- ✅ References and aliases
- ✅ Parameters

Symfony additionally provides:
- Service decoration
- Lazy proxy generation
- Container dumping/caching
- Extension system
- YAML/XML loaders
- Service locators
- And much more...

## Conclusion

This chapter provides a complete, working dependency injection container that demonstrates all core concepts used in modern PHP frameworks. The implementation is educational yet practical, showing how frameworks like Symfony manage services and dependencies.

The code is production-ready for small to medium projects and serves as an excellent foundation for understanding how professional frameworks work under the hood.

## Resources

- [README.md](README.md) - Complete documentation
- [QUICKSTART.md](QUICKSTART.md) - Quick start guide
- [EXERCISES.md](EXERCISES.md) - Practice exercises
- [examples/demo.php](examples/demo.php) - Interactive demo
- [tests/](tests/) - Comprehensive test suite

## Credits

Built following Symfony's DI component architecture and PSR-11 standards, adapted for educational purposes with modern PHP 8.2+ features.
