# Managing Users

Users are accounts that can log in to the admin panel and manage content.

## Listing Users

Navigate to `/admin/users` to see all users. The table shows:

- **Username** — Login name
- **Email** — Email address
- **Display Name** — Public-facing name
- **Role** — Admin, Editor, Author, or Subscriber
- **Status** — Active, Inactive, or Banned
- **Actions** — Edit and Delete links

## Creating a User

1. Go to `/admin/users/new`
2. Fill in the fields:

| Field | Description |
|-------|-------------|
| **Username** | Login name (required, unique) |
| **Email** | Email address (required, unique) |
| **Display Name** | Public-facing name |
| **Password** | Login password (required for new users) |
| **Role** | Admin, Editor, Author, or Subscriber |
| **Status** | Active, Inactive, or Banned |

3. Click **Save**

## Editing a User

1. Go to `/admin/users`
2. Click the **Edit** link
3. Modify the fields
4. Leave password blank to keep the current password
5. Click **Save**

## User Roles

| Role | Capabilities |
|------|-------------|
| **Admin** | Full access to all admin features |
| **Editor** | Can manage all posts, pages, and categories |
| **Author** | Can create and manage their own posts |
| **Subscriber** | Can log in and manage their profile only |

## Deleting a User

1. Go to `/admin/users`
2. Click **Delete** on the user you want to remove
3. The user is permanently deleted