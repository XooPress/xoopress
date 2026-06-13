<?php $pageTitle = 'Marketplace Modules - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <div class="admin-header">
                <h2><?= __('Marketplace Modules') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="/admin/marketplace" class="btn btn-sm btn-secondary">← <?= __('Back') ?></a>
                    <form method="GET" action="/admin/marketplace/modules" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" name="search" placeholder="<?= __('Search modules...') ?>" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control" style="width:250px;">
                        <select name="category" class="form-control" style="width:auto;">
                            <option value=""><?= __('All Categories') ?></option>
                            <option value="seo" <?= ($category ?? '') === 'seo' ? 'selected' : '' ?>><?= __('SEO') ?></option>
                            <option value="security" <?= ($category ?? '') === 'security' ? 'selected' : '' ?>><?= __('Security') ?></option>
                            <option value="content" <?= ($category ?? '') === 'content' ? 'selected' : '' ?>><?= __('Content') ?></option>
                            <option value="analytics" <?= ($category ?? '') === 'analytics' ? 'selected' : '' ?>><?= __('Analytics') ?></option>
                            <option value="ecommerce" <?= ($category ?? '') === 'ecommerce' ? 'selected' : '' ?>><?= __('E-Commerce') ?></option>
                            <option value="forms" <?= ($category ?? '') === 'forms' ? 'selected' : '' ?>><?= __('Forms') ?></option>
                            <option value="social" <?= ($category ?? '') === 'social' ? 'selected' : '' ?>><?= __('Social') ?></option>
                            <option value="media" <?= ($category ?? '') === 'media' ? 'selected' : '' ?>><?= __('Media') ?></option>
                            <option value="tools" <?= ($category ?? '') === 'tools' ? 'selected' : '' ?>><?= __('Tools') ?></option>
                            <option value="performance" <?= ($category ?? '') === 'performance' ? 'selected' : '' ?>><?= __('Performance') ?></option>
                            <option value="backup" <?= ($category ?? '') === 'backup' ? 'selected' : '' ?>><?= __('Backup') ?></option>
                            <option value="membership" <?= ($category ?? '') === 'membership' ? 'selected' : '' ?>><?= __('Membership') ?></option>
                            <option value="ai" <?= ($category ?? '') === 'ai' ? 'selected' : '' ?>><?= __('AI / Machine Learning') ?></option>
                        </select>
                        <select name="status" class="form-control" style="width:auto;">
                            <option value=""><?= __('Any Status') ?></option>
                            <option value="active" <?= ($status ?? '') === 'active' ? 'selected' : '' ?>><?= __('Active') ?></option>
                            <option value="inactive" <?= ($status ?? '') === 'inactive' ? 'selected' : '' ?>><?= __('Inactive') ?></option>
                            <option value="beta" <?= ($status ?? '') === 'beta' ? 'selected' : '' ?>><?= __('Beta') ?></option>
                        </select>
                        <label style="display:flex;align-items:center;gap:4px;font-size:0.85rem;cursor:pointer;">
                            <input type="checkbox" name="featured" value="1" <?= !empty($featured) ? 'checked' : '' ?>>
                            <?= __('Featured only') ?>
                        </label>
                        <button type="submit" class="btn btn-primary btn-sm"><?= __('Filter') ?></button>
                        <?php
                            $hasActiveFilters = !empty($search) || !empty($category) || !empty($status) || !empty($featured);
                            if ($hasActiveFilters):
                        ?>
                        <a href="/admin/marketplace/modules" class="btn btn-sm btn-secondary"><?= __('Clear') ?></a>
                        <?php endif; ?>
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
                                <a href="#" onclick="event.preventDefault();openMarketplaceDetail('module','<?= htmlspecialchars($itemSlug) ?>')" class="btn btn-sm btn-secondary"><?= __('Details') ?></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($totalPages > 1): ?>
                <?php
                    $queryParams = [];
                    if (!empty($search)) $queryParams['search'] = $search;
                    if (!empty($category)) $queryParams['category'] = $category;
                    if (!empty($status)) $queryParams['status'] = $status;
                    if (!empty($featured)) $queryParams['featured'] = $featured;
                    $queryString = http_build_query($queryParams);
                ?>
                <div style="margin-top:20px;display:flex;justify-content:center;gap:8px;">
                    <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= !empty($queryString) ? '&' . htmlspecialchars($queryString) : '' ?>" class="btn btn-sm btn-secondary">← <?= __('Previous') ?></a>
                    <?php endif; ?>
                    <span style="padding:6px 12px;"><?= __('Page') ?> <?= $page ?> <?= __('of') ?> <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($queryString) ? '&' . htmlspecialchars($queryString) : '' ?>" class="btn btn-sm btn-secondary"><?= __('Next') ?> →</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
<?php include __DIR__ . '/_admin_footer.php'; ?>