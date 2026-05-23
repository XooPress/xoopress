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
    <?php $role = $_SESSION['user_role'] ?? ''; ?>
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
                <h1>Posts (<?= (int)($total ?? 0) ?>)</h1>
                <a href="/admin/posts/new" class="btn btn-primary" style="font-size:0.85rem;padding:8px 16px;">Add New Post</a>
            </header>

            <?php $message = $_SESSION['admin_notice'] ?? null; $messageType = $_SESSION['admin_notice_type'] ?? null; include __DIR__ . '/_notices.php'; ?>

            <form method="GET" action="/admin/posts" class="admin-search-bar">
                <input type="search" name="search" placeholder="Search posts..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                <a href="/admin/posts" class="btn btn-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </form>

            <form method="POST" action="/admin/posts/bulk" id="bulkForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <div class="admin-bulk-actions">
                    <input type="checkbox" id="selectAll" class="admin-checkbox" onclick="document.querySelectorAll('.admin-checkbox[name=\"ids[]\"]').forEach(c => c.checked = this.checked)">
                    <label for="selectAll" style="font-size:0.85rem;color:#666;">Select All</label>
                    <select name="action">
                        <option value="">Bulk Actions</option>
                        <option value="publish">Publish</option>
                        <option value="draft">Move to Draft</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:32px;"></th>
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
                        <tr><td colspan="7" style="text-align:center;color:#999;">No posts found. <a href="/admin/posts/new">Create one</a>.</td></tr>
                        <?php else: ?>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= $post['id'] ?>" class="admin-checkbox"></td>
                            <td><strong><?= htmlspecialchars($post['title'] ?? '') ?></strong></td>
                            <td><span class="status-badge status-<?= htmlspecialchars($post['status'] ?? 'draft') ?>"><?= htmlspecialchars($post['status'] ?? 'draft') ?></span></td>
                            <td><?= htmlspecialchars($post['category_name'] ?? 'Uncategorized') ?></td>
                            <td><?= htmlspecialchars($post['author_name'] ?? '') ?></td>
                            <td style="font-size:0.85rem;color:#888;"><?= htmlspecialchars($post['published_at'] ?? $post['created_at'] ?? '') ?></td>
                            <td>
                                <a href="/admin/posts/edit/<?= $post['id'] ?>">Edit</a> |
                                <a href="/admin/posts/delete/<?= $post['id'] ?>" onclick="return confirm('Delete this post?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <?php $baseUrl = '/admin/posts'; include __DIR__ . '/_pagination.php'; ?>
        </main>
    </div>
    <script>
        document.getElementById('bulkForm').addEventListener('submit', function(e) {
            var action = this.querySelector('select[name="action"]').value;
            var checked = this.querySelectorAll('.admin-checkbox[name="ids[]"]:checked').length;
            if (!action) { e.preventDefault(); alert('Please select a bulk action.'); return; }
            if (!checked) { e.preventDefault(); alert('Please select at least one item.'); return; }
            if (action === 'delete' && !confirm('Delete the selected items?')) { e.preventDefault(); return; }
        });
    </script>
</body>
</html>