# Managing Posts

Posts are time-based content entries, typically used for blog articles or news items.

## Listing Posts

Navigate to `/admin/posts` to see all posts. The table shows:

- **Title** — Post title (click to edit)
- **Status** — Draft, Published, Pending, or Trash
- **Author** — Who created the post
- **Date** — Creation or publication date
- **Actions** — Edit and Delete links

> **Note:** Authors can only see and edit their own posts. Editors and Admins can see all posts.

## Creating a Post

1. Go to `/admin/posts/new`
2. Fill in the fields:

| Field | Description |
|-------|-------------|
| **Title** | The post headline (required) |
| **Slug** | URL-friendly version of the title (auto-generated if left empty) |
| **Content** | The main body of the post |
| **Excerpt** | A short summary for listings |
| **Status** | Draft, Published, Pending, or Trash |
| **Category** | Assign to a category |
| **Language** | Content language (en_US, de_DE, fr_FR) |
| **Type** | `post` for blog posts, `page` for static pages |

3. Choose your editor mode (Visual, HTML, Markdown, or PHP)
4. Click **Save**

### Multi-Format Editor

The post editor features a tabbed interface with 4 editor modes:

| Mode | Icon | Description |
|------|------|-------------|
| **Visual** | 🎨 | Contenteditable WYSIWYG with formatting toolbar (B, I, U, H2, H3, blockquote, code, lists, links, images) |
| **HTML** | 🔤 | Code editor with HTML tag insertion helpers |
| **Markdown** | 📝 | Editor with Markdown formatting toolbar (bold, italic, headers, blockquotes, code, links, images, lists) |
| **PHP** | ⚡ | Code editor with PHP snippet helpers (echo, if, foreach, for, function, return) |

**Features:**
- **Live Preview** toggle — renders HTML/WYSIWYG inline, client-side Markdown preview, shows PHP source
- **Auto-sync** — switching tabs syncs content between editors via a hidden textarea
- **Auto-slug** — URL slug auto-generated from title on blur
- **Persistent mode** — the selected editor mode is saved as `content_type` per post

## Editing a Post

1. Go to `/admin/posts`
2. Click the post title or the **Edit** link
3. Modify the fields
4. Click **Save**

## Publishing a Post

Set the status to **Published** and save. The post will appear on the front-end with the current date/time as the publication date.

## Deleting a Post

1. Go to `/admin/posts`
2. Click **Delete** on the post you want to remove
3. The post is permanently deleted (no trash/recycle bin yet)

## Post Statuses

| Status | Description |
|--------|-------------|
| **Draft** | Not yet published, visible only to editors |
| **Published** | Visible on the public site |
| **Pending** | Awaiting review |
| **Trash** | Soft-deleted (not yet implemented — delete is permanent) |

## Role-Based Access

| Role | Can create? | Can edit own? | Can edit others? | Can delete? |
|------|-------------|---------------|------------------|-------------|
| **Admin** | ✅ | ✅ | ✅ | ✅ |
| **Editor** | ✅ | ✅ | ✅ | ✅ |
| **Author** | ✅ | ✅ | ❌ | ❌ |
| **Subscriber** | ❌ | ❌ | ❌ | ❌ |