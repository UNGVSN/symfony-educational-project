# Data Validation - Resources

Curated links to official Symfony documentation and external resources for mastering data validation.

---

## Official Symfony Documentation

### Core Documentation

- [Validation Component](https://symfony.com/doc/current/validation.html)
  - Complete guide to Symfony's Validator component
  - Basic usage, constraints, and configuration

- [Validation Constraints Reference](https://symfony.com/doc/current/reference/constraints.html)
  - Comprehensive list of all built-in constraints
  - Detailed options and examples for each constraint

- [How to Create a Custom Validation Constraint](https://symfony.com/doc/current/validation/custom_constraint.html)
  - Step-by-step guide to creating custom constraints
  - Best practices for constraint validators

### Validation Features

- [How to Use Validation Groups](https://symfony.com/doc/current/validation/groups.html)
  - Conditional validation with groups
  - Group sequences and providers
  - Dynamic group selection

- [How to Sequentially Apply Validation Groups](https://symfony.com/doc/current/validation/sequence_provider.html)
  - Group sequence providers
  - Dynamic validation based on object state

- [How to Validate Raw Values](https://symfony.com/doc/current/validation/raw_values.html)
  - Validating values without objects
  - Array validation
  - API request validation

### Integration

- [Form Validation](https://symfony.com/doc/current/forms.html#form-validation)
  - Automatic validation in forms
  - Form-specific constraints
  - Validation groups in forms

- [Doctrine ORM Validation](https://symfony.com/doc/current/reference/constraints/UniqueEntity.html)
  - UniqueEntity constraint
  - Database-level validation
  - Entity lifecycle validation

- [API Platform Validation](https://api-platform.com/docs/core/validation/)
  - Validation in REST APIs
  - Validation groups for different operations
  - Custom validation for API resources

### Advanced Topics

- [Callback Constraint](https://symfony.com/doc/current/reference/constraints/Callback.html)
  - Using callbacks for custom validation logic
  - Accessing validation context
  - Multi-field validation

- [Expression Constraint](https://symfony.com/doc/current/reference/constraints/Expression.html)
  - Using ExpressionLanguage for validation
  - Complex conditional validation
  - Custom expression functions

- [Compound Constraints](https://symfony.com/doc/current/validation/custom_constraint.html#creating-a-reusable-set-of-constraints)
  - Creating reusable constraint sets
  - Combining multiple constraints

---

## Constraint Types Reference

### String Constraints

- [NotBlank](https://symfony.com/doc/current/reference/constraints/NotBlank.html) - Value must not be blank
- [Blank](https://symfony.com/doc/current/reference/constraints/Blank.html) - Value must be blank
- [NotNull](https://symfony.com/doc/current/reference/constraints/NotNull.html) - Value must not be null
- [IsNull](https://symfony.com/doc/current/reference/constraints/IsNull.html) - Value must be null
- [Type](https://symfony.com/doc/current/reference/constraints/Type.html) - Value must be of specific type
- [Email](https://symfony.com/doc/current/reference/constraints/Email.html) - Valid email address
- [Length](https://symfony.com/doc/current/reference/constraints/Length.html) - String length constraints
- [Url](https://symfony.com/doc/current/reference/constraints/Url.html) - Valid URL
- [Regex](https://symfony.com/doc/current/reference/constraints/Regex.html) - Regular expression matching
- [Ip](https://symfony.com/doc/current/reference/constraints/Ip.html) - Valid IP address
- [Json](https://symfony.com/doc/current/reference/constraints/Json.html) - Valid JSON string
- [Uuid](https://symfony.com/doc/current/reference/constraints/Uuid.html) - Valid UUID
- [Ulid](https://symfony.com/doc/current/reference/constraints/Ulid.html) - Valid ULID
- [UserPassword](https://symfony.com/doc/current/reference/constraints/UserPassword.html) - Current user password
- [NotCompromisedPassword](https://symfony.com/doc/current/reference/constraints/NotCompromisedPassword.html) - Password not leaked in data breach
- [PasswordStrength](https://symfony.com/doc/current/reference/constraints/PasswordStrength.html) - Password strength validation

### Numeric Constraints

- [Range](https://symfony.com/doc/current/reference/constraints/Range.html) - Value within range
- [Positive](https://symfony.com/doc/current/reference/constraints/Positive.html) - Positive number
- [PositiveOrZero](https://symfony.com/doc/current/reference/constraints/PositiveOrZero.html) - Positive or zero
- [Negative](https://symfony.com/doc/current/reference/constraints/Negative.html) - Negative number
- [NegativeOrZero](https://symfony.com/doc/current/reference/constraints/NegativeOrZero.html) - Negative or zero
- [DivisibleBy](https://symfony.com/doc/current/reference/constraints/DivisibleBy.html) - Divisible by value
- [LessThan](https://symfony.com/doc/current/reference/constraints/LessThan.html) - Less than value
- [LessThanOrEqual](https://symfony.com/doc/current/reference/constraints/LessThanOrEqual.html) - Less than or equal
- [GreaterThan](https://symfony.com/doc/current/reference/constraints/GreaterThan.html) - Greater than value
- [GreaterThanOrEqual](https://symfony.com/doc/current/reference/constraints/GreaterThanOrEqual.html) - Greater than or equal
- [EqualTo](https://symfony.com/doc/current/reference/constraints/EqualTo.html) - Equal to value
- [NotEqualTo](https://symfony.com/doc/current/reference/constraints/NotEqualTo.html) - Not equal to value
- [IdenticalTo](https://symfony.com/doc/current/reference/constraints/IdenticalTo.html) - Identical to value
- [NotIdenticalTo](https://symfony.com/doc/current/reference/constraints/NotIdenticalTo.html) - Not identical to value

### Date and Time Constraints

- [Date](https://symfony.com/doc/current/reference/constraints/Date.html) - Valid date
- [DateTime](https://symfony.com/doc/current/reference/constraints/DateTime.html) - Valid datetime
- [Time](https://symfony.com/doc/current/reference/constraints/Time.html) - Valid time
- [Timezone](https://symfony.com/doc/current/reference/constraints/Timezone.html) - Valid timezone

### Choice Constraints

- [Choice](https://symfony.com/doc/current/reference/constraints/Choice.html) - Value from choices
- [Language](https://symfony.com/doc/current/reference/constraints/Language.html) - Valid language code
- [Locale](https://symfony.com/doc/current/reference/constraints/Locale.html) - Valid locale
- [Country](https://symfony.com/doc/current/reference/constraints/Country.html) - Valid country code
- [Currency](https://symfony.com/doc/current/reference/constraints/Currency.html) - Valid currency code

### File Constraints

- [File](https://symfony.com/doc/current/reference/constraints/File.html) - Valid file
- [Image](https://symfony.com/doc/current/reference/constraints/Image.html) - Valid image file

### Financial Constraints

- [Bic](https://symfony.com/doc/current/reference/constraints/Bic.html) - Valid BIC (Bank Identifier Code)
- [CardScheme](https://symfony.com/doc/current/reference/constraints/CardScheme.html) - Valid credit card
- [Currency](https://symfony.com/doc/current/reference/constraints/Currency.html) - Valid currency
- [Luhn](https://symfony.com/doc/current/reference/constraints/Luhn.html) - Luhn algorithm validation
- [Iban](https://symfony.com/doc/current/reference/constraints/Iban.html) - Valid IBAN
- [Isbn](https://symfony.com/doc/current/reference/constraints/Isbn.html) - Valid ISBN
- [Issn](https://symfony.com/doc/current/reference/constraints/Issn.html) - Valid ISSN

### Collection Constraints

- [Count](https://symfony.com/doc/current/reference/constraints/Count.html) - Collection count
- [Unique](https://symfony.com/doc/current/reference/constraints/Unique.html) - Unique values
- [Collection](https://symfony.com/doc/current/reference/constraints/Collection.html) - Array structure validation
- [All](https://symfony.com/doc/current/reference/constraints/All.html) - All elements validation

### Object Constraints

- [Valid](https://symfony.com/doc/current/reference/constraints/Valid.html) - Cascade validation
- [Traverse](https://symfony.com/doc/current/reference/constraints/Traverse.html) - Control object traversal

### Boolean Constraints

- [IsTrue](https://symfony.com/doc/current/reference/constraints/IsTrue.html) - Value must be true
- [IsFalse](https://symfony.com/doc/current/reference/constraints/IsFalse.html) - Value must be false

### Other Constraints

- [Callback](https://symfony.com/doc/current/reference/constraints/Callback.html) - Custom callback validation
- [Expression](https://symfony.com/doc/current/reference/constraints/Expression.html) - Expression language validation
- [When](https://symfony.com/doc/current/reference/constraints/When.html) - Conditional constraint application
- [Sequentially](https://symfony.com/doc/current/reference/constraints/Sequentially.html) - Sequential constraint application
- [AtLeastOneOf](https://symfony.com/doc/current/reference/constraints/AtLeastOneOf.html) - At least one constraint must pass
- [Compound](https://symfony.com/doc/current/reference/constraints/Compound.html) - Create reusable constraint sets

---

## Configuration

### YAML Configuration

- [Validation Configuration](https://symfony.com/doc/current/reference/configuration/framework.html#validation)
  - Framework validation settings
  - Enabling/disabling features
  - Cache configuration

### Service Configuration

- [Validation Services](https://symfony.com/doc/current/validation.html#validation-service-configuration)
  - Custom constraint validators as services
  - Service injection in validators
  - Tagging validators

---

## Best Practices & Guides

### Symfony Blog & Articles

- [Symfony Blog - Validation](https://symfony.com/blog/category/validation)
  - Latest updates and features
  - Best practices articles
  - Performance tips

### SymfonyCasts

- [Symfony Validation Tutorials](https://symfonycasts.com/search?q=validation)
  - Video tutorials and courses
  - Hands-on examples
  - Real-world scenarios

### GitHub Discussions

- [Symfony Validator Component Repository](https://github.com/symfony/validator)
  - Source code reference
  - Issue tracking
  - Community discussions

---

## Related Components

### Symfony Components

- [Form Component](https://symfony.com/doc/current/forms.html)
  - Form building and validation
  - Integration with Validator component

- [Serializer Component](https://symfony.com/doc/current/serializer.html)
  - Data serialization and validation
  - Validation during denormalization

- [ExpressionLanguage Component](https://symfony.com/doc/current/components/expression_language.html)
  - Used by Expression constraint
  - Custom expression functions

### Third-Party Bundles

- [API Platform](https://api-platform.com/)
  - REST/GraphQL API validation
  - Advanced validation features

- [EasyAdmin](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
  - Admin interface with validation
  - Form customization

---

## Testing

- [Testing with Validators](https://symfony.com/doc/current/validation.html#testing)
  - Unit testing constraints
  - Functional testing with validation

- [Validator Test Case](https://symfony.com/doc/current/components/validator.html#unit-testing)
  - Base test class for validators
  - Testing custom constraints

---

## Community Resources

### Books

- [Symfony 7: The Fast Track](https://symfony.com/book)
  - Official Symfony book
  - Validation chapter with examples

- [Mastering Symfony](https://masteringsymfony.com/)
  - Advanced Symfony techniques
  - Validation patterns

### Blogs & Tutorials

- [Symfony Casts Blog](https://symfonycasts.com/blog)
  - Tutorials and tips
  - Code examples

- [KNP University](https://knpuniversity.com/)
  - Video courses
  - Symfony tutorials

### Stack Overflow

- [Symfony Validation Questions](https://stackoverflow.com/questions/tagged/symfony+validation)
  - Community Q&A
  - Common problems and solutions

---

## Cheat Sheets

### Quick Reference

- [Symfony Validation Cheat Sheet](https://symfony.com/doc/current/validation.html#quick-reference)
  - Common constraints
  - Usage patterns

### Code Examples

- [Symfony Demo Application](https://github.com/symfony/demo)
  - Real-world validation examples
  - Best practices implementation

- [Symfony Recipes](https://github.com/symfony/recipes)
  - Configuration examples
  - Integration patterns

---

## Tools & Utilities

### Debug Tools

- [Symfony Profiler](https://symfony.com/doc/current/profiler.html)
  - Validation debugging
  - Performance analysis

- [Debug Commands](https://symfony.com/doc/current/validation.html#debugging)
  - `debug:validator` - Inspect validation metadata
  - `debug:container` - View validator services

### IDE Support

- [Symfony Plugin for PhpStorm](https://plugins.jetbrains.com/plugin/7219-symfony-support)
  - Constraint autocomplete
  - Validation configuration support

- [Symfony Extension for VS Code](https://marketplace.visualstudio.com/items?itemName=TheNouillet.symfony-vscode)
  - Symfony development support
  - Validation helpers

---

## Standards & Specifications

- [PSR-7: HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
  - Request validation in HTTP context

- [PSR-15: HTTP Server Request Handlers](https://www.php-fig.org/psr/psr-15/)
  - Middleware with validation

- [JSON Schema](https://json-schema.org/)
  - Alternative validation approach
  - API request/response validation

---

## Performance

- [Validation Performance Tips](https://symfony.com/doc/current/validation.html#performance)
  - Metadata caching
  - Group sequences
  - Lazy validation

- [Symfony Performance Best Practices](https://symfony.com/doc/current/performance.html)
  - General optimization
  - Production configuration

---

## Migration Guides

- [Upgrading Validation Configuration](https://symfony.com/doc/current/setup/upgrade_major.html)
  - Migrating between Symfony versions
  - Deprecated features
  - New features adoption

---

## External Validation Libraries

### Complementary Tools

- [Respect\Validation](https://respect-validation.readthedocs.io/)
  - Alternative validation library
  - Different approach and features

- [Symfony\Component\Validator vs. Respect\Validation](https://stackoverflow.com/questions/tagged/respect-validation)
  - Comparison and use cases

### JavaScript Validation

- [Symfony UX](https://symfony.com/doc/current/frontend/ux.html)
  - Client-side validation
  - Form enhancements

- [Stimulus Validation](https://stimulus-validation.netlify.app/)
  - JavaScript validation with Stimulus
  - Integration with Symfony

---

## API Documentation

- [Validator Component API Documentation](https://api.symfony.com/master/Symfony/Component/Validator.html)
  - Complete API reference
  - Class documentation
  - Method signatures

- [Constraint API Documentation](https://api.symfony.com/master/Symfony/Component/Validator/Constraint.html)
  - Base constraint class
  - Inheritance hierarchy

---

## Additional Reading

### Security

- [Validation and Security](https://symfony.com/doc/current/security.html)
  - Security implications
  - Input sanitization vs. validation

- [OWASP Input Validation](https://owasp.org/www-project-proactive-controls/v3/en/c5-validate-inputs)
  - Security best practices
  - Validation strategies

### Architecture

- [Domain-Driven Design with Symfony](https://symfony.com/doc/current/best_practices.html)
  - Validation in DDD
  - Entity vs. DTO validation

- [Clean Architecture and Validation](https://blog.cleancoder.com/)
  - Architectural patterns
  - Validation layer placement

---

## Getting Help

- [Symfony Slack](https://symfony.com/slack)
  - Real-time community support
  - Validation channel

- [Symfony Forums](https://symfony.com/forum)
  - Community discussions
  - Help and support

- [StackOverflow](https://stackoverflow.com/questions/tagged/symfony)
  - Q&A platform
  - Tagged questions

---

## Contributing

- [Contributing to Symfony](https://symfony.com/doc/current/contributing/index.html)
  - How to contribute
  - Code standards
  - Pull request process

- [Validator Component Contributing Guide](https://github.com/symfony/validator/blob/7.2/CONTRIBUTING.md)
  - Component-specific guidelines
  - Adding new constraints
