<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Module Management') ?> - <?= __('XooPress Admin') ?></title>
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h1><?= __('XooPress Admin') ?></h1>
            <ul>
                <li><a href="/admin"><?= __('Dashboard') ?></a></li>
                <li><a href="/admin/posts"><?= __('Posts') ?></a></li>
                <li><a href="/admin/pages"><?= __('Pages') ?></a></li>
                <li><a href="/admin/categories"><?= __('Categories') ?></a></li>
                <li><a href="/admin/users"><?= __('Users') ?></a></li>
                <li><a href="/admin/modules" class="active"><?= __('Modules') ?></a></li>
                <li><a href="/admin/themes"><?= __('Themes') ?></a></li>
                <li><a href="/admin/settings"><?= __('Settings') ?></a></li>
                <li><a href="/"><?= __('View Site') ?></a></li>
                <li><a href="/logout"><?= __('Logout') ?></a></li>
            </ul>
        </nav>
        <main class="admin-main">
            <div class="admin-header">
                <h2><?= __('Module Management') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <form method="POST" action="/admin/modules/upload" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="file" name="module_zip" accept=".zip" required style="font-size:0.85rem;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= __('Upload Module') ?></button>
                    </form>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th><?= __('Module') ?></th>
                        <th><?= __('Version') ?></th>
                        <th><?= __('Author') ?></th>
                        <th><?= __('Description') ?></th>
                        <th><?= __('Status') ?></th>
                        <th><?= __('Actions') ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($modules)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#888;">
                            <?= __('No modules found.') ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($modules as $module): ?>
                    <?php
                        $def = $module['definition'] ?? [];
                        $isInstalled = $module['installed'] ?? false;
                        $isActive = $module['active'] ?? false;
                        $name = $module['name'];
                        
                        if ($isInstalled && $isActive):
                            $statusClass = 'status-active';
                            $statusText = __('Active');
                        elseif ($isInstalled):
                            $statusClass = 'status-inactive';
                            $statusText = __('Inactive');
                        else:
                            $statusClass = 'status-not-installed';
                            $statusText = __('Not Installed');
                        endif;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($def['name'] ?? $name) ?></strong></td>
                        <td><?= htmlspecialchars($def['version'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($def['author'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($def['description'] ?? '') ?></td>
                        <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                        <td class="actions-cell">
                            <?php if (!$isInstalled): ?>
                                <a href="/admin/modules/install/<?= urlencode($name) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                <a href="/admin/modules/delete/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Delete this module from filesystem?') ?>')"><?= __('Delete') ?></a>
                            <?php elseif ($isActive): ?>
                                <a href="/admin/modules/deactivate/<?= urlencode($name) ?>" class="btn btn-sm btn-warning"><?= __('Deactivate') ?></a>
                                <a href="/admin/modules/uninstall/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Uninstall this module? This will drop its database tables.') ?>')"><?= __('Uninstall') ?></a>
                            <?php else: ?>
                                <a href="/admin/modules/activate/<?= urlencode($name) ?>" class="btn btn-sm btn-success"><?= __('Activate') ?></a>
                                <a href="/admin/modules/uninstall/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Uninstall this module? This will drop its database tables.') ?>')"><?= __('Uninstall') ?></a>
                                <a href="/admin/modules/delete/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Delete this module from filesystem?') ?>')"><?= __('Delete') ?></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>