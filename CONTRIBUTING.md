# Contributing to Laravel CA CRL

Thank you for considering contributing to this package. Please read the following guidelines before submitting a pull request.

## Prerequisites

- PHP 8.4+
- Composer 2
- Git

## Setup

```bash
git clone https://github.com/groupesti/laravel-ca-crl.git
cd laravel-ca-crl
composer install
```

## Branching Strategy

- `main` — stable, release-ready code.
- `develop` — work-in-progress for the next release.
- `feat/` — new features (branch from `develop`).
- `fix/` — bug fixes (branch from `develop`).
- `docs/` — documentation-only changes.

## Coding Standards

This project uses [Laravel Pint](https://laravel.com/docs/pint) with the `@laravel` ruleset and [PHPStan](https://phpstan.org/) at level 9.

```bash
# Format code
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse
```

All code must pass both tools with zero errors before a PR can be merged.

## Tests

Tests are written with [Pest 3](https://pestphp.com/). A minimum coverage of 80% is required.

```bash
# Run tests
./vendor/bin/pest

# Run tests with coverage
./vendor/bin/pest --coverage --min=80
```

## Commit Messages

Follow the [Conventional Commits](https://www.conventionalcommits.org/) specification:

- `feat:` — a new feature
- `fix:` — a bug fix
- `docs:` — documentation changes only
- `chore:` — maintenance tasks (CI, dependencies, etc.)
- `refactor:` — code restructuring without behavior change
- `test:` — adding or updating tests

Examples:

```
feat: add delta CRL generation support
fix: correct CRL number increment for concurrent requests
docs: update configuration section in README
```

## Pull Request Process

1. Fork the repository.
2. Create a feature or fix branch from `develop`.
3. Write or update tests for your changes.
4. Ensure all checks pass: Pest, Pint, PHPStan.
5. Update `CHANGELOG.md` under the `[Unreleased]` section.
6. Update `README.md` if the public API changes.
7. Submit a PR to `develop` using the pull request template.

## PHP 8.4 Specifics

When contributing, use PHP 8.4 features where appropriate:

- `readonly` classes and properties for DTOs and value objects.
- Property hooks and asymmetric visibility when they improve clarity.
- Named arguments in function calls for better readability.
- Backed enums (`string` or `int`) instead of class constants.
- Strict type declarations (`declare(strict_types=1)`) in every file.

## Code of Conduct

This project follows the [Contributor Covenant Code of Conduct](CODE_OF_CONDUCT.md). By participating, you agree to uphold this code.
