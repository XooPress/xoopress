<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - XooPress Admin</title>
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
                <li><a href="/admin/posts" class="active">Posts</a></li>
                <li><a href="/admin/pages">Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/themes">Themes</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Posts</h1>
                <a href="/admin/posts/new" class="btn btn-primary" style="font-size:0.85rem;padding:8px 16px;">Add New Post</a>
            </header>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Category</th>
                        <th>Author</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($posts)): ?>
                    <tr><td colspan="6" style="text-align:center;color:#999;">No posts found. <a href="/admin/posts/new">Create one</a>.</td></tr>
                    <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($post['title'] ?? '') ?></strong></td>
                        <td><span class="status-badge status-<?= htmlspecialchars($post['status'] ?? 'draft') ?>"><?= htmlspecialchars($post['status'] ?? 'draft') ?></span></td>
                        <td><?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></td>
                        <td><?= htmlspecialchars($post['author_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($post['published_at'] ?? $post['created_at'] ?? '') ?></td>
                        <td>
                            <a href="/admin/posts/edit/<?= $post['id'] ?>">Edit</a> |
                            <a href="/admin/posts/delete/<?= $post['id'] ?>" onclick="return confirm('Delete this post?')">Delete</a>
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