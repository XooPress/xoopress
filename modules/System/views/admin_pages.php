<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pages - XooPress Admin</title>
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
                <li><a href="/admin">Dashboard</a></li>
                li><a href="/admin/posts">Posts</a></li>
                li><a href="/admin/pages" class="active">Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Pages</h1>
                <a href="/admin/pages/new" class="btn btn-primary" style="font-size:0.85rem;padding:8px 16px;">Add New Page</a>
            </header>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pages)): ?>
                    tr><td colspan="5" style="text-align:center;color:#999;">No pages found. <a href="/admin/pages/new">Create one</a>.</td></tr>
                    <?php else: ?>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($page['title'] ?? '') ?></strong></td>
                        td><span class="status-badge status-<?= htmlspecialchars($page['status'] ?? 'draft') ?>"><?= htmlspecialchars($page['status'] ?? 'draft') ?></span></td>
                        <td><?= htmlspecialchars($page['author_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($page['published_at'] ?? $page['created_at'] ?? '') ?></td>
                        <td>
                            <a href="/admin/pages/edit/<?= $page['id'] ?>">Edit</a> |
                            <a href="/admin/posts/delete/<?= $page['id'] ?>" onclick="return confirm('Delete this page?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>