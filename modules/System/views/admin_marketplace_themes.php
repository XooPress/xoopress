<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Marketplace Themes') ?> - <?= __('XooPress Admin') ?></title>
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
                <h2><?= __('Marketplace Themes') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="/admin/marketplace" class="btn btn-sm btn-secondary">← <?= __('Back') ?></a>
                    <form method="GET" action="/admin/marketplace/themes" style="display:flex;gap:8px;align-items:center;">
                        <input type="text" name="search" placeholder="<?= __('Search themes...') ?>" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control" style="width:250px;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= __('Search') ?></button>
                    </form>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($items) && empty($error)): ?>
                <p style="text-align:center;padding:40px;color:#888;"><?= __('No themes found in marketplace.') ?></p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">
                    <?php foreach ($items as $item): ?>
                    <?php
                        $itemDesc = $item['description'] ?? $item['summary'] ?? $item['excerpt'] ?? $item['tagline'] ?? '';
                        $itemName = $item['name'] ?? $item['title'] ?? '';
                        $itemSlug = $item['slug'] ?? $itemName;
                        $itemVersion = $item['version'] ?? '1.0.0';
                        $itemAuthor = $item['author'] ?? $item['author_name'] ?? $item['publisher'] ?? '';
                        $displayName = !empty($itemName) ? $itemName : $itemSlug;
                        $displayDesc = !empty($itemDesc) ? $itemDesc : __('No description available.');
                    ?>
                    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
                        <?php if (!empty($item['screenshot'])): ?>
                        <img src="<?= htmlspecialchars($item['screenshot']) ?>" alt="" style="width:100%;height:160px;object-fit:cover;">
                        <?php else: ?>
                        <div style="width:100%;height:160px;background:linear-gradient(135deg,#f0f4ff,#e8f0fe);display:flex;align-items:center;justify-content:center;font-size:3rem;">🎨</div>
                        <?php endif; ?>
                        <div style="padding:15px;">
                            <h3 style="margin:0 0 4px;font-size:1rem;"><?= htmlspecialchars($displayName) ?></h3>
                            <div style="color:#888;font-size:0.8rem;margin-bottom:8px;">
                                v<?= htmlspecialchars($itemVersion) ?>
                                <?php if (!empty($itemAuthor)): ?>
                                &middot; <?= htmlspecialchars($itemAuthor) ?>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.85rem;color:#666;margin:0 0 12px;"><?= htmlspecialchars(mb_substr($displayDesc, 0, 200)) ?></p>
                            <div style="display:flex;gap:6px;">
                                <a href="/admin/marketplace/install/theme/<?= urlencode($itemSlug) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                <?php if (!empty($item['demo_url'])): ?>
                                <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" class="btn btn-sm btn-secondary"><?= __('Preview') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div style="margin-top:20px;display:flex;justify-content:center;gap:8px;">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-sm btn-secondary">← <?= __('Previous') ?></a>
                    <?php endif; ?>
                    <span style="padding:6px 12px;"><?= __('Page') ?> <?= $page ?> <?= __('of') ?> <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="btn btn-sm btn-secondary"><?= __('Next') ?> →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>