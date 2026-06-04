<?php $pageTitle = 'Menus - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
    <style>
        .menus-layout { display:flex; gap:20px; align-items:flex-start; }
        .menus-list { width:280px; flex-shrink:0; }
        .menus-editor { flex:1; }
        .menu-card { background:#fff; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:10px; }
        .menu-card-header { background:#f8f9fa; padding:10px 15px; font-weight:600; border-bottom:1px solid #e0e0e0; display:flex; justify-content:space-between; align-items:center; }
        .menu-card-body { padding:12px 15px; }
        .menu-card-actions { padding:8px 15px; border-top:1px solid #f0f0f0; display:flex; gap:8px; }
        .menu-items-list { list-style:none; padding:0; margin:0; }
        .menu-items-list li { padding:6px 10px; background:#f8f9fa; border:1px solid #e0e0e0; border-radius:3px; margin-bottom:4px; display:flex; justify-content:space-between; align-items:center; font-size:0.85rem; }
        .menu-items-list .sub-item { margin-left:24px; }
        .location-badge { display:inline-block; background:#e7f3ff; color:#0073aa; font-size:0.75rem; padding:2px 8px; border-radius:3px; margin:2px; }
        .add-item-form { display:flex; gap:8px; flex-wrap:wrap; align-items:center; padding:12px 0; }
        .add-item-form input, .add-item-form select { padding:6px 10px; border:1px solid #ddd; border-radius:3px; font-size:0.85rem; }
        .add-item-form input[type="text"] { flex:1; min-width:120px; }
    </style>
            <div class="admin-header"><h1>Menus</h1></div>
            <?php include __DIR__ . '/_notices.php'; ?>
            <div class="menus-layout">
                <div class="menus-list">
                    <h2 style="font-size:1.1rem;margin-bottom:10px;">Navigation Menus</h2>
                    <form method="POST" action="/admin/menus/create" style="display:flex;gap:8px;margin-bottom:15px;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="text" name="name" placeholder="New menu name" required style="flex:1;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem;">
                        <button type="submit" class="btn btn-primary btn-sm">Create</button>
                    </form>
                    <?php if (empty($menus)): ?>
                    <p style="color:#999;font-size:0.9rem;">No menus yet. Create one above.</p>
                    <?php else: ?>
                    <?php foreach ($menus as $menu): ?>
                    <?php $isEditing = isset($editMenu) && (int)$editMenu['id'] === (int)$menu['id']; ?>
                    <div class="menu-card" style="<?= $isEditing ? 'border-color:#0073aa;' : '' ?>">
                        <div class="menu-card-header">
                            <span><?= htmlspecialchars($menu['name']) ?></span>
                            <?php if (!empty($menu['locations'])): ?>
                            <div><?php foreach (explode(',', $menu['locations']) as $loc): if (!empty($loc)): ?><span class="location-badge"><?= htmlspecialchars($loc) ?></span><?php endif; endforeach; ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="menu-card-body" style="font-size:0.85rem;color:#888;">
                            <?php if ($menu['description']): ?><p><?= htmlspecialchars($menu['description']) ?></p><?php endif; ?>
                            <p>Slug: <?= htmlspecialchars($menu['slug']) ?> | Items: <?= count($theme->getNavMenuItems((int)$menu['id'])) ?></p>
                        </div>
                        <div class="menu-card-actions">
                            <a href="/admin/menus/edit/<?= (int)$menu['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                            <a href="/admin/menus/delete/<?= (int)$menu['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this menu and all its items?')">Delete</a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (!empty($locations)): ?>
                    <h3 style="font-size:1rem;margin-top:20px;margin-bottom:10px;">Theme Locations</h3>
                    <ul style="list-style:none;padding:0;">
                    <?php foreach ($locations as $loc => $desc): ?>
                        <li style="padding:6px 10px;background:#fff;border:1px solid #e0e0e0;border-radius:4px;margin-bottom:4px;font-size:0.85rem;">
                            <strong><?= htmlspecialchars($desc) ?></strong>
                            <span style="color:#888;display:block;font-size:0.8rem;"><?= htmlspecialchars($loc) ?></span>
                        </li>
                    <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
                <?php if (isset($editMenu)): ?>
                <div class="menus-editor">
                    <h2 style="font-size:1.1rem;margin-bottom:10px;">Editing: <?= htmlspecialchars($editMenu['name']) ?></h2>
                    <form method="POST" action="/admin/menus/assign-location" style="display:flex;gap:8px;align-items:center;margin-bottom:15px;padding:10px;background:#f8f9fa;border-radius:4px;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="hidden" name="menu_id" value="<?= (int)$editMenu['id'] ?>">
                        <span style="font-size:0.85rem;">Assign to location:</span>
                        <select name="location" style="padding:6px;border:1px solid #ddd;border-radius:3px;font-size:0.85rem;">
                            <option value="">-- Select --</option>
                            <?php foreach ($locations as $loc => $desc): ?>
                            <option value="<?= htmlspecialchars($loc) ?>"><?= htmlspecialchars($desc) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Assign</button>
                    </form>
                    <h3 style="font-size:1rem;margin-bottom:8px;">Menu Items</h3>
                    <?php $menuItems = $theme->getNavMenuItems((int)$editMenu['id']); ?>
                    <?php if (empty($menuItems)): ?>
                    <p style="color:#999;font-size:0.9rem;">No items in this menu.</p>
                    <?php else: ?>
                    <ul class="menu-items-list">
                        <?php foreach ($menuItems as $item): ?>
                        <li class="<?= (int)$item['parent_id'] > 0 ? 'sub-item' : '' ?>">
                            <span><strong><?= htmlspecialchars($item['title']) ?></strong> — <?= htmlspecialchars($item['url']) ?></span>
                            <div>
                                <a href="/admin/menus/edit-item/<?= (int)$item['id'] ?>" class="btn btn-sm btn-secondary" style="font-size:0.75rem;">Edit</a>
                                <a href="/admin/menus/delete-item/<?= (int)$item['id'] ?>" class="btn btn-sm btn-danger" style="font-size:0.75rem;" onclick="return confirm('Delete?')">Delete</a>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    <form method="POST" action="/admin/menus/add-item" class="add-item-form" style="background:#f8f9fa;border-radius:4px;padding:12px;margin-top:15px;">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <input type="hidden" name="menu_id" value="<?= (int)$editMenu['id'] ?>">
                        <input type="text" name="title" placeholder="Link Text" required>
                        <input type="text" name="url" placeholder="URL" value="#">
                        <select name="parent_id">
                            <option value="0">Top Level</option>
                            <?php foreach ($menuItems as $item): ?>
                            <option value="<?= (int)$item['id'] ?>">— <?= htmlspecialchars($item['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-primary btn-sm">Add Item</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>