# Webhooks

XooPress provides an event-driven webhook system that sends HTTP callbacks when specific events occur.

## Supported Events

| Event | Description | Trigger |
|-------|-------------|---------|
| `post_published` | A post is published | Post status changes to `published` |
| `post_updated` | A post is updated | Post content or metadata changes |
| `post_deleted` | A post is deleted | Post is permanently removed |
| `user_registered` | A new user registers | New user account created |
| `user_updated` | A user profile is updated | User data changes |
| `module_installed` | A module is installed | Module installation completes |
| `module_uninstalled` | A module is uninstalled | Module uninstallation completes |
| `theme_activated` | A theme is activated | Theme switch occurs |

## Database Schema

Webhooks are stored in the `xp_webhooks` table:

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Unique webhook ID |
| `event` | VARCHAR(100) | Event name (indexed) |
| `url` | VARCHAR(500) | Callback endpoint URL |
| `secret` | VARCHAR(255) | Optional HMAC secret for payload signing |
| `description` | TEXT | Human-readable description |
| `is_active` | TINYINT(1) | Whether this webhook is active (indexed) |
| `timeout` | INT | Request timeout in seconds (default: 5) |
| `retry_count` | INT | Number of retries on failure |
| `last_triggered_at` | DATETIME | When this webhook was last triggered |
| `created_at` | DATETIME | Creation timestamp |
| `updated_at` | DATETIME | Last update timestamp |

## How It Works

### Dispatching

When an event occurs, the `dispatch()` method:
1. Looks up all active webhooks registered for that event
2. Prepares a JSON payload with event details and timestamp
3. Signs the payload with HMAC-SHA256 (using the webhook's secret)
4. Sends all webhooks concurrently using `curl_multi_exec()` for parallel dispatch
5. Updates `last_triggered_at` and `retry_count` on success/failure

### Payload Format

```json
{
  "event": "post_published",
  "timestamp": "2026-05-25T12:00:00Z",
  "data": {
    "post_id": 42,
    "title": "Hello World",
    "slug": "hello-world"
  }
}
```

### Security

- **HMAC-SHA256 signing** — Each payload is signed with the webhook's secret and sent via the `XooPress-Signature` header
- **Verification** — Receiving services can verify the payload by computing the HMAC with the shared secret
- **Timeouts** — Configurable per webhook to prevent slow callbacks from blocking dispatches

## Admin UI

### Managing Webhooks

Access webhook management at `/admin/webhooks`:

- **List** all configured webhooks with event, URL, status, and last triggered time
- **Create** new webhooks by selecting event, entering URL, and optional secret
- **Edit** existing webhook configuration
- **Test** — Send a test payload to verify the endpoint is working
- **Delete** unwanted webhooks

### Creating a Webhook

1. Navigate to `/admin/webhooks`
2. Click **"Add New Webhook"**
3. Select the **Event** from the dropdown (post_published, post_updated, etc.)
4. Enter the **Callback URL** (the endpoint that will receive the POST request)
5. Optionally set a **Secret** for HMAC signing
6. Set the **Timeout** (seconds before the request is considered failed)
7. Toggle **Active** status
8. Save

## API

### Dispatching an Event Programmatically

```php
use XooPress\Core\Webhooks;

$webhooks = $container->get('webhooks');
$webhooks->dispatch('post_published', ['post_id' => 42, 'title' => 'Hello World']);
```

### Registering a Webhook Programmatically

```php
$webhooks->register([
    'event' => 'post_published',
    'url' => 'https://example.com/webhook',
    'secret' => 'your-secret-key',
    'description' => 'Notify external service on publish',
    'is_active' => true,
    'timeout' => 5,
]);
```

## Use Cases

- **CI/CD pipelines** — Trigger deployments when content is published
- **CDN purging** — Purge cache on content updates
- **Analytics** — Send events to analytics platforms
- **Slack/Teams notifications** — Notify team channels of content changes
- **Search index updates** — Trigger external search re-indexing