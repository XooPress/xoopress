<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Module Config') ?> - <?= __('XooPress Admin') ?></title>
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
                <h2><?= __('Configuration') ?>: <?= htmlspecialchars($module['definition']['name'] ?? $module['name']) ?></h2>
                <a href="/admin/modules" class="btn btn-secondary btn-sm"><?= __('Back to Modules') ?></a>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/modules/config/save" class="admin-form" style="max-width:600px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="name" value="<?= htmlspecialchars($module['name']) ?>">

                <?php if (!empty($configSchema)): ?>
                    <?php foreach ($configSchema as $field): ?>
                    <?php
                        $fieldKey = $field['key'] ?? $field['name'] ?? '';
                        $fieldLabel = $field['label'] ?? __(ucfirst(str_replace('_', ' ', $fieldKey)));
                        $fieldType = $field['type'] ?? 'text';
                        $fieldDefault = $field['default'] ?? '';
                        $fieldDesc = $field['description'] ?? '';
                        $currentValue = $config[$fieldKey] ?? $fieldDefault;
                    ?>
                    <div class="form-group">
                        <label for="config_<?= htmlspecialchars($fieldKey) ?>"><?= htmlspecialchars($fieldLabel) ?></label>
                        <?php if ($fieldType === 'textarea'): ?>
                            <textarea id="config_<?= htmlspecialchars($fieldKey) ?>" name="config[<?= htmlspecialchars($fieldKey) ?>]" class="form-control" rows="4"><?= htmlspecialchars((string)$currentValue) ?></textarea>
                        <?php elseif ($fieldType === 'select'): ?>
                            <select id="config_<?= htmlspecialchars($fieldKey) ?>" name="config[<?= htmlspecialchars($fieldKey) ?>]" class="form-control">
                                <?php foreach (($field['options'] ?? []) as $optValue => $optLabel): ?>
                                <option value="<?= htmlspecialchars((string)$optValue) ?>" <?= (string)$currentValue === (string)$optValue ? 'selected' : '' ?>><?= htmlspecialchars((string)$optLabel) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($fieldType === 'checkbox'): ?>
                            <label style="display:flex;align-items:center;gap:8px;font-weight:normal;">
                                <input type="hidden" name="config[<?= htmlspecialchars($fieldKey) ?>]" value="0">
                                <input type="checkbox" id="config_<?= htmlspecialchars($fieldKey) ?>" name="config[<?= htmlspecialchars($fieldKey) ?>]" value="1" <?= !empty($currentValue) ? 'checked' : '' ?>>
                                <?= htmlspecialchars($fieldLabel) ?>
                            </label>
                        <?php else: ?>
                            <input type="<?= htmlspecialchars($fieldType === 'number' ? 'number' : 'text') ?>" id="config_<?= htmlspecialchars($fieldKey) ?>" name="config[<?= htmlspecialchars($fieldKey) ?>]" class="form-control"
                                   value="<?= htmlspecialchars((string)$currentValue) ?>"
                                   <?= $fieldType === 'number' ? 'step="any"' : '' ?>
                                   <?= !empty($field['placeholder']) ? 'placeholder="' . htmlspecialchars($field['placeholder']) . '"' : '' ?>>
                        <?php endif; ?>
                        <?php if ($fieldDesc): ?>
                        <small style="color:#888;display:block;margin-top:4px;"><?= htmlspecialchars($fieldDesc) ?></small>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-group">
                        <label><?= __('No config schema defined') ?></label>
                        <p style="color:#888;"><?= __('This module has not registered any configuration fields.') ?></p>
                    </div>
                <?php endif; ?>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary"><?= __('Save Configuration') ?></button>
                    <a href="/admin/modules" class="btn btn-secondary"><?= __('Cancel') ?></a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>