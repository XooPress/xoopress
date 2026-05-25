<?php
/**
 * Webhook Add/Edit View
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var bool $isNew */
/** @var array $webhook */
/** @var array $events */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $isNew ? 'Add New' : 'Edit'; ?> Webhook - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .form-table { width: 100%; border-collapse: collapse; }
        .form-table th { text-align: left; padding: 12px 12px 12px 0; width: 180px; vertical-align: top; font-size: 13px; }
        .form-table td { padding: 8px 0; }
        .form-table input[type="text"], .form-table input[type="url"], .form-table input[type="number"], .form-table select, .form-table textarea { width: 100%; max-width: 500px; padding: 6px 8px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 13px; }
        .form-table textarea { min-height: 60px; }
        .form-table .description { font-size: 12px; color: #646970; margin-top: 4px; }
        .submit-row { margin-top: 20px; padding-top: 16px; border-top: 1px solid #dcdcde; }
        .submit-row input[type="submit"] { padding: 8px 24px; background: #2271b1; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
        .submit-row input[type="submit"]:hover { opacity: 0.9; }
        .submit-row .cancel { margin-left: 12px; font-size: 13px; color: #2271b1; text-decoration: none; }
    </style>
</head>
<body class="admin-page">
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <div class="admin-brand">
                <img src="/images/xp-logo.svg" alt="XooPress" style="height:32px;vertical-align:middle;margin-right:8px;">
                <span style="font-size:1.1rem;font-weight:700;">XooPress</span>
            </div>
            <ul class="admin-nav">
                <?php if (!empty($adminMenu)): ?>
                <?php foreach ($adminMenu as $menuItem): ?>
                <?php
                    $menuUrl = $menuItem['url'] ?? '';
                    $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl);
                ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <a href="/admin/webhooks" style="display:inline-block;margin-bottom:16px;font-size:13px;">← Back to Webhooks</a>
                <h1><?php echo $isNew ? 'Add New Webhook' : 'Edit Webhook'; ?></h1>
            </header>

            <?php $message = $_SESSION['admin_notice'] ?? null; $messageType = $_SESSION['admin_notice_type'] ?? null; include __DIR__ . '/_notices.php'; ?>

            <form method="post" action="/admin/webhooks/save">
                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)$webhook['id']; ?>">
                <?php endif; ?>

                <table class="form-table">
                    <tr>
                        <th><label for="event">Event</label></th>
                        <td>
                            <select name="event" id="event" required>
                                <option value="">— Select Event —</option>
                                <?php foreach ($events as $ev): ?>
                                <option value="<?php echo htmlspecialchars($ev); ?>" <?php echo (!empty($webhook['event']) && $webhook['event'] === $ev) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ev); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="description">The event that triggers this webhook.</div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="url">Callback URL</label></th>
                        <td>
                            <input type="url" name="url" id="url" value="<?php echo htmlspecialchars($webhook['url'] ?? ''); ?>" required placeholder="https://example.com/webhook">
                            <div class="description">The URL that will receive the HTTP POST request.</div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="secret">Secret</label></th>
                        <td>
                            <input type="text" name="secret" id="secret" value="<?php echo htmlspecialchars($webhook['secret'] ?? ''); ?>" placeholder="Leave empty for no signature">
                            <div class="description">Optional shared secret for HMAC-SHA256 signature verification (sent as XooPress-Signature header).</div>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td>
                            <textarea name="description" id="description" placeholder="Optional description"><?php echo htmlspecialchars($webhook['description'] ?? ''); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="timeout">Timeout (seconds)</label></th>
                        <td>
                            <input type="number" name="timeout" id="timeout" value="<?php echo (int)($webhook['timeout'] ?? 5); ?>" min="1" max="30" style="width:100px;">
                            <div class="description">Maximum time to wait for a response (1–30 seconds).</div>
                        </td>
                    </tr>
                    <tr>
                        <th><label>Active</label></th>
                        <td>
                            <label><input type="checkbox" name="is_active" value="1" <?php echo (!isset($webhook['is_active']) || !empty($webhook['is_active'])) ? 'checked' : ''; ?>> Enable this webhook</label>
                        </td>
                    </tr>
                </table>

                <div class="submit-row">
                    <input type="submit" value="<?php echo $isNew ? 'Create Webhook' : 'Save Changes'; ?>">
                    <a class="cancel" href="/admin/webhooks">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>