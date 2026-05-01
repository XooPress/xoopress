<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $contentType = ($type ?? 'post') === 'page' ? 'Page' : 'Post'; ?>
    <?php $listUrl = ($type ?? 'post') === 'page' ? '/admin/pages' : '/admin/posts'; ?>
    <title><?= $isNew ? 'Add New ' . $contentType : 'Edit ' . $contentType ?> - XooPress Admin</title>
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
                <li><a href="/admin/posts" <?= ($type ?? 'post') !== 'page' ? 'class="active"' : '' ?>>Posts</a></li>
                <li><a href="/admin/pages" <?= ($type ?? 'post') === 'page' ? 'class="active"' : '' ?>>Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1><?= $isNew ? 'Add New ' . $contentType : 'Edit ' . $contentType ?></h1>
                <a href="<?= $listUrl ?>" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← Back to <?= $contentType ?>s</a>
            </header>
            <form method="POST" action="/admin/posts/save" style="max-width:800px;">
                <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">
                <?php endif; ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($type ?? 'post') ?>">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1.1rem;">
                </div>
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <div class="form-hint">Leave empty to auto-generate from title.</div>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="15" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-family:monospace;"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="3" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="pending" <?= ($post['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select id="language" name="language" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="en_US" <?= ($post['language'] ?? 'en_US') === 'en_US' ? 'selected' : '' ?>>English</option>
                            <option value="de_DE" <?= ($post['language'] ?? '') === 'de_DE' ? 'selected' : '' ?>>Deutsch</option>
                            <option value="fr_FR" <?= ($post['language'] ?? '') === 'fr_FR' ? 'selected' : '' ?>>Français</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="padding:12px 30px;"><?= $isNew ? 'Publish' : 'Update' ?></button>
                    <a href="<?= $listUrl ?>" class="btn btn-secondary" style="padding:12px 30px;">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>