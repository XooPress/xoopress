<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - XooPress</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
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
                <li><a href="/admin" class="active">Dashboard</a></li>
                <li><a href="/admin/posts">Posts</a></li>
                <li><a href="/admin/pages">Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/themes">Themes</a></li>
                <li><a href="/admin/modules">Modules</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/user/themes">Choose Theme</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
            </header>
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Users</h3>
                    <p class="stat-number"><?= (int)($userCount ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Modules</h3>
                    <p class="stat-number"><?= is_countable($modules) ? count($modules) : 0 ?></p>
                </div>
                <div class="stat-card">
                    <h3>Version</h3>
                    <p class="stat-number"><?= htmlspecialchars($version) ?></p>
                </div>
            </div>

            <h2 style="margin-top:30px;font-size:1.2rem;">Installed Modules</h2>
            <table class="admin-table" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Version</th>
                        <th>Description</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!is_array($modules) || empty($modules)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No modules installed.</td></tr>
                    <?php else: ?>
                    <?php foreach ($modules as $mod): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($mod['name'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($mod['version'] ?? '') ?></td>
                        <td><?= htmlspecialchars($mod['description'] ?? '') ?></td>
                        <td><?= htmlspecialchars($mod['author'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>