Here is the complete list of XooPress **Roles** and **Core Capabilities** based on `app/Core/Capabilities.php`:

---

## XooPress Roles (5 default + 1 alias)

| Role | Slug | Description |
|------|------|-------------|
| **Administrator** | `administrator` | Full access — all capabilities |
| *Admin (alias)* | `admin` | Alias for administrator (for compatibility) |
| **Editor** | `editor` | Can manage all posts, pages, users, categories, tags, blocks, widgets, menus, and review/approve posts |
| **Author** | `author` | Can publish and manage their own posts, upload files, access admin |
| **Contributor** | `contributor` | Can write and edit their own posts but cannot publish |
| **Subscriber** | `subscriber` | Can only read content and access admin dashboard |

---

## Core Capabilities (39 total)

### Posts
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `read` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `read_post` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `edit_post` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `edit_posts` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `edit_others_posts` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_private_posts` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_published_posts` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `publish_posts` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `delete_post` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `delete_posts` | ✅ | ✅ | ✅ | ✅ | ❌ |
| `delete_others_posts` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_private_posts` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_published_posts` | ✅ | ✅ | ✅ | ❌ | ❌ |

### Pages
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `read_page` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_page` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_others_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_private_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_published_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `publish_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_page` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_others_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_private_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_published_pages` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Media
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `upload_files` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `edit_files` | ✅ | ✅ | ✅ | ❌ | ❌ |
| `delete_files` | ✅ | ✅ | ✅ | ❌ | ❌ |

### Users
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `list_users` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `create_users` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `edit_users` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `delete_users` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `promote_users` | ✅ | ❌ | ❌ | ❌ | ❌ |

### Categories & Tags
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `manage_categories` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_tags` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Modules & Themes
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `install_modules` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `activate_modules` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `deactivate_modules` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `install_themes` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `switch_themes` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `edit_themes` | ✅ | ❌ | ❌ | ❌ | ❌ |

### Settings & Administration
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `manage_settings` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_options` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `access_admin` | ✅ | ✅ | ✅ | ✅ | ✅ |
| `access_dashboard` | ✅ | ✅ | ✅ | ✅ | ❌ |

### Content Blocks, Widgets & Menus
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `manage_blocks` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_widgets` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `manage_menus` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Workflow
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `review_posts` | ✅ | ✅ | ❌ | ❌ | ❌ |
| `approve_posts` | ✅ | ✅ | ❌ | ❌ | ❌ |

### Webhooks
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `manage_webhooks` | ✅ | ❌ | ❌ | ❌ | ❌ |

### Multisite
| Capability | Admin | Editor | Author | Contributor | Subscriber |
|---|---|---|---|---|---|
| `manage_network` | ✅ | ❌ | ❌ | ❌ | ❌ |
| `manage_sites` | ✅ | ❌ | ❌ | ❌ | ❌ |

---

**Note:** The `administrator` role has **all** capabilities implicitly — the system grants it full access without needing to enumerate individual capabilities. This is handled by a special check in `userCan()`.
