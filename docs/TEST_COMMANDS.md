# 🧪 PHPUnit Test Commands Reference

Quick reference guide for running tests in the Catering API project.

## Basic Test Commands

```bash
# Run all tests
vendor/bin/phpunit

# Run with testdox format (readable output)
vendor/bin/phpunit --testdox

# Run all unit tests
composer test-unit

# Run with verbose output
vendor/bin/phpunit --verbose
```

## Running Specific Tests

```bash
# Run specific test class
vendor/bin/phpunit tests/Unit/Helpers/ValidatorTest.php

# Run specific test method
vendor/bin/phpunit --filter testSanitizeId tests/Unit/Helpers/InputSanitizerTest.php

# Run multiple tests matching a pattern
vendor/bin/phpunit --filter "testCreate|testUpdate"
```

## Running Tests by Category

```bash
# Run tests by test suite
vendor/bin/phpunit --testsuite="Unit Tests"

# Run by directory
vendor/bin/phpunit tests/Unit/Helpers/
vendor/bin/phpunit tests/Unit/Models/
vendor/bin/phpunit tests/Unit/Services/
vendor/bin/phpunit tests/Unit/Controllers/
vendor/bin/phpunit tests/Unit/Middleware/
```

## Test Coverage

```bash
# Generate coverage report (requires Xdebug)
composer test-coverage

# Coverage in HTML format
vendor/bin/phpunit --coverage-html coverage/

# Coverage in text format
vendor/bin/phpunit --coverage-text

# Coverage for specific directory
vendor/bin/phpunit --coverage-html coverage/ tests/Unit/Services/
```

## Debugging Tests

```bash
# Stop on first failure
vendor/bin/phpunit --stop-on-failure

# Stop on first error
vendor/bin/phpunit --stop-on-error

# Display extra output
vendor/bin/phpunit --debug

# Don't show progress
vendor/bin/phpunit --no-progress
```

## Test Filtering

```bash
# Run tests from specific group
vendor/bin/phpunit --group database
vendor/bin/phpunit --group slow

# Exclude specific group
vendor/bin/phpunit --exclude-group slow

# Run only risky tests
vendor/bin/phpunit --strict-coverage
```

## Configuration

```bash
# Use specific configuration file
vendor/bin/phpunit -c phpunit.xml

# List available test suites
vendor/bin/phpunit --list-suites

# List available test groups
vendor/bin/phpunit --list-groups
```

## Quick Test Examples

### Example 1: Test a Specific Service
```bash
vendor/bin/phpunit tests/Unit/Services/FacilityServiceTest.php --testdox
```

### Example 2: Test All Controllers
```bash
vendor/bin/phpunit tests/Unit/Controllers/ --testdox
```

### Example 3: Run Failed Tests First
```bash
# PHPUnit automatically runs previously failed tests first
vendor/bin/phpunit
```

### Example 4: Watch for Changes (with entr)
```bash
# Install entr first: sudo apt install entr
find tests/ -name "*.php" | entr vendor/bin/phpunit
```

## Common Composer Scripts

```bash
# Run unit tests
composer test-unit

# Run with coverage (requires xdebug)
composer test-coverage
```

## Test Status Summary

**Current Status: 166/166 tests passing (100%) ✅**

For test writing guide, assertions reference, and detailed strategy, see:
- **[UNIT_TEST_STRATEGY.md](UNIT_TEST_STRATEGY.md)** - Testing strategy and best practices
- **[PHPUnit Documentation](https://phpunit.de/documentation.html)** - Official documentation