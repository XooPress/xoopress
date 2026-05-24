<?php
/**
 * Sites Overview View
 *
 * Lists all registered multisite network sites.
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $sites */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sites - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
<?php include __DIR__ . '/_admin_header.php'; ?>
<div class="wrap">
    <h1>🌐 Network Sites</h1>
    <p class="description">Manage virtual sites on this XooPress installation. Each site runs on its own domain.</p>

    <?php $notice = $_SESSION['admin_notice'] ?? null; $noticeType = $_SESSION['admin_notice_type'] ?? 'info'; unset($_SESSION['admin_notice'], $_SESSION['admin_notice_type']); ?>
    <?php if ($notice): ?>
    <div class="notice notice-<?php echo htmlspecialchars($noticeType); ?>"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <p><a href="/admin/sites/new" class="button button-primary">➕ Add New Site</a></p>

    <?php if (empty($sites)): ?>
    <p>No sites registered yet. The main site runs at the installation domain.</p>
    <?php else: ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Domain</th>
                <th>Status</th>
                <th>Theme</th>
                <th>Language</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sites as $site): ?>
            <tr>
                <td><?php echo (int)$site['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($site['name']); ?></strong></td>
                <td><code><?php echo htmlspecialchars($site['domain']); ?></code>
                    <?php if (!empty($site['aliases'])): $aliases = json_decode($site['aliases'], true); if (is_array($aliases) && count($aliases) > 0): ?>
                    <br><span style="font-size:11px;color:#888;">Aliases: <?php echo htmlspecialchars(implode(', ', $aliases)); ?></span>
                    <?php endif; endif; ?>
                </td>
                <td><span class="badge badge-<?php echo htmlspecialchars($site['status']); ?>"><?php echo htmlspecialchars($site['status']); ?></span></td>
                <td><?php echo htmlspecialchars($site['theme'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($site['language'] ?? '—'); ?></td>
                <td class="actions">
                    <a href="/admin/sites/edit/<?php echo (int)$site['id']; ?>" class="button button-small">Edit</a>
                    <a href="/admin/sites/delete/<?php echo (int)$site['id']; ?>" class="button button-small" onclick="return confirm('Delete this site? This cannot be undone.')">Delete</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
        </main>
    </div>
</body>
</html>
