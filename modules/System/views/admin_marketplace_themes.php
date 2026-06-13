<?php $pageTitle = 'Marketplace Themes - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <div class="admin-header">
                <h2><?= __('Marketplace Themes') ?></h2>
                <div style="display:flex;gap:10px;align-items:center;">
                    <a href="/admin/marketplace" class="btn btn-sm btn-secondary">← <?= __('Back') ?></a>
                    <form method="GET" action="/admin/marketplace/themes" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                        <input type="text" name="search" placeholder="<?= __('Search themes...') ?>" value="<?= htmlspecialchars($search ?? '') ?>" class="form-control" style="width:250px;">
                        <select name="category" class="form-control" style="width:auto;">
                            <option value=""><?= __('All Categories') ?></option>
                            <option value="blog" <?= ($category ?? '') === 'blog' ? 'selected' : '' ?>><?= __('Blog') ?></option>
                            <option value="business" <?= ($category ?? '') === 'business' ? 'selected' : '' ?>><?= __('Business') ?></option>
                            <option value="portfolio" <?= ($category ?? '') === 'portfolio' ? 'selected' : '' ?>><?= __('Portfolio') ?></option>
                            <option value="ecommerce" <?= ($category ?? '') === 'ecommerce' ? 'selected' : '' ?>><?= __('E-Commerce') ?></option>
                            <option value="magazine" <?= ($category ?? '') === 'magazine' ? 'selected' : '' ?>><?= __('Magazine') ?></option>
                            <option value="news" <?= ($category ?? '') === 'news' ? 'selected' : '' ?>><?= __('News') ?></option>
                            <option value="landing-page" <?= ($category ?? '') === 'landing-page' ? 'selected' : '' ?>><?= __('Landing Page') ?></option>
                            <option value="personal" <?= ($category ?? '') === 'personal' ? 'selected' : '' ?>><?= __('Personal') ?></option>
                            <option value="photography" <?= ($category ?? '') === 'photography' ? 'selected' : '' ?>><?= __('Photography') ?></option>
                            <option value="education" <?= ($category ?? '') === 'education' ? 'selected' : '' ?>><?= __('Education') ?></option>
                            <option value="nonprofit" <?= ($category ?? '') === 'nonprofit' ? 'selected' : '' ?>><?= __('Nonprofit') ?></option>
                            <option value="wiki" <?= ($category ?? '') === 'wiki' ? 'selected' : '' ?>><?= __('Wiki / Knowledge Base') ?></option>
                            <option value="dark" <?= ($category ?? '') === 'dark' ? 'selected' : '' ?>><?= __('Dark Theme') ?></option>
                            <option value="minimal" <?= ($category ?? '') === 'minimal' ? 'selected' : '' ?>><?= __('Minimal') ?></option>
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
                        <a href="/admin/marketplace/themes" class="btn btn-sm btn-secondary"><?= __('Clear') ?></a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <?php if (empty($items) && empty($error)): ?>
                <p style="text-align:center;padding:40px;color:#888;"><?= __('No themes found in marketplace.') ?></p>
            <?php else: ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;">
                    <?php foreach ($items as $item): ?>
                    <?php
                        $itemDesc = $item['description'] ?? $item['summary'] ?? $item['excerpt'] ?? $item['tagline'] ?? '';
                        $itemName = $item['name'] ?? $item['title'] ?? '';
                        $itemSlug = $item['slug'] ?? $itemName;
                        $itemVersion = $item['version'] ?? '1.0.0';
                        $itemAuthor = $item['author'] ?? $item['author_name'] ?? $item['publisher'] ?? '';
                        $displayName = !empty($itemName) ? $itemName : $itemSlug;
                        $displayDesc = !empty($itemDesc) ? $itemDesc : __('No description available.');
                    ?>
                    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;">
                        <?php if (!empty($item['screenshot'])): ?>
                        <img src="<?= htmlspecialchars($item['screenshot']) ?>" alt="" style="width:100%;height:160px;object-fit:cover;">
                        <?php else: ?>
                        <div style="width:100%;height:160px;background:linear-gradient(135deg,#f0f4ff,#e8f0fe);display:flex;align-items:center;justify-content:center;font-size:3rem;">🎨</div>
                        <?php endif; ?>
                        <div style="padding:15px;">
                            <h3 style="margin:0 0 4px;font-size:1rem;"><?= htmlspecialchars($displayName) ?></h3>
                            <div style="color:#888;font-size:0.8rem;margin-bottom:8px;">
                                v<?= htmlspecialchars($itemVersion) ?>
                                <?php if (!empty($itemAuthor)): ?>
                                &middot; <?= htmlspecialchars($itemAuthor) ?>
                                <?php endif; ?>
                            </div>
                            <p style="font-size:0.85rem;color:#666;margin:0 0 12px;"><?= htmlspecialchars(mb_substr($displayDesc, 0, 200)) ?></p>
                            <div style="display:flex;gap:6px;">
                                <a href="/admin/marketplace/install/theme/<?= urlencode($itemSlug) ?>" class="btn btn-sm btn-success"><?= __('Install') ?></a>
                                <a href="#" onclick="event.preventDefault();openMarketplaceDetail('theme','<?= htmlspecialchars($itemSlug) ?>')" class="btn btn-sm btn-secondary"><?= __('Details') ?></a>
                                <?php if (!empty($item['demo_url'])): ?>
                                <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" class="btn btn-sm btn-secondary"><?= __('Preview') ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

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