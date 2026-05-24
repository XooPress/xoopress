<?php
/**
 * Webhooks Listing View
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $webhooks */
/** @var array $events */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Webhooks - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .event-grid { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; }
        .event-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; background: #f0f0f1; font-size: 11px; color: #646970; }
        .event-badge.active { background: #e5f5fa; color: #0073aa; font-weight: 600; }
        .wh-actions { white-space: nowrap; }
        .wh-actions a { margin-right: 6px; font-size: 12px; }
        .url-cell { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .active-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; }
        .active-badge.yes { background: #ecf7ed; color: #46b450; }
        .active-badge.no { background: #fbeaea; color: #dc3232; }
        .btn-primary { display: inline-block; padding: 6px 16px; background: #2271b1; color: #fff; border-radius: 3px; text-decoration: none; font-size: 13px; margin-bottom: 16px; }
        .btn-primary:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;">
        <h1>🔔 Webhooks</h1>
        <a class="btn-primary" href="/admin/webhooks/new">+ Add New</a>
    </div>

    <p style="font-size:13px;color:#646970;margin-bottom:16px;">
        Available events:
        <?php foreach ($events as $ev): ?>
        <span class="event-badge"><?php echo htmlspecialchars($ev); ?></span>
        <?php endforeach; ?>
    </p>

    <?php if (!empty($webhooks)): ?>
    <table class="admin-table">
        <thead>
            <tr><th>Event</th><th>URL</th><th>Status</th><th>Timeout</th><th>Last Triggered</th><th>Actions</th></tr>
        </thead>
        <tbody>
            <?php foreach ($webhooks as $wh): ?>
            <tr>
                <td><span class="event-badge active"><?php echo htmlspecialchars($wh['event']); ?></span></td>
                <td class="url-cell" title="<?php echo htmlspecialchars($wh['url']); ?>"><?php echo htmlspecialchars($wh['url']); ?></td>
                <td>
                    <?php if (!empty($wh['is_active'])): ?>
                    <span class="active-badge yes">Active</span>
                    <?php else: ?>
                    <span class="active-badge no">Inactive</span>
                    <?php endif; ?>
                </td>
                <td><?php echo (int)($wh['timeout'] ?? 5); ?>s</td>
                <td><?php echo htmlspecialchars($wh['last_triggered_at'] ?? '—'); ?></td>
                <td class="wh-actions">
                    <a href="/admin/webhooks/edit/<?php echo (int)$wh['id']; ?>">Edit</a>
                    <a href="/admin/webhooks/test/<?php echo (int)$wh['id']; ?>" onclick="return confirm('Send a test payload?');">Test</a>
                    <a href="/admin/webhooks/delete/<?php echo (int)$wh['id']; ?>" onclick="return confirm('Delete this webhook?');" style="color:#dc3232;">Delete</a>
                </td>
            </tr>
            <?php if (!empty($wh['description'])): ?>
            <tr><td colspan="6" style="font-size:12px;color:#646970;padding-top:0;"><?php echo htmlspecialchars($wh['description']); ?></td></tr>
            <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div style="text-align:center;padding:48px 20px;color:#646970;">
        <div style="font-size:48px;margin-bottom:12px;">🔔</div>
        <p>No webhooks configured yet.</p>
        <p style="font-size:13px;">Webhooks send HTTP POST requests to external URLs when specific events occur.</p>
        <a class="btn-primary" href="/admin/webhooks/new">Create Your First Webhook</a>
    </div>
    <?php endif; ?>
</div>
</body>
</html>