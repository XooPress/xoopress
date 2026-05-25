# Webhooks (Admin)

Webhooks allow you to send HTTP callbacks to external services when specific events occur in your XooPress site.

## Accessing Webhooks

Navigate to **Admin → Webhooks** (`/admin/webhooks`) to manage webhook configurations.

## Overview

The webhooks page displays a table of all configured webhooks with:
- **Event** — Which event triggers this webhook
- **URL** — The endpoint that receives the callback
- **Status** — Active or Inactive
- **Last Triggered** — When it was last fired
- **Actions** — Edit, Test, Delete

## Managing Webhooks

### Creating a Webhook

1. Click **"Add New Webhook"**
2. **Event** — Select the event that should trigger this webhook:
   - `post_published` — When a post is published
   - `post_updated` — When a post is updated
   - `post_deleted` — When a post is deleted
   - `user_registered` — When a new user registers
   - `user_updated` — When a user profile is updated
   - `module_installed` — When a module is installed
   - `module_uninstalled` — When a module is uninstalled
   - `theme_activated` — When a theme is activated
3. **Callback URL** — The URL that will receive the POST request with the event payload
4. **Secret** (optional) — A shared secret used to sign the payload (HMAC-SHA256)
5. **Timeout** — Time in seconds before the request is considered failed (default: 5)
6. **Active** — Toggle to enable or disable the webhook
7. Click **Save**

### Editing a Webhook

1. Click the **Edit** button next to any webhook
2. Modify the event, URL, secret, or settings
3. Click **Save**

### Testing a Webhook

Click the **Test** button to send a test payload to the webhook URL. This verifies:
- The endpoint is reachable
- The endpoint accepts POST requests
- The HMAC signature is correctly computed

### Deleting a Webhook

1. Click the **Delete** button
2. Confirm the deletion

## Payload Format

When triggered, the webhook sends a JSON POST request:

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

### Headers

```
Content-Type: application/json
XooPress-Signature: sha256=<HMAC-SHA256 of payload body>
```

The receiving service can verify the payload by computing the HMAC using the shared secret and comparing it to the `XooPress-Signature` header.

## Use Cases

- **Slack/Teams notifications** — Get notified when content is published
- **CDN purging** — Automatically purge CDN cache on content updates
- **CI/CD pipelines** — Trigger deployments when content changes
- **External search indexing** — Notify external search services of content updates
- **Analytics** — Send events to custom analytics platforms