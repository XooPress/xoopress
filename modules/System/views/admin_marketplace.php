<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Marketplace') ?> - <?= __('XooPress Admin') ?></title>
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
                <h2><?= __('Marketplace') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="/admin/marketplace/modules" class="btn btn-primary btn-sm"><?= __('Browse Modules') ?></a>
                    <a href="/admin/marketplace/themes" class="btn btn-primary btn-sm"><?= __('Browse Themes') ?></a>
                    <a href="/admin/marketplace/clear-cache" class="btn btn-secondary btn-sm"><?= __('Clear Cache') ?></a>
                </div>
            </div>

            <?php if (isset($notice)): ?>
                <div class="alert alert-<?= htmlspecialchars($noticeType ?? 'info') ?>"><?= htmlspecialchars($notice) ?></div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:30px;">
                <!-- Featured Modules -->
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                        <h3 style="margin:0;"><?= __('Featured Modules') ?></h3>
                        <a href="/admin/marketplace/modules" class="btn btn-sm btn-secondary"><?= __('View All') ?></a>
                    </div>
                    <?php if (empty($modules['items'])): ?>
                        <p style="color:#888;text-align:center;padding:30px;"><?= __('No modules available or unable to reach marketplace.') ?></p>
                    <?php else: ?>
                        <?php foreach (array_slice($modules['items'], 0, 6) as $item): ?>
                        <?php
                            $itemDesc = $item['description'] ?? $item['summary'] ?? $item['excerpt'] ?? $item['tagline'] ?? '';
                            $itemName = $item['name'] ?? $item['title'] ?? '';
                            $itemSlug = $item['slug'] ?? $itemName;
                            $itemVersion = $item['version'] ?? '1.0.0';
                            $itemAuthor = $item['author'] ?? $item['author_name'] ?? $item['publisher'] ?? '';
                            $displayName = !empty($itemName) ? $itemName : $itemSlug;
                            $displayDesc = !empty($itemDesc) ? $itemDesc : __('No description available.');
                        ?>
                        <div style="padding:12px 0;border-bottom:1px solid #f0f0f0;display:flex;gap:12px;">
                            <?php if (!empty($item['icon'])): ?>
                            <img src="<?= htmlspecialchars($item['icon']) ?>" alt="" style="width:48px;height:48px;border-radius:6px;object-fit:cover;">
                            <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:6px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;font-size:1.5rem;">📦</div>
                            <?php endif; ?>
                            <div style="flex:1;">
                                <strong><?= htmlspecialchars($displayName) ?></strong>
                                <span style="color:#888;font-size:0.8rem;">v<?= htmlspecialchars($itemVersion) ?></span>
                                <?php if (!empty($itemAuthor)): ?>
                                <span style="color:#aaa;font-size:0.75rem;"> &middot; <?= htmlspecialchars($itemAuthor) ?></span>
                                <?php endif; ?>
                                <p style="margin:2px 0 0;font-size:0.85rem;color:#666;"><?= htmlspecialchars(mb_substr($displayDesc, 0, 200)) ?></p>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                                <a href="/admin/marketplace/install/module/<?= urlencode($itemSlug) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                <?php if (!empty($item['homepage'])): ?>
                                <a href="<?= htmlspecialchars($item['homepage']) ?>" target="_blank" class="btn btn-sm btn-secondary"><?= __('Details') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Featured Themes -->
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
                        <h3 style="margin:0;"><?= __('Featured Themes') ?></h3>
                        <a href="/admin/marketplace/themes" class="btn btn-sm btn-secondary"><?= __('View All') ?></a>
                    </div>
                    <?php if (empty($themes['items'])): ?>
                        <p style="color:#888;text-align:center;padding:30px;"><?= __('No themes available or unable to reach marketplace.') ?></p>
                    <?php else: ?>
                        <?php foreach (array_slice($themes['items'], 0, 6) as $item): ?>
                        <?php
                            $itemDesc = $item['description'] ?? $item['summary'] ?? $item['excerpt'] ?? $item['tagline'] ?? '';
                            $itemName = $item['name'] ?? $item['title'] ?? '';
                            $itemSlug = $item['slug'] ?? $itemName;
                            $itemVersion = $item['version'] ?? '1.0.0';
                            $itemAuthor = $item['author'] ?? $item['author_name'] ?? $item['publisher'] ?? '';
                            $displayName = !empty($itemName) ? $itemName : $itemSlug;
                            $displayDesc = !empty($itemDesc) ? $itemDesc : __('No description available.');
                        ?>
                        <div style="padding:12px 0;border-bottom:1px solid #f0f0f0;display:flex;gap:12px;">
                            <?php if (!empty($item['screenshot'])): ?>
                            <img src="<?= htmlspecialchars($item['screenshot']) ?>" alt="" style="width:80px;height:48px;border-radius:6px;object-fit:cover;">
                            <?php else: ?>
                            <div style="width:80px;height:48px;border-radius:6px;background:#f0f4ff;display:flex;align-items:center;justify-content:center;font-size:1.2rem;">🎨</div>
                            <?php endif; ?>
                            <div style="flex:1;">
                                <strong><?= htmlspecialchars($displayName) ?></strong>
                                <span style="color:#888;font-size:0.8rem;">v<?= htmlspecialchars($itemVersion) ?></span>
                                <?php if (!empty($itemAuthor)): ?>
                                <span style="color:#aaa;font-size:0.75rem;"> &middot; <?= htmlspecialchars($itemAuthor) ?></span>
                                <?php endif; ?>
                                <p style="margin:2px 0 0;font-size:0.85rem;color:#666;"><?= htmlspecialchars(mb_substr($displayDesc, 0, 200)) ?></p>
                            </div>
                            <div style="display:flex;flex-direction:column;gap:4px;align-items:flex-end;">
                                <a href="/admin/marketplace/install/theme/<?= urlencode($itemSlug) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                <?php if (!empty($item['demo_url'])): ?>
                                <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" class="btn btn-sm btn-secondary"><?= __('Preview') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div style="margin-top:30px;padding:20px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:8px;text-align:center;">
                <p style="color:#888;"><?= __('Can\'t find what you\'re looking for?') ?></p>
                <p><a href="https://marketplace.xoopress.org/" target="_blank" class="btn btn-primary"><?= __('Visit XooPress Marketplace') ?></a></p>
            </div>
        </main>
    </div>
</body>
</html>