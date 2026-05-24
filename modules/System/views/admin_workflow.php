<?php
/**
 * Workflow/Approval Dashboard
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $pendingPosts */
/** @var array $stats */
/** @var string $currentType */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workflow - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .wf-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .wf-stat-card { background: var(--xp-card-bg); border: 1px solid var(--xp-border); border-radius: 6px; padding: 16px; text-align: center; }
        .wf-stat-card .count { font-size: 28px; font-weight: 700; color: var(--xp-primary); }
        .wf-stat-card .label { font-size: 11px; text-transform: uppercase; color: var(--xp-text-light); letter-spacing: 1px; margin-top: 4px; }
        .badge-default { background: #f0f0f1; color: #646970; }
        .tab-bar { display: flex; gap: 0; margin-bottom: 16px; border-bottom: 2px solid var(--xp-border); }
        .tab-bar a { padding: 8px 20px; text-decoration: none; font-size: 13px; color: var(--xp-text-light); border-bottom: 2px solid transparent; margin-bottom: -2px; }
        .tab-bar a.active { color: var(--xp-primary); border-bottom-color: var(--xp-primary); font-weight: 600; }
        .empty-state { text-align: center; padding: 48px 20px; color: var(--xp-text-light); }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .action-link { display: inline-block; padding: 4px 12px; background: var(--xp-primary); color: #fff; border-radius: 3px; text-decoration: none; font-size: 12px; }
        .action-link:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>📋 Content Workflow</h1>
    <?php if (!empty($stats)): ?>
    <div class="wf-stats">
        <div class="wf-stat-card"><div class="count"><?php echo (int)($stats['draft'] ?? 0); ?></div><div class="label">Drafts</div></div>
        <div class="wf-stat-card"><div class="count" style="color:var(--xp-warning);"><?php echo (int)($stats['pending_review'] ?? 0); ?></div><div class="label">Pending Review</div></div>
        <div class="wf-stat-card"><div class="count" style="color:var(--xp-primary);"><?php echo (int)($stats['approved'] ?? 0); ?></div><div class="label">Approved</div></div>
        <div class="wf-stat-card"><div class="count" style="color:var(--xp-success);"><?php echo (int)($stats['published'] ?? 0); ?></div><div class="label">Published</div></div>
    </div>
    <?php endif; ?>

    <div class="tab-bar">
        <a href="/admin/workflow?type=post" class="<?php echo $currentType === 'post' ? 'active' : ''; ?>">Posts</a>
        <a href="/admin/workflow?type=page" class="<?php echo $currentType === 'page' ? 'active' : ''; ?>">Pages</a>
    </div>

    <?php if (!empty($pendingPosts['items'])): ?>
    <table class="admin-table">
        <thead><tr><th>Title</th><th>Author</th><th>Status</th><th>Submitted</th><th>Actions</th></tr></thead>
        <tbody>
            <?php foreach ($pendingPosts['items'] as $item): ?>
            <tr>
                <td><a href="/admin/workflow/review/<?php echo (int)$item['id']; ?>"><?php echo htmlspecialchars($item['title'] ?? 'Untitled'); ?></a></td>
                <td><?php echo htmlspecialchars($item['author_name'] ?? '—'); ?></td>
                <td><span class="badge badge-warning"><?php echo \XooPress\Core\Workflow::getStatusLabels()[$item['status']] ?? $item['status']; ?></span></td>
                <td><?php echo htmlspecialchars($item['created_at'] ?? ''); ?></td>
                <td><a class="action-link" href="/admin/workflow/review/<?php echo (int)$item['id']; ?>">Review</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($pendingPosts['totalPages']) && $pendingPosts['totalPages'] > 1): ?>
    <div class="pagination">
        <?php for ($p = 1; $p <= $pendingPosts['totalPages']; $p++): ?>
        <a href="/admin/workflow?type=<?php echo urlencode($currentType); ?>&page=<?php echo $p; ?>" class="<?php echo ($p === ($pendingPosts['page'] ?? 1)) ? 'active' : ''; ?>"><?php echo $p; ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>

    <?php else: ?>
    <div class="empty-state">
        <div class="icon">✅</div>
        <p>No items pending review.</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>