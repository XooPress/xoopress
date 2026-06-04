<?php $pageTitle = 'Edit Translation - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
    <style>
        .trans-row { display:flex; gap:15px; padding:10px 0; border-bottom:1px solid #f0f0f0; align-items:flex-start; }
        .trans-row .source { flex:1; font-size:0.9rem; color:#333; padding-top:6px; }
        .trans-row .target { flex:1; }
        .trans-row .target textarea { width:100%; min-height:40px; padding:6px 8px; border:1px solid #ddd; border-radius:4px; font-size:0.9rem; }
        .translation-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; }
        .locale-badge { display:inline-block; padding:4px 12px; background:#e8f0fe; border-radius:12px; font-size:0.85rem; font-weight:600; }
    </style>
            <div class="admin-header">
                <h2><?= __('Edit Translations') ?>: <span class="locale-badge"><?= htmlspecialchars($locale) ?></span></h2>
                <a href="/admin/translations" class="btn btn-sm btn-secondary">← <?= __('Back to Translations') ?></a>
            </div>

            <?php if (!empty($stats)): ?>
            <div style="display:flex;gap:15px;margin-bottom:20px;flex-wrap:wrap;">
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:12px 20px;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:700;"><?= $stats['total'] ?></div>
                    <div style="font-size:0.8rem;color:#888;"><?= __('Total') ?></div>
                </div>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:12px 20px;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:700;color:#28a745;"><?= $stats['translated'] ?></div>
                    <div style="font-size:0.8rem;color:#888;"><?= __('Translated') ?></div>
                </div>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:12px 20px;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:700;color:#dc3545;"><?= $stats['untranslated'] ?></div>
                    <div style="font-size:0.8rem;color:#888;"><?= __('Untranslated') ?></div>
                </div>
                <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:12px 20px;text-align:center;">
                    <div style="font-size:1.2rem;font-weight:700;color:<?= $stats['percent'] > 80 ? '#28a745' : ($stats['percent'] > 50 ? '#ffc107' : '#dc3545') ?>;"><?= $stats['percent'] ?>%</div>
                    <div style="font-size:0.8rem;color:#888;"><?= __('Progress') ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($entries)): ?>
                <p style="text-align:center;padding:40px;color:#888;"><?= __('No translation entries found.') ?></p>
            <?php else: ?>
                <form method="POST" action="/admin/translations/save">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="locale" value="<?= htmlspecialchars($locale) ?>">

                    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;padding:20px;">
                        <div class="translation-header">
                            <strong><?= __('Source String') ?></strong>
                            <strong><?= __('Translation') ?></strong>
                        </div>

                        <?php foreach ($entries as $index => $entry): ?>
                        <?php if ($entry['header'] ?? false) continue; ?>
                        <div class="trans-row">
                            <div class="source">
                                <?= htmlspecialchars($entry['msgid']) ?>
                                <?php if (!empty($entry['msgid_plural'])): ?>
                                <br><small style="color:#888;">(<?= __('Plural') ?>: <?= htmlspecialchars($entry['msgid_plural']) ?>)</small>
                                <?php endif; ?>
                            </div>
                            <div class="target">
                                <input type="hidden" name="msg_<?= $index ?>_orig" value="<?= htmlspecialchars($entry['msgid']) ?>">
                                <textarea name="msg_<?= $index ?>" rows="2"><?= htmlspecialchars($entry['msgstr']) ?></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="margin-top:20px;">
                        <button type="submit" class="btn btn-primary"><?= __('Save Translations') ?></button>
                        <a href="/admin/translations" class="btn btn-secondary"><?= __('Cancel') ?></a>
                    </div>
                </form>
            <?php endif; ?>
<?php include __DIR__ . '/_admin_footer.php'; ?>
