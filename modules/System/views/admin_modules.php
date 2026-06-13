<?php $pageTitle = 'Modules - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <style>
                .update-badge { display:inline-block; background:#fff3cd; color:#856404; border:1px solid #ffc107; border-radius:10px; padding:1px 8px; font-size:0.75rem; font-weight:600; margin-left:4px; }
                .v-divider { width:1px; height:16px; background:#ccc; display:inline-block; vertical-align:middle; margin:0 4px; }
            </style>
            <div class="admin-header">
                <h2><?= __('Module Management') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
                    <a href="/admin/modules/check-updates" class="btn btn-secondary btn-sm"><?= __('Check for Updates') ?></a>
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
                    <?php if (empty($modulesList)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:40px;color:#888;">
                            <?= __('No modules found.') ?>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($modulesList as $module): ?>
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

                        $hasUpgrade = false;
                        if ($isInstalled) {
                            $installedVer = $module['version_db'] ?? null;
                            $availableVer = $def['version'] ?? null;
                            $hasUpgrade = ($installedVer && $availableVer && version_compare($availableVer, $installedVer, '>'));
                        }

                        $depCount = count($def['dependencies'] ?? []);
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($def['name'] ?? $name) ?></strong></td>
                        <td>
                            <?= htmlspecialchars($def['version'] ?? '—') ?>
                            <?php if ($isInstalled && $module['version_db'] && $module['version_db'] !== ($def['version'] ?? null)): ?>
                            <br><small style="color:#888;">(DB: <?= htmlspecialchars($module['version_db']) ?>)</small>
                            <?php endif; ?>
                            <?php if (!empty($module['remote_has_update'])): ?>
                            <br><span class="update-badge">v<?= htmlspecialchars($module['remote_latest_version']) ?> available</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($def['author'] ?? '—') ?></td>
                        <td>
                            <?= htmlspecialchars($def['description'] ?? '') ?>
                            <?php if ($depCount > 0): ?>
                            <br><small style="color:#888;"><?= $depCount ?> <?= __('dependencies') ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                        <td class="actions-cell" style="min-width:250px;">
                            <div style="display:flex;gap:4px;flex-wrap:wrap;">
                                <a href="/admin/modules/edit/<?= urlencode($name) ?>" class="btn btn-sm btn-primary"><?= __('Edit') ?></a>
                                <?php if (!$isInstalled): ?>
                                    <a href="/admin/modules/install/<?= urlencode($name) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                    <a href="/admin/modules/delete/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Delete this module from filesystem?') ?>')"><?= __('Delete') ?></a>
                                <?php elseif ($isActive): ?>
                                    <a href="/admin/modules/deactivate/<?= urlencode($name) ?>" class="btn btn-sm btn-warning"><?= __('Deactivate') ?></a>
                                    <a href="/admin/modules/uninstall/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Uninstall this module? This will drop its database tables.') ?>')"><?= __('Uninstall') ?></a>
                                <?php else: ?>
                                    <a href="/admin/modules/activate/<?= urlencode($name) ?>" class="btn btn-sm btn-success"><?= __('Activate') ?></a>
                                    <a href="/admin/modules/uninstall/<?= urlencode($name) ?>" class="btn btn-sm btn-danger" onclick="return confirm('<?= __('Uninstall this module? This will drop its database tables.') ?>')"><?= __('Uninstall') ?></a>
                                <?php endif; ?>
                                <a href="/admin/modules/dependencies/<?= urlencode($name) ?>" class="btn btn-sm btn-secondary"><?= __('Deps') ?></a>
                                <a href="/admin/modules/config/<?= urlencode($name) ?>" class="btn btn-sm btn-secondary"><?= __('Config') ?></a>
                                <?php if ($hasUpgrade): ?>
                                    <a href="/admin/modules/upgrade/<?= urlencode($name) ?>" class="btn btn-sm btn-warning" onclick="return confirm('<?= __('Upgrade this module?') ?>')"><?= __('Upgrade') ?> <span class="update-badge"><?= $def['version'] ?></span></a>
                                <?php endif; ?>
                                <?php if ($isInstalled): ?>
                                <a href="/admin/modules/export/<?= urlencode($name) ?>" class="btn btn-sm btn-secondary"><?= __('Export') ?></a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <div style="margin-top:30px;padding:20px;background:#f9f9f9;border:1px solid #e0e0e0;border-radius:8px;">
                <h3><?= __('Clone Module') ?></h3>
                <form method="POST" action="/admin/modules/clone" class="admin-form" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <div class="form-group" style="margin:0;">
                        <label for="clone_name"><?= __('Source Module') ?></label>
                        <select id="clone_name" name="name" class="form-control" required>
                            <option value=""><?= __('— Select —') ?></option>
                            <?php foreach ($modulesList as $module): ?>
                            <option value="<?= htmlspecialchars($module['name']) ?>"><?= htmlspecialchars($module['definition']['name'] ?? $module['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin:0;">
                        <label for="clone_new_name"><?= __('New Module Name') ?></label>
                        <input type="text" id="clone_new_name" name="new_name" class="form-control" placeholder="my-new-module" required pattern="[a-zA-Z0-9_-]+">
                    </div>
                    <button type="submit" class="btn btn-primary"><?= __('Clone Module') ?></button>
                </form>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>
