<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Theme Management') ?> - <?= __('XooPress Admin') ?></title>
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            padding: 20px 0;
        }
        .theme-card {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: border-color 0.2s;
        }
        .theme-card.active {
            border-color: #0073aa;
        }
        .theme-card.active .theme-card-header {
            background: #0073aa;
            color: #fff;
        }
        .theme-card-header {
            background: #f5f5f5;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 1rem;
        }
        .theme-card-body {
            padding: 15px;
        }
        .theme-screenshot {
            width: 100%;
            height: 160px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 0.85rem;
            margin-bottom: 12px;
            overflow: hidden;
        }
        .theme-screenshot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .theme-meta {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 8px;
        }
        .theme-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .child-badge {
            display: inline-block;
            background: #ffc107;
            color: #333;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 3px;
            margin-left: 6px;
        }
        .parent-badge {
            display: inline-block;
            background: #17a2b8;
            color: #fff;
            font-size: 0.75rem;
            padding: 2px 8px;
            border-radius: 3px;
            margin-left: 6px;
        }
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
                <?php
                    $menuUrl = $menuItem['url'] ?? '';
                    $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl);
                ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/"><?= __('View Site') ?></a></li>
                <li><a href="/logout"><?= __('Logout') ?></a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <div class="admin-header">
                <h2><?= __('Theme Management') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <form method="POST" action="/admin/themes/upload" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="file" name="theme_zip" accept=".zip" required style="font-size:0.85rem;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= __('Upload Theme') ?></button>
                    </form>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <div class="theme-grid">
                <?php if (empty($themes)): ?>
                <p style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">
                    <?= __('No themes found.') ?>
                </p>
                <?php else: ?>
                <?php foreach ($themes as $name => $theme): ?>
                <?php
                    $isActive = $theme['dir_name'] === $activeTheme;
                    $isParent = !$theme['is_child'] && isset($childTheme) && $childTheme === $theme['dir_name'];
                ?>
                <div class="theme-card <?= $isActive ? 'active' : '' ?>">
                    <div class="theme-card-header">
                        <?= htmlspecialchars($theme['name']) ?>
                        <?php if ($theme['is_child']): ?>
                            <span class="child-badge"><?= __('Child') ?></span>
                        <?php elseif ($isParent): ?>
                            <span class="parent-badge"><?= __('Parent') ?></span>
                        <?php endif; ?>
                        <?php if ($isActive): ?>
                            <span style="float:right;font-size:0.8rem;"><?= __('Active') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="theme-card-body">
                        <div class="theme-screenshot">
                            <?php if ($theme['screenshot']): ?>
                                <img src="/<?= htmlspecialchars($theme['screenshot']) ?>" alt="<?= htmlspecialchars($theme['name']) ?>">
                            <?php else: ?>
                                <?= __('No screenshot') ?>
                            <?php endif; ?>
                        </div>
                        <div class="theme-meta">
                            <?= __('Version') ?> <?= htmlspecialchars($theme['version']) ?>
                            <?php if (!empty($theme['author'])): ?>
                                | <?= htmlspecialchars($theme['author']) ?>
                            <?php endif; ?>
                        </div>
                        <p style="font-size:0.9rem;color:#555;"><?= htmlspecialchars($theme['description']) ?></p>
                        <?php if ($theme['template']): ?>
                        <p style="font-size:0.85rem;color:#888;margin-top:6px;">
                            <?= __('Template') ?>: <?= htmlspecialchars($theme['template']) ?>
                        </p>
                        <?php endif; ?>
                        <div class="theme-actions">
                            <?php if (!$isActive): ?>
                                <a href="/admin/themes/activate/<?= urlencode($name) ?>" class="btn btn-sm btn-primary"><?= __('Activate') ?></a>
                            <?php endif; ?>
                            <?php if (!$isActive && !$isParent): ?>
                                <a href="/admin/themes/delete/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Delete this theme?') ?>')"><?= __('Delete') ?></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>