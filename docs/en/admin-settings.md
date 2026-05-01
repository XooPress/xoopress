# Settings

The settings page at `/admin/settings` allows you to configure your site.

## Available Settings

| Setting | Description |
|---------|-------------|
| **Site Name** | The name displayed in the site header and browser title |
| **Site Description** | A short tagline displayed below the site name |
| **Site URL** | The public URL of your site |

## Saving Settings

1. Navigate to `/admin/settings`
2. Modify the fields
3. Click **Save**

Settings are stored in the `xp_settings` database table with the `autoload` flag, so they are loaded on every page request without additional queries.