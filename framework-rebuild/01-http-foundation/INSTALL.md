# Installation and Setup Guide

## Prerequisites

Before starting, ensure you have:

- **PHP 8.2 or higher**
- **Composer** (dependency manager)
- **Git** (optional, for version control)

### Check PHP Version

```bash
php -v
```

Expected output (version should be 8.2 or higher):
```
PHP 8.2.x (cli) (built: ...)
```

### Check Composer

```bash
composer --version
```

Expected output:
```
Composer version 2.x.x
```

If you don't have Composer, install it from [getcomposer.org](https://getcomposer.org/).

## Installation Steps

### Step 1: Navigate to the Chapter Directory

```bash
cd /home/ungvsn/symfony-educational-project/framework-rebuild/01-http-foundation
```

### Step 2: Install Dependencies

```bash
composer install
```

This will:
- Create a `vendor/` directory
- Install PHPUnit and its dependencies
- Set up autoloading for PSR-4

Expected output:
```
Installing dependencies from lock file (or creating lock file)
...
Generating autoload files
```

### Step 3: Verify Installation

Run the test suite to ensure everything is working:

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

Expected output:
```
PHPUnit 10.x.x by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.x
Configuration: /path/to/phpunit.xml

.......................................................     55 / 55 (100%)

Time: 00:00.123, Memory: 10.00 MB

OK (55 tests, 200+ assertions)
```

### Step 4: Run Examples

Test one of the examples:

```bash
php examples/01-basic-usage.php
```

Expected output:
```
=== Request Information ===
Method: POST
Path: /users
Full URI: http://example.com/users?page=2&sort=name
Client IP: 192.168.1.100
...
```

## Troubleshooting

### Issue: "PHP version requirement not met"

**Error Message:**
```
Your requirements could not be resolved to an installable set of packages.
Problem: Root composer.json requires php >=8.2 but your php version is X.X.X
```

**Solution:**
1. Upgrade PHP to version 8.2 or higher
2. Or modify `composer.json` to lower the requirement (not recommended for learning)

### Issue: "Class not found"

**Error Message:**
```
Fatal error: Uncaught Error: Class 'FrameworkRebuild\HttpFoundation\Request' not found
```

**Solution:**
1. Make sure you ran `composer install`
2. Verify `vendor/autoload.php` exists
3. Check that you're including the autoloader:
   ```php
   require_once __DIR__ . '/../vendor/autoload.php';
   ```

### Issue: PHPUnit not found

**Error Message:**
```
bash: phpunit: command not found
```

**Solution:**
Use Composer's PHPUnit:
```bash
vendor/bin/phpunit
```

Or use the composer script:
```bash
composer test
```

### Issue: Permission denied

**Error Message:**
```
Permission denied: vendor/bin/phpunit
```

**Solution:**
Make the file executable:
```bash
chmod +x vendor/bin/phpunit
```

### Issue: Tests failing

**Possible Causes:**
1. **PHP version mismatch**: Ensure PHP 8.2+
2. **Missing dependencies**: Run `composer install` again
3. **Code changes**: Verify source files weren't modified

**Solution:**
```bash
# Clean install
rm -rf vendor composer.lock
composer install
composer test
```

## File Permissions

Ensure proper permissions (on Linux/Mac):

```bash
# Make scripts executable
chmod +x examples/*.php

# Ensure vendor directory is readable
chmod -R 755 vendor/
```

## Directory Structure Verification

After installation, your directory should look like this:

```
01-http-foundation/
├── vendor/              ← Created by composer install
│   ├── autoload.php
│   ├── bin/
│   │   └── phpunit      ← PHPUnit executable
│   ├── composer/
│   └── phpunit/
├── composer.lock        ← Created by composer install
├── src/
├── tests/
├── examples/
├── composer.json
├── phpunit.xml
└── README.md
```

## Running the Full Test Suite

### Basic Test Run

```bash
composer test
```

### Verbose Output

```bash
vendor/bin/phpunit --testdox
```

Expected output with test names:
```
Request
 ✔ Constructor initializes parameter bags
 ✔ Get method
 ✔ Get method with override
 ✔ Is method
 ...

Response
 ✔ Constructor sets defaults
 ✔ Set content
 ✔ Set status code
 ...

ParameterBag
 ✔ Constructor with empty array
 ✔ Get
 ✔ Get with default
 ...
```

### With Code Coverage (requires Xdebug)

```bash
composer test-coverage
```

This creates an HTML coverage report in `coverage/index.html`.

## IDE Setup

### PhpStorm

1. Open the project directory
2. Right-click on `src/` → Mark Directory as → Sources Root
3. Right-click on `tests/` → Mark Directory as → Test Sources Root
4. Go to Settings → PHP → Test Frameworks → Add PHPUnit
5. Select "Use Composer autoloader"
6. Path: `vendor/autoload.php`

### VS Code

Install these extensions:
1. **PHP Intelephense** - Code completion
2. **PHP Unit Test Explorer** - Run tests from sidebar

Create `.vscode/settings.json`:
```json
{
    "php.suggest.basic": false,
    "intelephense.files.exclude": [
        "**/vendor/**"
    ]
}
```

## Performance Optimization

### Optimize Composer Autoloader

For faster autoloading in production:

```bash
composer dump-autoload --optimize
```

### Disable Xdebug (if not needed)

Xdebug can slow down tests significantly. Disable it when not needed:

```bash
# Check if Xdebug is enabled
php -v | grep -i xdebug

# Disable Xdebug (method varies by installation)
php -d xdebug.mode=off vendor/bin/phpunit
```

## Verification Checklist

After installation, verify everything works:

- [ ] `composer install` completed successfully
- [ ] `vendor/` directory exists
- [ ] `vendor/bin/phpunit` exists and is executable
- [ ] `composer test` runs and all tests pass
- [ ] `php examples/01-basic-usage.php` runs without errors
- [ ] No "Class not found" errors
- [ ] No permission errors

## Next Steps

Once installation is complete and verified:

1. **Read the documentation**:
   - [INDEX.md](INDEX.md) - Complete chapter index
   - [GETTING-STARTED.md](GETTING-STARTED.md) - Quick start
   - [README.md](README.md) - Full documentation

2. **Run all examples**:
   ```bash
   for file in examples/*.php; do echo "Running $file"; php "$file"; echo; done
   ```

3. **Study the code**:
   - Start with `src/ParameterBag.php`
   - Then `src/Request.php`
   - Finally `src/Response.php`

4. **Read the tests**:
   - See how components are tested
   - Learn testing best practices

5. **Do the exercises**:
   - Work through exercises in README.md
   - Build something on your own

## Getting Help

If you encounter issues:

1. **Check the documentation**: Most questions are answered in README.md
2. **Read error messages carefully**: They usually indicate the exact problem
3. **Verify prerequisites**: Ensure PHP 8.2+ and Composer are installed
4. **Clean reinstall**: Delete vendor/ and run `composer install` again
5. **Check file permissions**: Ensure files are readable/executable

## Additional Resources

- [Composer Documentation](https://getcomposer.org/doc/)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [PHP 8.2 Documentation](https://www.php.net/manual/en/)
- [PSR-4 Autoloading Standard](https://www.php-fig.org/psr/psr-4/)

## System Requirements Summary

| Requirement | Minimum | Recommended |
|------------|---------|-------------|
| PHP | 8.2 | 8.3+ |
| Composer | 2.0 | Latest |
| Memory | 128 MB | 256 MB |
| Disk Space | 20 MB | 50 MB |

Happy learning!
