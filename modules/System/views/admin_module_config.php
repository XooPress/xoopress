<?php $pageTitle = 'Module Config - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <div class="admin-header">
                <h2><?= __('Configuration') ?>: <?= htmlspecialchars(is_array($module) ? (is_array($module['definition'] ?? null) ? ($module['definition']['name'] ?? $module['name']) : $module['name']) : (is_string($module) ? $module : 'Unknown')) ?></h2>
                <a href="/admin/modules" class="btn btn-secondary btn-sm"><?= __('Back to Modules') ?></a>
            </div>

            <?php if (isset($message)): ?>
                <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <form method="POST" action="/admin/modules/config/save" class="admin-form" style="max-width:600px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="name" value="<?= htmlspecialchars(is_array($module) ? ($module['name'] ?? '') : (is_string($module) ? $module : '')) ?>">

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
<?php include __DIR__ . '/_admin_footer.php'; ?>
