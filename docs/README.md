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

### Contributing
- [Development Setup](./en/dev-setup.md) — Local environment, Docker, VS Code config

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
│   └── dev-setup.md
├── de/                    ← German documentation (not started)
│   └── .gitkeep
└── fr/                    ← French documentation (not started)
    └── .gitkeep
```

---

## 🌐 Translation Status

| Language | Progress | Contributors Needed |
|----------|----------|-------------------|
| English  | ✅ Complete | — |
| German   | ⬜ Not started | ✅ |
| French   | ⬜ Not started | ✅ |

---

*Last updated: 2026-05-05*