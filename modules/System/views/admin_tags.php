<?php $pageTitle = 'Tags - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <header class="admin-header">
                <h1>Tags</h1>
            </header>

            <?php if (!empty($editTag)): ?>
            <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:20px;margin-bottom:20px;">
                <h3 style="margin-top:0;">Edit Tag</h3>
                <form method="POST" action="/admin/tags/save" style="display:flex;gap:10px;align-items:flex-end;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="id" value="<?= (int)$editTag['id'] ?>">
                    <div class="form-group" style="flex:2;">
                        <label for="edit_name">Name</label>
                        <input type="text" id="edit_name" name="name" value="<?= htmlspecialchars($editTag['name'] ?? '') ?>" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="edit_slug">Slug</label>
                        <input type="text" id="edit_slug" name="slug" value="<?= htmlspecialchars($editTag['slug'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:8px 20px;">Update</button>
                    <a href="/admin/tags" class="btn btn-secondary" style="padding:8px 20px;">Cancel</a>
                </form>
            </div>
            <?php endif; ?>

            <div style="background:#f9f9f9;border:1px solid #ddd;border-radius:4px;padding:20px;margin-bottom:20px;">
                <h3 style="margin-top:0;">Add New Tag</h3>
                <form method="POST" action="/admin/tags/save" style="display:flex;gap:10px;align-items:flex-end;">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <div class="form-group" style="flex:2;">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter tag name" required style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" placeholder="Leave empty to auto-generate" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    </div>
                    <button type="submit" class="btn btn-primary" style="padding:8px 20px;">Add Tag</button>
                </form>
            </div>

            <div style="background:#fff;border:1px solid #ddd;border-radius:4px;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:12px 16px;text-align:left;border-bottom:1px solid #ddd;">Name</th>
                            <th style="padding:12px 16px;text-align:left;border-bottom:1px solid #ddd;">Slug</th>
                            <th style="padding:12px 16px;text-align:center;border-bottom:1px solid #ddd;">Posts</th>
                            <th style="padding:12px 16px;text-align:right;border-bottom:1px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tags)): ?>
                        <tr><td colspan="4" style="padding:20px;text-align:center;color:#888;">No tags found.</td></tr>
                        <?php else: ?>
                        <?php foreach ($tags as $tag): ?>
                        <tr style="border-top:1px solid #eee;">
                            <td style="padding:10px 16px;"><?= htmlspecialchars($tag['name'] ?? '') ?></td>
                            <td style="padding:10px 16px;color:#888;font-size:0.9rem;"><?= htmlspecialchars($tag['slug'] ?? '') ?></td>
                            <td style="padding:10px 16px;text-align:center;"><?= (int)($tag['count'] ?? 0) ?></td>
                            <td style="padding:10px 16px;text-align:right;">
                                <a href="/admin/tags/edit/<?= (int)$tag['id'] ?>" class="btn btn-sm" style="padding:4px 12px;">Edit</a>
                                <a href="/admin/tags/delete/<?= (int)$tag['id'] ?>" class="btn btn-sm btn-danger" style="padding:4px 12px;" onclick="return confirm('Delete tag <?= htmlspecialchars(addslashes($tag['name'] ?? ''), ENT_QUOTES) ?>?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>
