<?php
/**
 * Staging Overview View
 *
 * Shows all staged content items with publish/discard actions.
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $items */
/** @var int $total */
/** @var int $page */
/** @var int $totalPages */
/** @var string|null $currentType */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Staging - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
<?php include __DIR__ . '/_admin_header.php'; ?>
<div class="wrap">
    <h1>📦 Content Staging</h1>
    <p class="description">Stage content changes and generate preview links before publishing.</p>

    <?php $notice = $_SESSION['admin_notice'] ?? null; $noticeType = $_SESSION['admin_notice_type'] ?? 'info'; unset($_SESSION['admin_notice'], $_SESSION['admin_notice_type']); ?>
    <?php if ($notice): ?>
    <div class="notice notice-<?php echo htmlspecialchars($noticeType); ?>"><?php echo htmlspecialchars($notice); ?></div>
    <?php endif; ?>

    <!-- New Staging Form -->
    <div class="card" style="max-width:700px;margin-bottom:24px;">
        <h2>Stage Content</h2>
        <form method="post" action="/admin/staging/save">
            <?php echo '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars($csrfToken) . '">'; ?>
            <table class="form-table">
                <tr>
                    <th><label for="content_type">Type</label></th>
                    <td>
                        <select name="content_type" id="content_type">
                            <option value="post">Post</option>
                            <option value="page">Page</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="content_id">Post ID (optional)</label></th>
                    <td><input type="number" name="content_id" id="content_id" min="0" placeholder="Leave empty for new content"></td>
                </tr>
                <tr>
                    <th><label for="title">Title *</label></th>
                    <td><input type="text" name="title" id="title" required class="regular-text"></td>
                </tr>
                <tr>
                    <th><label for="slug">Slug</label></th>
                    <td><input type="text" name="slug" id="slug" class="regular-text" placeholder="Auto-generated if empty"></td>
                </tr>
                <tr>
                    <th><label for="content">Content</label></th>
                    <td><textarea name="content" id="content" rows="6" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="excerpt">Excerpt</label></th>
                    <td><textarea name="excerpt" id="excerpt" rows="2" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th><label for="status">Target Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary">Stage Content</button></p>
        </form>
    </div>

    <!-- Staged Items Table -->
    <h2>Staged Items (<?php echo $total; ?>)</h2>
    <?php if (empty($items)): ?>
    <p>No staged content yet.</p>
    <?php else: ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Type</th>
                <th>Post ID</th>
                <th>Author</th>
                <th>Updated</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo (int)$item['id']; ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($item['title']); ?></strong>
                    <?php if (!empty($item['meta_data'])): 
                        $meta = json_decode($item['meta_data'], true);
                        if (!empty($meta['status'])): ?>
                            <span class="badge badge-<?php echo htmlspecialchars($meta['status']); ?>"><?php echo htmlspecialchars($meta['status']); ?></span>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
                <td><?php echo htmlspecialchars($item['content_type'] ?? 'post'); ?></td>
                <td><?php echo $item['content_id'] ? (int)$item['content_id'] : '<em>New</em>'; ?></td>
                <td><?php echo htmlspecialchars($item['author_name'] ?? '—'); ?></td>
                <td><?php echo htmlspecialchars($item['updated_at'] ?? ''); ?></td>
                <td class="actions">
                    <a href="/admin/staging/publish/<?php echo (int)$item['id']; ?>" class="button button-primary button-small" onclick="return confirm('Publish this staged content?')">Publish</a>
                    <a href="/admin/staging/discard/<?php echo (int)$item['id']; ?>" class="button button-small" onclick="return confirm('Discard this staged content?')">Discard</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <?php if ($p === $page): ?>
                    <span class="current"><?php echo $p; ?></span>
                <?php else: ?>
                    <a href="/admin/staging?page=<?php echo $p; ?>" class="page-numbers"><?php echo $p; ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>
</body>
</html>