# Contributing to XooPress

Thank you for considering contributing to XooPress! We welcome contributions from everyone.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for all contributors.

## How to Contribute

### Reporting Bugs

1. **Search existing issues** — Check if the bug has already been reported.
2. **Create a detailed report** — Include:
   - PHP version
   - Server environment (Apache/Nginx, OS)
   - Database type and version
   - Steps to reproduce
   - Expected vs actual behavior
   - Error messages or stack traces

### Suggesting Features

1. **Open an issue** with the `enhancement` label
2. **Describe the feature** clearly and explain why it would benefit XooPress
3. **Include use cases** and potential implementation approaches

### Pull Requests

1. **Fork the repository**
2. **Create a feature branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Follow coding standards** (see below)
4. **Write tests** for new functionality
5. **Update documentation** as needed (see [Documentation](#documentation) below)
6. **Run syntax checks**:
   ```bash
   find . -path "./vendor" -prune -o -name "*.php" -print -exec php -l {} \; 2>&1 | grep -E "Parse error|Fatal error"
   ```
7. **Commit** with clear, descriptive messages:
   ```
   feat: add user authentication middleware
   fix: resolve database connection timeout
   docs: update module creation guide
   ```
8. **Push** and open a pull request

## Development Setup

```bash
git clone https://github.com/XooPress/xoopress.git
cd xoopress
composer install
cp config/app.php config/app.local.php  # Create local config
# Edit config/app.local.php with your database settings
```

### Running Tests

XooPress uses PHPUnit for testing:

```bash
./vendor/bin/phpunit
```

## Coding Standards

### PHP

- **PHP 8.2+** — Use typed properties, named arguments, match expressions, readonly classes where appropriate
- **PSR-4** autoloading — Namespace must match directory structure
- **PSR-12** coding style — Follow PSR-12 with the following additions:
  - Use `declare(strict_types=1)` in all PHP files
  - Use `|` for union types: `int|string`
  - Use `?` for nullable types: `?string`
  - Add docblocks for all classes, methods, and properties

### Naming Conventions

| Element | Convention | Example |
|---------|-----------|---------|
| Classes | PascalCase | `UserController` |
| Methods | camelCase | `getPublished()` |
| Properties | camelCase | `$tableName` |
| Constants | UPPER_SNAKE_CASE | `XOO_PRESS_VERSION` |
| Variables | camelCase | `$userName` |
| Files | PascalCase | `ModuleManager.php` |

### Directory Structure

When creating a new module:

```
modules/YourModule/
├── Controllers/     # HTTP controllers
├── Models/          # Database models
├── views/           # PHP view templates
├── locales/         # Translations (gettext .po/.mo files)
└── module.php       # Module definition (required)
```

### Database

- Use the table prefix handler: `$db->getPrefix()`
- Use MySQL InnoDB engine with utf8mb4 charset
- Add indexes for foreign keys and frequently queried columns
- Always use prepared statements (never concatenate SQL)

### Security

- Escape all output with `htmlspecialchars()`
- Use prepared statements for all database queries
- Validate and sanitize all user input
- Use CSRF tokens for form submissions
- Hash passwords with `PASSWORD_BCRYPT`

## Documentation

XooPress documentation lives in the [`docs/`](docs/) directory:

```
docs/
├── README.md              # Table of contents
├── project-summary.md     # Architecture overview & roadmap
├── en/                    # English documentation
├── de/                    # German (in progress)
└── fr/                    # French (in progress)
```

### Documentation Structure

- **User Documentation** — Installation, configuration, admin guides, user guides
- **Developer Documentation** — Architecture, module/theme development, API reference, contributing
- **Translations** — Each language has its own subdirectory (`en/`, `de/`, `fr/`)

### Contributing to Documentation

1. **Fix existing docs** — Improve clarity, fix typos, update outdated information
2. **Add new docs** — Follow the existing structure in the appropriate language directory
3. **Translate** — Help translate docs into German (`docs/de/`) or French (`docs/fr/`)
4. **Update the TOC** — If you add new files, update `docs/README.md` to include them

### Documentation Style

- Use clear, concise language
- Include code examples where relevant
- Use tables for structured information
- Link to related documentation pages
- Keep line lengths reasonable for readability

## Module Development Guidelines

1. **Module definition** (`module.php`) must include `name`, `version`, and `description`
2. **Use semantic versioning** (MAJOR.MINOR.PATCH)
3. **Declare dependencies** explicitly in the `dependencies` array
4. **Implement install/uninstall** callbacks for database schema management
5. **Register routes** in the module definition or in a `routes.php` file
6. **Use the container** for service registration and dependency injection

## Commit Messages

Follow conventional commits:

- `feat:` — New feature
- `fix:` — Bug fix
- `docs:` — Documentation
- `refactor:` — Code refactoring
- `test:` — Adding or updating tests
- `chore:` — Maintenance tasks

## Review Process

1. All pull requests require at least one review
2. CI checks must pass (syntax, tests)
3. No new warnings or errors introduced
4. Documentation updated if public API changes

## Questions?

Open a discussion or issue on GitHub. We're happy to help!

---

**XooPress** — Built with ♥ and PHP 8.2+