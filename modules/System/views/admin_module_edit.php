<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Edit Module') ?> - <?= __('XooPress Admin') ?></title>
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
        <main class="admin-content">
            <div class="admin-header">
                <h2><?= __('Edit Module') ?>: <?= htmlspecialchars($module['definition']['name'] ?? $module['name']) ?></h2>
                <a href="/admin/modules" class="btn btn-secondary btn-sm"><?= __('Back to Modules') ?></a>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/modules/save" class="admin-form" style="max-width:600px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="name" value="<?= htmlspecialchars($module['name']) ?>">

                <div class="form-group">
                    <label for="display_name"><?= __('Display Name') ?></label>
                    <input type="text" id="display_name" name="display_name" class="form-control"
                           value="<?= htmlspecialchars($module['definition']['name'] ?? $module['name']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="version"><?= __('Version') ?></label>
                    <input type="text" id="version" name="version" class="form-control"
                           value="<?= htmlspecialchars($module['definition']['version'] ?? '1.0.0') ?>">
                </div>

                <div class="form-group">
                    <label for="description"><?= __('Description') ?></label>
                    <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($module['definition']['description'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label for="author"><?= __('Author') ?></label>
                    <input type="text" id="author" name="author" class="form-control"
                           value="<?= htmlspecialchars($module['definition']['author'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label for="license"><?= __('License') ?></label>
                    <input type="text" id="license" name="license" class="form-control"
                           value="<?= htmlspecialchars($module['definition']['license'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="active" value="1" <?= ($module['active'] ?? false) ? 'checked' : '' ?>>
                        <?= __('Active') ?>
                    </label>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= __('Save Changes') ?></button>
                    <a href="/admin/modules" class="btn btn-secondary"><?= __('Cancel') ?></a>
                </div>
            </form>

            <?php if (!empty($module['definition']['dependencies'])): ?>
            <div style="margin-top:30px;">
                <h3><?= __('Dependencies') ?></h3>
                <ul>
                    <?php foreach ($module['definition']['dependencies'] as $dep): ?>
                    <li><?= htmlspecialchars($dep) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>