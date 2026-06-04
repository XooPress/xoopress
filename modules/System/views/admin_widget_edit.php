<?php $pageTitle = 'Edit Widget - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
    <style>
        .widget-edit-form { max-width:600px; background:#fff; padding:25px; border-radius:6px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
        .widget-edit-form .form-group { margin-bottom:15px; }
        .widget-edit-form label { display:block; font-weight:600; margin-bottom:5px; color:#333; font-size:0.9rem; }
        .widget-edit-form input, .widget-edit-form textarea, .widget-edit-form select { width:100%; padding:8px 12px; border:1px solid #ddd; border-radius:4px; font-size:0.9rem; }
        .widget-edit-form textarea { min-height:150px; font-family:monospace; }
        .widget-edit-form input:focus, .widget-edit-form textarea:focus { outline:none; border-color:#0073aa; }
    </style>
            <div class="admin-header">
                <h1>Edit Widget</h1>
                <a href="/admin/widgets" class="btn btn-secondary btn-sm">← Back to Widgets</a>
            </div>
            <?php include __DIR__ . '/_notices.php'; ?>
            <div class="widget-edit-form">
                <form method="POST" action="/admin/widgets/save">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="id" value="<?= (int)($widget['id'] ?? 0) ?>">
                    <div class="form-group">
                        <label>Widget Type</label>
                        <input type="text" value="<?= htmlspecialchars(ucfirst(str_replace('_', ' ', $widget['widget_type'] ?? ''))) ?>" readonly style="background:#f5f5f5;">
                    </div>
                    <?php $wd = $widget['widget_data'] ?? []; ?>
                    <?php if (is_string($wd)) { $decoded = json_decode($wd, true); $wd = $decoded !== null ? $decoded : []; } ?>
                    <div class="form-group">
                        <label>Title</label>
                        <input type="text" name="widget_data[title]" value="<?= htmlspecialchars($wd['title'] ?? '') ?>" placeholder="Widget title">
                    </div>
                    <?php if (in_array($widget['widget_type'] ?? '', ['text', 'custom_html'])): ?>
                    <div class="form-group">
                        <label>Content</label>
                        <textarea name="widget_data[content]" placeholder="Enter content..."><?= htmlspecialchars($wd['content'] ?? '') ?></textarea>
                    </div>
                    <?php endif; ?>
                    <?php if (($widget['widget_type'] ?? '') === 'recent_posts'): ?>
                    <div class="form-group">
                        <label>Number of Posts to Show</label>
                        <input type="number" name="widget_data[count]" value="<?= (int)($wd['count'] ?? 5) ?>" min="1" max="20">
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary">Save Widget</button>
                </form>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>