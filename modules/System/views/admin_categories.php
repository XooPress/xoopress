<?php $pageTitle = 'Categories - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <header class="admin-header">
                <h1>Categories</h1>
            </header>

            <form method="POST" action="/admin/categories" style="margin-bottom:20px;padding:15px;background:#f9f9f9;border-radius:4px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <h3 style="margin-bottom:10px;">Add New Category</h3>
                <div style="display:flex;gap:10px;">
                    <input type="text" name="name" placeholder="Category name" required style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <input type="text" name="slug" placeholder="slug" style="flex:1;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <button type="submit" class="btn btn-primary" style="padding:8px 16px;">Add</button>
                </div>
            </form>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No categories yet.</td></tr>
                    <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($cat['name'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($cat['slug'] ?? '') ?></td>
                        <td><?= htmlspecialchars($cat['description'] ?? '') ?></td>
                        <td>
                            <a href="/admin/categories/delete/<?= $cat['id'] ?>" onclick="return confirm('Delete this category?')">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
<?php include __DIR__ . '/_admin_footer.php'; ?>
