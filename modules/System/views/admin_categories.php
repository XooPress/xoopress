<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories - XooPress Admin</title>
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
                <li><a href="/admin/posts">Posts</a></li>
                <li><a href="/admin/pages">Pages</a></li>
                <li><a href="/admin/categories" class="active">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Categories</h1>
            </header>

            <form method="POST" action="/admin/categories" style="margin-bottom:20px;padding:15px;background:#f9f9f9;border-radius:4px;">
                <h3 style="margin-bottom:10px;">Add New Category</h3>
                <div style="display:flex;gap:10px;">
                    <input type="text" name="name" placeholder="Category name" required style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <input type="text" name="slug" placeholder="slug" style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:8px 16px;">Add</button>
                </div>
            </form>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No categories yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cat['name'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($cat['slug'] ?? '') ?></td>
                        <td><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                        <td>
                            <a href="/admin/categories/delete/<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
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