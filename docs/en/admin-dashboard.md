# Dashboard Overview

The admin dashboard is the central hub for managing your XooPress site. Access it at `/admin` after logging in.

## Navigation

The admin navigation menu provides access to all management sections:

| Menu Item | Description |
|-----------|-------------|
| **Dashboard** | Overview page with site stats |
| **Posts** | Manage blog posts |
| **Pages** | Manage static pages |
| **Categories** | Organize content |
| **Users** | Manage user accounts |
| **Modules** | Install/activate/deactivate modules |
| **Themes** | Activate/upload/delete themes |
| **Settings** | Site configuration |
| **View Site** | Open the public site in a new tab |
| **Logout** | End your session |

## Dashboard Widgets

The dashboard displays:

- **Site Information** — XooPress version, PHP version
- **Module List** — All installed modules with versions (System, Content)
- **User Count** — Total registered users
- **Quick Links** — Common admin tasks

## Admin URL Structure

All admin pages follow the pattern `/admin/{section}` with sub-pages at `/admin/{section}/{action}/{id}`.

Example:
- `/admin/posts` — List all posts
- `/admin/posts/new` — Create new post
- `/admin/posts/edit/1` — Edit post with ID 1
- `/admin/posts/delete/1` — Delete post with ID 1

## Role-Based Access

The navigation menu adapts to the user's role:

| Role | Access |
|------|--------|
| **Admin** | Full menu — all sections visible |
| **Editor** | Posts, Pages, Categories, Dashboard |
| **Author** | Posts (own only), Dashboard |
| **Subscriber** | Dashboard only (limited info) |