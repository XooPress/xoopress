# XooPress Documentation

> **XooPress** — A modular open-source Content Management System combining the modular architecture of XOOPS with the theming paradigm of WordPress.

---

## 📖 User Documentation

| Language | Directory |
|----------|----------|
| 🇬🇧 English | [`docs/en/`](./en/) |
| 🇩🇪 Deutsch | [`docs/de/`](./de/) |
| 🇫🇷 Français | [`docs/fr/`](./fr/) |

### Getting Started
- [Installation Guide](./en/installation.md) — System requirements, setup, first run
- [Configuration](./en/configuration.md) — Database, modules, i18n, session settings
- [Upgrading](./en/upgrading.md) — How to update XooPress

### Admin Guide
- [Dashboard Overview](./en/admin-dashboard.md) — Navigating the admin panel
- [Managing Posts](./en/admin-posts.md) — Create, edit, publish, delete posts
- [Managing Pages](./en/admin-pages.md) — Static pages management
- [Managing Categories](./en/admin-categories.md) — Organize content with categories
- [Managing Users](./en/admin-users.md) — User roles, permissions, profiles
- [Module Management](./en/admin-modules.md) — Install, activate, deactivate, uninstall modules
- [Theme Management](./en/admin-themes.md) — Activate, upload, delete themes
- [Settings](./en/admin-settings.md) — Site name, description, locale, advanced options

### User Guide
- [Front-end Navigation](./en/user-navigation.md) — Browsing the public site
- [Language Switching](./en/user-language.md) — Changing site language
- [Registration & Login](./en/user-auth.md) — Creating an account, logging in

---

## 🛠 Developer Documentation

### Architecture
- [Project Summary](./project-summary.md) — Full architecture overview, design decisions, roadmap
- [Core Classes](./en/dev-core.md) — Application, Container, Router, Database, I18n, Validator
- [Module System](./en/dev-modules.md) — Module structure, definition file, lifecycle callbacks
- [Theme System](./en/dev-themes.md) — Theme structure, style.css headers, child themes, template hierarchy
- [Routing](./en/dev-routing.md) — Route patterns, middleware, controller resolution
- [Database](./en/dev-database.md) — Query builder, migrations, table prefix

### Module Development
- [Creating a Module](./en/dev-module-create.md) — Step-by-step module creation
- [Module Definition Reference](./en/dev-module-definition.md) — All module.php options
- [Module Services](./en/dev-module-services.md) — Registering and using services
- [Module Routes](./en/dev-module-routes.md) — Adding routes from modules
- [Module Translations](./en/dev-module-i18n.md) — Localizing your module
- [Module Hooks](./en/dev-module-hooks.md) — Extending other modules

### Theme Development
- [Creating a Theme](./en/dev-theme-create.md) — Step-by-step theme creation
- [Template Hierarchy](./en/dev-theme-templates.md) — How templates are resolved
- [Template Parts](./en/dev-theme-parts.md) — Header, footer, sidebar, custom parts
- [Theme Functions](./en/dev-theme-functions.md) — Using functions.php
- [Theme Customizer](./en/dev-theme-customizer.md) — Adding theme options
- [Child Themes](./en/dev-theme-child.md) — Creating and using child themes
- [Theme.json Reference](./en/dev-theme-json.md) — Global styles configuration

### API Reference
- [Global Functions](./en/dev-api-global.md) — `__()`, helpers
- [Controller Methods](./en/dev-api-controller.md) — `view()`, `json()`, `redirect()`, `validate()`
- [Container API](./en/dev-api-container.md) — `get()`, `has()`, `bind()`, `singleton()`, `instance()`
- [Database API](./en/dev-api-database.md) — `select()`, `insert()`, `update()`, `delete()`
- [Router API](./en/dev-api-router.md) — `addRoute()`, `dispatch()`, URL generation
- [I18n API](./en/dev-api-i18n.md) — `translate()`, `translatePlural()`, locale detection
- [ModuleManager API](./en/dev-api-modules.md) — `install()`, `uninstall()`, `activate()`, `deactivate()`
- [ThemeManager API](./en/dev-api-theme.md) — `render()`, `getHeader()`, `getFooter()`, `getTemplatePart()`

### Contributing
- [Development Setup](./en/dev-setup.md) — Local environment, Docker, VS Code config
- [Coding Standards](./en/dev-standards.md) — PSR-12, naming conventions, file structure
- [Testing](./en/dev-testing.md) — PHPUnit setup, writing tests, running test suite
- [Pull Request Guide](./en/dev-pr.md) — PR workflow, review process, merge criteria
- [Translation Guide](./en/dev-translations.md) — Adding new locales, .mo file generation

---

## 📁 Documentation Structure

```
docs/
├── README.md              ← This file (TOC)
├── project-summary.md     ← Architecture overview & roadmap
├── en/                    ← English documentation
│   ├── installation.md
│   ├── configuration.md
│   ├── upgrading.md
│   ├── admin-dashboard.md
│   ├── admin-posts.md
│   ├── admin-pages.md
│   ├── admin-categories.md
│   ├── admin-users.md
│   ├── admin-modules.md
│   ├── admin-themes.md
│   ├── admin-settings.md
│   ├── user-navigation.md
│   ├── user-language.md
│   ├── user-auth.md
│   ├── dev-core.md
│   ├── dev-modules.md
│   ├── dev-themes.md
│   ├── dev-routing.md
│   ├── dev-database.md
│   ├── dev-module-create.md
│   ├── dev-module-definition.md
│   ├── dev-module-services.md
│   ├── dev-module-routes.md
│   ├── dev-module-i18n.md
│   ├── dev-module-hooks.md
│   ├── dev-theme-create.md
│   ├── dev-theme-templates.md
│   ├── dev-theme-parts.md
│   ├── dev-theme-functions.md
│   ├── dev-theme-customizer.md
│   ├── dev-theme-child.md
│   ├── dev-theme-json.md
│   ├── dev-api-global.md
│   ├── dev-api-controller.md
│   ├── dev-api-container.md
│   ├── dev-api-database.md
│   ├── dev-api-router.md
│   ├── dev-api-i18n.md
│   ├── dev-api-modules.md
│   ├── dev-api-theme.md
│   ├── dev-setup.md
│   ├── dev-standards.md
│   ├── dev-testing.md
│   ├── dev-pr.md
│   └── dev-translations.md
├── de/                    ← German documentation (same structure)
│   └── ...
└── fr/                    ← French documentation (same structure)
    └── ...
```

---

## 🌐 Translation Status

| Language | Progress | Contributors Needed |
|----------|----------|-------------------|
| English  | ✅ Planned | — |
| German   | ⬜ Not started | ✅ |
| French   | ⬜ Not started | ✅ |

---

*Last updated: 2026-01-05*