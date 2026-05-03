<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - XooPress Admin</title>
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body class="admin-page">
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <div class="admin-brand">
                <img src="/images/xp-logo.svg" alt="XooPress" style="height:32px;vertical-align:middle;margin-right:8px;">
                <span style="font-size:1.1rem;font-weight:700;">XooPress</span>
            </div>
            <ul class="admin-nav">
                <li><a href="/admin">Dashboard</a></li>
                <li><a href="/admin/posts">Posts</a></li>
                <li><a href="/admin/pages">Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/themes">Themes</a></li>
                <li><a href="/admin/settings" class="active">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Settings</h1>
            </header>
            <?php if (isset($message) && $message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType ?? 'info') ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <div class="admin-form-container">
                <form method="POST" action="/admin/settings">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'XooPress') ?>">
                    </div>
                    <div class="form-group">
                        <label for="site_description">Site Description</label>
                        <textarea id="site_description" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description'] ?? 'A modular CMS combining XOOPS and WordPress concepts') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="site_url">Site URL</label>
                        <input type="url" id="site_url" name="site_url" value="<?= htmlspecialchars($settings['site_url'] ?? 'http://localhost') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>