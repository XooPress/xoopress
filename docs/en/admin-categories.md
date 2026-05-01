# Managing Categories

Categories help organize your content into logical groups.

## Listing Categories

Navigate to `/admin/categories` to see all categories. The table shows:

- **Name** — Category name
- **Slug** — URL-friendly identifier
- **Description** — Category description
- **Actions** — Delete link

## Creating a Category

1. Go to `/admin/categories`
2. Fill in the form:

| Field | Description |
|-------|-------------|
| **Name** | Display name (required) |
| **Slug** | URL-friendly version (auto-generated if left empty) |
| **Description** | Optional description |

3. Click **Add New Category**

## Deleting a Category

1. Go to `/admin/categories`
2. Click **Delete** on the category you want to remove
3. Posts assigned to this category will have their `category_id` set to NULL

## Default Category

A default "Uncategorized" category is created during installation. It can be renamed or deleted.