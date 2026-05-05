# Front-end Navigation

The public-facing site is what visitors see when they navigate to your domain.

## Site Header

The header typically contains:

- **Site Name** — Links to the homepage
- **Site Description** — Tagline below the name
- **Navigation Menu** — Links to Home, Posts, Login/Logout
- **Language Switcher** — Dropdown to change the site language

## Site Content

The main content area displays:

- **Homepage** — Latest published posts or a welcome message
- **Posts Archive** — List of all published posts at `/posts`
- **Single Post** — Full post content at `/posts/{id}` with previous/next post navigation

## Post Pagination

On single post pages (`/posts/{id}`), previous and next post links are displayed at the bottom for easy navigation between posts.

## Site Footer

The footer typically contains copyright information.

## Theme Switching

XooPress supports per-user theme switching via session preference. Depending on the theme configuration, you may be able to select a personal theme preference that overrides the site-wide theme.

## Language Switching

Use the language switcher dropdown in the header to change the site language. Available languages:

- English
- Deutsch (German)
- Français (French)

The language preference is stored in your session.