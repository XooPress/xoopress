<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Widgets - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .widgets-layout { display:flex; gap:20px; align-items:flex-start; }
        .widgets-areas { flex:1; }
        .widgets-available { width:320px; flex-shrink:0; }
        .widget-area-box { background:#fff; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px; overflow:hidden; }
        .widget-area-header { background:#f8f9fa; padding:12px 15px; font-weight:600; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .widget-area-body { padding:10px 15px; min-height:40px; }
        .widget-item { background:#f1f3f5; border:1px solid #dee2e6; border-radius:4px; padding:10px 12px; margin-bottom:8px; cursor:move; display:flex; justify-content:space-between; align-items:center; }
        .widget-item:hover { border-color:#0073aa; }
        .widget-item-title { font-weight:500; font-size:0.9rem; }
        .widget-item-actions { display:flex; gap:6px; }
        .empty-sidebar { color:#999; font-size:0.85rem; text-align:center; padding:15px 0; }
        .available-widget { background:#fff; border:1px solid #e0e0e0; border-radius:4px; padding:10px 12px; margin-bottom:8px; cursor:pointer; transition:all 0.2s; }
        .available-widget:hover { border-color:#0073aa; background:#f0f7ff; }
        .widget-form { display:none; background:#fff; border:1px solid #0073aa; border-radius:6px; padding:15px; margin-top:8px; }
        .widget-form.open { display:block; }
        .widget-inline-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .widget-inline-form input, .widget-inline-form select { padding:6px 10px; border:1px solid #ddd; border-radius:3px; font-size:0.85rem; width:auto; flex:1; min-width:120px; }
        .widget-inline-form button { white-space:nowrap; }
    </style>
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
            <div class="admin-header">
                <h1>Widgets</h1>
            </div>

            <?php include __DIR__ . '/_notices.php'; ?>

            <div class="widgets-layout">
                <div class="widgets-areas">
                    <h2 style="font-size:1.1rem;margin-bottom:10px;">Widget Areas (Sidebars)</h2>
                    
                    <?php if (empty($sidebars)): ?>
                    <p style="color:#999;">No widget areas registered.</p>
                    <?php else: ?>
                    <?php foreach ($sidebars as $sidebar): ?>
                    <div class="widget-area-box">
                        <div class="widget-area-header">
                            <span><?= htmlspecialchars($sidebar['name'] ?? $sidebar['id']) ?></span>
                            <span style="font-size:0.8rem;color:#888;font-weight:normal;"><?= htmlspecialchars($sidebar['id']) ?></span>
                        </div>
                        <div class="widget-area-body" data-sidebar="<?= htmlspecialchars($sidebar['id']) ?>">
                            <?php $sbWidgets = $theme->getSidebarWidgets($sidebar['id']); ?>
                            <?php if (empty($sbWidgets)): ?>
                            <div class="empty-sidebar">No widgets in this area.</div>
                            <?php else: ?>
                            <?php foreach ($sbWidgets as $w): ?>
                            <div class="widget-item" data-widget-id="<?= (int)$w['id'] ?>">
                                <span class="widget-item-title"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $w['widget_type']))) ?></span>
                                <div class="widget-item-actions">
                                    <a href="/admin/widgets/edit/<?= (int)$w['id'] ?>" class="btn btn-sm btn-secondary" style="font-size:0.75rem;">Edit</a>
                                    <a href="/admin/widgets/delete/<?= (int)$w['id'] ?>" class="btn btn-sm btn-danger" style="font-size:0.75rem;" onclick="return confirm('Delete this widget?')">Delete</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="padding:8px 15px;border-top:1px solid #f0f0f0;">
                            <form method="POST" action="/admin/widgets/add" class="widget-inline-form">
                                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                                <input type="hidden" name="sidebar_id" value="<?= htmlspecialchars($sidebar['id']) ?>">
                                <select name="widget_type" required>
                                    <option value="">Add widget...</option>
                                    <option value="text">Text</option>
                                    <option value="recent_posts">Recent Posts</option>
                                    <option value="categories">Categories</option>
                                    <option value="custom_html">Custom HTML</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Add</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="widgets-available">
                    <h2 style="font-size:1.1rem;margin-bottom:10px;">Available Widget Types</h2>
                    <div class="available-widget" onclick="document.querySelector('.widget-inline-form select').focus();">
                        <strong>📝 Text</strong>
                        <p style="font-size:0.8rem;color:#888;margin:4px 0 0;">Arbitrary text or HTML with optional title.</p>
                    </div>
                    <div class="available-widget">
                        <strong>📰 Recent Posts</strong>
                        <p style="font-size:0.8rem;color:#888;margin:4px 0 0;">Display a list of your most recent posts.</p>
                    </div>
                    <div class="available-widget">
                        <strong>📁 Categories</strong>
                        <p style="font-size:0.8rem;color:#888;margin:4px 0 0;">List of post categories.</p>
                    </div>
                    <div class="available-widget">
                        <strong>🔗 Custom HTML</strong>
                        <p style="font-size:0.8rem;color:#888;margin:4px 0 0;">Arbitrary HTML code.</p>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>