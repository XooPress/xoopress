<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Translations') ?> - <?= __('XooPress Admin') ?></title>
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
                <h2><?= __('Translations') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="/admin/translations/sync" class="btn btn-secondary btn-sm"><?= __('Sync from Community') ?></a>
                </div>
            </div>

            <?php if (isset($notice)): ?>
                <div class="alert alert-<?= htmlspecialchars($noticeType ?? 'info') ?>"><?= htmlspecialchars($notice) ?></div>
            <?php endif; ?>

            <?php if (empty($locales)): ?>
                <p style="text-align:center;padding:40px;color:#888;"><?= __('No locale directories found.') ?></p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= __('Locale') ?></th>
                            <th><?= __('Language') ?></th>
                            <th><?= __('Total Strings') ?></th>
                            <th><?= __('Translated') ?></th>
                            <th><?= __('Progress') ?></th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $code => $stat): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($code) ?></code></td>
                            <td><?= htmlspecialchars($stat['label'] ?? $code) ?></td>
                            <td><?= $stat['total'] ?></td>
                            <td><?= $stat['translated'] ?></td>
                            <td>
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div style="flex:1;max-width:150px;height:8px;background:#e0e0e0;border-radius:4px;overflow:hidden;">
                                        <div style="height:100%;width:<?= $stat['percent'] ?>%;background:<?= $stat['percent'] > 80 ? '#28a745' : ($stat['percent'] > 50 ? '#ffc107' : '#dc3545') ?>;border-radius:4px;"></div>
                                    </div>
                                    <span style="font-size:0.85rem;font-weight:600;"><?= $stat['percent'] ?>%</span>
                                </div>
                            </td>
                            <td>
                                <a href="/admin/translations/edit/<?= htmlspecialchars($code) ?>" class="btn btn-sm btn-primary"><?= __('Edit') ?></a>
                                <a href="/admin/translations/sync?locale=<?= htmlspecialchars($code) ?>" class="btn btn-sm btn-secondary"><?= __('Sync') ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <div style="margin-top:30px;padding:20px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:8px;">
                <h3><?= __('Add New Locale') ?></h3>
                <form method="POST" action="/admin/translations/add-locale" class="admin-form" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <div class="form-group" style="margin:0;">
                        <label for="locale"><?= __('Locale Code') ?></label>
                        <input type="text" id="locale" name="locale" class="form-control" placeholder="de_DE, fr_FR, etc." required pattern="[a-z]{2}_[A-Z]{2}">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= __('Add Locale') ?></button>
                </form>
                <p style="margin:8px 0 0;font-size:0.85rem;color:#888;"><?= __('Use format: de_DE, fr_FR, es_ES, etc.') ?></p>
            </div>
        </main>
    </div>
</body>
</html>