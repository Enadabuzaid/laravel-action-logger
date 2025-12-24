# Contributing to Laravel Action Logger

Thank you for considering contributing to Laravel Action Logger! We welcome contributions from the community.

## Code of Conduct

Please be respectful and constructive in all interactions. We aim to foster an open and welcoming environment.

## How to Contribute

### Reporting Bugs

1. **Search existing issues** to avoid duplicates
2. **Use the bug report template** when creating a new issue
3. Include:
   - PHP and Laravel versions
   - Package version
   - Steps to reproduce
   - Expected vs actual behavior
   - Relevant code snippets

### Suggesting Features

1. **Open a discussion** before creating a feature request
2. Explain the use case and why it would be valuable
3. Consider if it aligns with the package's scope

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch**: `git checkout -b feature/my-feature`
3. **Make your changes**
4. **Write tests** for new functionality
5. **Run the test suite**: `composer test`
6. **Format code**: `composer format`
7. **Submit your pull request**

## Development Setup

### Requirements

- PHP 8.2+
- Composer

### Installation

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/laravel-action-logger.git
cd laravel-action-logger

# Install dependencies
composer install
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
vendor/bin/phpunit --coverage-html coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/ActionLoggerServiceTest.php
```

### Code Style

We use Laravel Pint for code formatting:

```bash
# Check formatting
vendor/bin/pint --test

# Fix formatting
composer format
```

## Coding Standards

### PHP

- Follow PSR-12 coding standards
- Use strict types: `declare(strict_types=1);`
- Add PHPDoc blocks for all public methods
- Use type hints for parameters and return types

### Example Method

```php
/**
 * Log an action for a model.
 *
 * @param object $subject The model being logged
 * @param string $event The event name
 * @return ActionLog|null The created log, or null if disabled
 */
public function log(object $subject, string $event): ?ActionLog
{
    // Implementation
}
```

### Tests

- Write tests for all new functionality
- Name test methods clearly: `test_it_creates_log_when_model_created`
- Use data providers for testing multiple scenarios
- Mock external dependencies

### Example Test

```php
public function test_it_logs_create_event(): void
{
    $post = Post::create(['title' => 'Test']);

    $this->assertDatabaseHas('action_logs', [
        'subject_type' => Post::class,
        'subject_id' => $post->id,
        'event' => 'created',
    ]);
}
```

## Commit Messages

Use clear, descriptive commit messages:

```
feat: add custom tenant resolver support
fix: resolve null pointer in actor resolution
docs: update installation instructions
test: add tests for date range scope
refactor: simplify fluent API implementation
```

Prefixes:
- `feat:` New feature
- `fix:` Bug fix
- `docs:` Documentation changes
- `test:` Test changes
- `refactor:` Code refactoring
- `chore:` Build/tooling changes

## Pull Request Guidelines

1. **Title**: Use a clear, descriptive title
2. **Description**: Explain what the PR does and why
3. **Tests**: Include tests for new functionality
4. **Documentation**: Update docs if needed
5. **Breaking changes**: Clearly mark and explain

### PR Checklist

- [ ] Tests pass locally: `composer test`
- [ ] Code is formatted: `composer format`
- [ ] Documentation is updated
- [ ] CHANGELOG is updated for significant changes
- [ ] No breaking changes (or clearly documented)

## Release Process

Releases are managed by maintainers:

1. Update CHANGELOG.md
2. Bump version in composer.json (if applicable)
3. Create a git tag: `git tag v1.0.0`
4. Push tag: `git push origin v1.0.0`
5. Packagist auto-updates from GitHub

## Questions?

- Open a [GitHub Discussion](https://github.com/enadstack/laravel-action-logger/discussions)
- Email: enad@enadstack.com

Thank you for contributing! 🙏
