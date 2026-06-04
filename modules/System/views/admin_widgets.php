<?php $pageTitle = 'Widgets - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
    <style>
        .widgets-layout { display:flex; gap:20px; align-items:flex-start; }
        .widget-area-card { background:#fff; border-radius:4px; box-shadow:0 1px 3px rgba(0,0,0,0.1); margin-bottom:20px; }
        .widget-area-header { background:#f8f8f8; padding:12px 16px; border-bottom:1px solid #e0e0e0; font-weight:600; font-size:0.95rem; }
        .widget-area-content { padding:12px 16px; min-height:60px; }
        .widget-item { background:#f8f8f8; border:1px solid #e0e0e0; border-radius:3px; padding:10px 12px; margin-bottom:8px; display:flex; align-items:center; justify-content:space-between; font-size:0.9rem; }
        .widget-item:last-child { margin-bottom:0; }
        .widget-item .widget-actions { display:flex; gap:6px; }
        .widget-item .widget-actions a { color:#0073aa; text-decoration:none; font-size:0.85rem; }
        .widget-item .widget-actions a:hover { text-decoration:underline; }
        .add-widget-form { display:flex; gap:8px; padding:12px 16px; border-top:1px solid #e0e0e0; flex-wrap:wrap; }
        .add-widget-form select { padding:6px 10px; border:1px solid #ddd; border-radius:3px; font-size:0.85rem; flex:1; min-width:120px; }
        .no-widgets { color:#999; font-size:0.9rem; text-align:center; padding:20px; }
    </style>
            <header class="admin-header">
                <h1>Widgets</h1>
            </header>

            <?php $message = $_SESSION['admin_notice'] ?? null; $messageType = $_SESSION['admin_notice_type'] ?? null; include __DIR__ . '/_notices.php'; ?>

            <div class="widgets-layout">
                <?php if (!empty($sidebars)): ?>
                <?php foreach ($sidebars as $sidebar): ?>
                <div class="widget-area-card" style="flex:1;min-width:280px;">
                    <div class="widget-area-header"><?= htmlspecialchars($sidebar['name'] ?? $sidebar['id'] ?? 'Unknown Sidebar') ?></div>
                    <div class="widget-area-content">
                        <?php $widgets = $sidebar['widgets'] ?? []; ?>
                        <?php if (empty($widgets)): ?>
                        <div class="no-widgets">No widgets yet.</div>
                        <?php else: ?>
                        <?php foreach ($widgets as $widget): ?>
                        <div class="widget-item">
                            <span><?= htmlspecialchars($widget['widget_type'] ?? 'Unknown') ?></span>
                            <div class="widget-actions">
                                <a href="/admin/widgets/edit/<?= $widget['id'] ?>">Edit</a>
                                <a href="/admin/widgets/delete/<?= $widget['id'] ?>" onclick="return confirm('Delete this widget?')">Delete</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <form method="POST" action="/admin/widgets/add" class="add-widget-form">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="hidden" name="sidebar_id" value="<?= htmlspecialchars($sidebar['id'] ?? '') ?>">
                        <select name="widget_type" required>
                            <option value="">Add widget...</option>
                            <option value="text">Text</option>
                            <option value="recent_posts">Recent Posts</option>
                            <option value="categories">Categories</option>
                            <option value="search">Search</option>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Add</button>
                    </form>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="admin-notice admin-notice-info">
                    <span class="admin-notice-icon">ℹ</span>
                    <span class="admin-notice-text">No sidebars registered. The active theme may not support widgets.</span>
                </div>
                <?php endif; ?>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>