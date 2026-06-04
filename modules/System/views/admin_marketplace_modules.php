<?php $pageTitle = 'Marketplace Modules - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <div class="admin-header">
                <h2><?= __('Marketplace Modules') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="/admin/marketplace" class="btn btn-sm btn-secondary">← <?= __('Back') ?></a>
                    <form method="GET" action="/admin/marketplace/modules" style="display:flex;gap:8px;align-items:center;">
                        <input type="text" name="search" placeholder="<?= __('Search modules...') ?>" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control" style="width:250px;">
                        <button type="submit" class="btn btn-primary btn-sm"><?= __('Search') ?></button>
                    </form>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($items) && empty($error)): ?>
                <p style="text-align:center;padding:40px;color:#888;"><?= __('No modules found in marketplace.') ?></p>
            <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th><?= __('Module') ?></th>
                            <th><?= __('Version') ?></th>
                            <th><?= __('Author') ?></th>
                            <th><?= __('Description') ?></th>
                            <th><?= __('Actions') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                        <?php
                            $itemDesc = $item['description'] ?? $item['summary'] ?? $item['excerpt'] ?? $item['tagline'] ?? '';
                            $itemName = $item['name'] ?? $item['title'] ?? '';
                            $itemSlug = $item['slug'] ?? $itemName;
                            $itemVersion = $item['version'] ?? '1.0.0';
                            $itemAuthor = $item['author'] ?? $item['author_name'] ?? $item['publisher'] ?? '—';
                            $displayName = !empty($itemName) ? $itemName : $itemSlug;
                            $displayDesc = !empty($itemDesc) ? $itemDesc : __('No description available.');
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($displayName) ?></strong></td>
                            <td><?= htmlspecialchars($itemVersion) ?></td>
                            <td><?= htmlspecialchars($itemAuthor) ?></td>
                            <td><?= htmlspecialchars(mb_substr($displayDesc, 0, 150)) ?></td>
                            <td>
                                <a href="/admin/marketplace/install/module/<?= urlencode($itemSlug) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                <?php if (!empty($item['homepage'])): ?>
                                <a href="<?= htmlspecialchars($item['homepage']) ?>" target="_blank" class="btn btn-sm btn-secondary"><?= __('Info') ?></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

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
<?php include __DIR__ . '/_admin_footer.php'; ?>
