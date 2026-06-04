<?php $pageTitle = 'Translations - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
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
<?php include __DIR__ . '/_admin_footer.php'; ?>
