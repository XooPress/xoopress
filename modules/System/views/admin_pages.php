<?php $pageTitle = 'Pages - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <header class="admin-header">
                <h1>Pages (<?= (int)($total ?? 0) ?>)</h1>
                <a href="/admin/pages/new" class="btn btn-primary" style="font-size:0.85rem;padding:8px 16px;">Add New Page</a>
            </header>

            <?php $message = $_SESSION['admin_notice'] ?? null; $messageType = $_SESSION['admin_notice_type'] ?? null; include __DIR__ . '/_notices.php'; ?>

            <form method="GET" action="/admin/pages" class="admin-search-bar">
                <input type="search" name="search" placeholder="Search pages..." value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                <select name="status">
                    <option value="">All Statuses</option>
                    <option value="draft" <?= ($_GET['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="published" <?= ($_GET['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                <?php if (!empty($_GET['search']) || !empty($_GET['status'])): ?>
                <a href="/admin/pages" class="btn btn-secondary btn-sm">Clear</a>
                <?php endif; ?>
            </form>

            <form method="POST" action="/admin/pages/bulk" id="bulkForm">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <div class="admin-bulk-actions">
                    <input type="checkbox" id="selectAll" class="admin-checkbox" onclick="document.querySelectorAll('.admin-checkbox[name=\"ids[]\"]').forEach(c => c.checked = this.checked)">
                    <label for="selectAll" style="font-size:0.85rem;color:#666;">Select All</label>
                    <select name="action">
                        <option value="">Bulk Actions</option>
                        <option value="publish">Publish</option>
                        <option value="draft">Move to Draft</option>
                        <option value="delete">Delete</option>
                    </select>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </div>

                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width:32px;"></th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Author</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pages)): ?>
                        <tr><td colspan="6" style="text-align:center;color:#999;">No pages found. <a href="/admin/pages/new">Create one</a>.</td></tr>
                        <?php else: ?>
                        <?php foreach ($pages as $page): ?>
                        <tr>
                            <td><input type="checkbox" name="ids[]" value="<?= $page['id'] ?>" class="admin-checkbox"></td>
                            <td><strong><?= htmlspecialchars($page['title'] ?? '') ?></strong></td>
                            <td><span class="status-badge status-<?= htmlspecialchars($page['status'] ?? 'draft') ?>"><?= htmlspecialchars($page['status'] ?? 'draft') ?></span></td>
                            <td><?= htmlspecialchars($page['author_name'] ?? '') ?></td>
                            <td style="font-size:0.85rem;color:#888;"><?= htmlspecialchars($page['published_at'] ?? $page['created_at'] ?? '') ?></td>
                            <td>
                                <a href="/admin/pages/edit/<?= $page['id'] ?>">Edit</a> |
                                <a href="/admin/posts/delete/<?= $page['id'] ?>" onclick="return confirm('Delete this page?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </form>

            <?php $baseUrl = '/admin/pages'; include __DIR__ . '/_pagination.php'; ?>
            <script>
                document.getElementById('bulkForm').addEventListener('submit', function(e) {
                    var action = this.querySelector('select[name="action"]').value;
                    var checked = this.querySelectorAll('.admin-checkbox[name="ids[]"]:checked').length;
                    if (!action) { e.preventDefault(); alert('Please select a bulk action.'); return; }
                    if (!checked) { e.preventDefault(); alert('Please select at least one item.'); return; }
                    if (action === 'delete' && !confirm('Delete the selected items?')) { e.preventDefault(); return; }
                });
            </script>
<?php include __DIR__ . '/_admin_footer.php'; ?>
