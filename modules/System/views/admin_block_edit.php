<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isNew ? 'New' : 'Edit' ?> Block - XooPress Admin</title>
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
                <?php if (!empty($adminMenu)): ?>
                <?php foreach ($adminMenu as $menuItem): ?>
                <?php $menuUrl = $menuItem['url'] ?? ''; $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl); ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1><?= $isNew ? 'New Content Block' : 'Edit Content Block' ?></h1>
                <a href="/admin/blocks" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← All Blocks</a>
            </header>

            <form method="POST" action="/admin/blocks/save" style="max-width:700px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= (int)($block['id'] ?? 0) ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($block['title'] ?? '') ?>" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                </div>
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($block['slug'] ?? '') ?>" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <div class="form-hint">Used in shortcode: <code>[block slug="your-slug"]</code></div>
                </div>
                <div class="form-group">
                    <label for="content">Content</label>
                    <textarea id="content" name="content" rows="12" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-family:monospace;"><?= htmlspecialchars($block['content'] ?? '') ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                    <div class="form-group">
                        <label for="content_type">Content Type</label>
                        <select id="content_type" name="content_type" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="html" <?= ($block['content_type'] ?? 'html') === 'html' ? 'selected' : '' ?>>HTML</option>
                            <option value="wysiwyg" <?= ($block['content_type'] ?? '') === 'wysiwyg' ? 'selected' : '' ?>>WYSIWYG</option>
                            <option value="markdown" <?= ($block['content_type'] ?? '') === 'markdown' ? 'selected' : '' ?>>Markdown</option>
                            <option value="php" <?= ($block['content_type'] ?? '') === 'php' ? 'selected' : '' ?>>PHP</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category">Category</label>
                        <input type="text" id="category" name="category" value="<?= htmlspecialchars($block['category'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;" placeholder="e.g., footer, sidebar">
                    </div>
                    <div class="form-group">
                        <label style="display:flex;align-items:center;gap:8px;margin-top:20px;cursor:pointer;">
                            <input type="checkbox" name="is_active" value="1" <?= (!isset($block['is_active']) || $block['is_active']) ? 'checked' : '' ?> style="width:auto;">
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="padding:10px 24px;"><?= $isNew ? 'Create Block' : 'Update Block' ?></button>
                    <a href="/admin/blocks" class="btn btn-secondary" style="padding:10px 24px;">Cancel</a>
                </div>
            </form>

            <?php if (!$isNew): ?>
            <div style="margin-top:20px;padding:15px;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">
                <strong>Shortcode:</strong>
                <code style="background:#f0f6fc;padding:3px 10px;border:1px solid #c8def5;border-radius:3px;display:inline-block;margin-top:5px;">[block slug="<?= htmlspecialchars($block['slug'] ?? '') ?>"]</code>
                <div style="color:#888;font-size:0.85rem;margin-top:5px;">Copy this shortcode into any post's content to display this block.</div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        document.getElementById('title').addEventListener('blur', function() {
            const slug = document.getElementById('slug');
            if (!slug.value) {
                slug.value = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/^-+|-+$/g, '') || 'untitled';
            }
        });
    </script>
</body>
</html>