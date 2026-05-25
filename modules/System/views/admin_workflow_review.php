<?php
/**
 * Workflow Review Detail View
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $post */
/** @var array $history */
/** @var array $nextStatuses */
/** @var array $statusLabels */
/** @var closure $badgeClass */
/** @var string $csrfToken */
/** @var array $adminMenu */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review: <?php echo htmlspecialchars($post['title'] ?? ''); ?> - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .review-layout { display: grid; grid-template-columns: 1fr 340px; gap: 20px; }
        .review-actions { background: var(--xp-card-bg); border: 1px solid var(--xp-border); border-radius: 6px; padding: 20px; }
        .review-actions h3 { margin: 0 0 12px; font-size: 14px; }
        .review-actions .action-btn { display: block; width: 100%; padding: 10px; margin-bottom: 8px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; text-align: center; }
        .btn-approve { background: #46b450; color: #fff; }
        .btn-approve:hover { opacity: 0.9; }
        .btn-reject { background: #dc3232; color: #fff; }
        .btn-reject:hover { opacity: 0.9; }
        .btn-draft { background: #f0f0f1; color: #3c434a; }
        .btn-draft:hover { background: #e0e0e1; }
        .btn-publish { background: #2271b1; color: #fff; }
        .btn-publish:hover { opacity: 0.9; }
        .content-preview { background: var(--xp-card-bg); border: 1px solid var(--xp-border); border-radius: 6px; padding: 20px; margin-bottom: 20px; }
        .content-preview h2 { margin: 0 0 8px; }
        .content-preview .meta { font-size: 12px; color: var(--xp-text-light); margin-bottom: 16px; }
        .content-preview .body { font-size: 14px; line-height: 1.6; }
        .history-table { width: 100%; border-collapse: collapse; font-size: 12px; }
        .history-table th, .history-table td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--xp-border); }
        .history-table th { background: #f6f7f7; font-weight: 600; }
        .comment-box { width: 100%; min-height: 60px; padding: 8px; border: 1px solid var(--xp-border); border-radius: 4px; font-size: 13px; margin-bottom: 12px; resize: vertical; }
        .back-link { display: inline-block; margin-bottom: 16px; font-size: 13px; }
    </style>
</head>
<body class="admin-page">
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <div class="admin-brand">
                <img src="/images/xp-logo.svg" alt="XooPress" style="height:32px;vertical-align:middle;margin-right:8px;">
                <span style="font-size:1.1rem;font-weight:700;">XooPress</span>
            </div>
            <ul class="admin-nav">
                <?php if (!empty($adminMenu)): ?>
                <?php foreach ($adminMenu as $menuItem): ?>
                <?php
                    $menuUrl = $menuItem['url'] ?? '';
                    $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl);
                ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <a class="back-link" href="/admin/workflow">← Back to Workflow</a>
                <h1>Review: <?php echo htmlspecialchars($post['title'] ?? 'Untitled'); ?></h1>
            </header>

            <?php $message = $_SESSION['admin_notice'] ?? null; $messageType = $_SESSION['admin_notice_type'] ?? null; include __DIR__ . '/_notices.php'; ?>

            <div class="review-layout">
                <div>
                    <div class="content-preview">
                        <div class="meta">
                            Type: <?php echo htmlspecialchars($post['type'] ?? 'post'); ?> |
                            Author: <?php echo htmlspecialchars($post['author_id'] ?? '—'); ?> |
                            Status: <span class="badge badge-warning"><?php echo htmlspecialchars($statusLabels[$post['status']] ?? $post['status']); ?></span> |
                            Created: <?php echo htmlspecialchars($post['created_at'] ?? ''); ?>
                        </div>
                        <?php if (!empty($post['excerpt'])): ?>
                        <div class="body"><strong>Excerpt:</strong><br><?php echo nl2br(htmlspecialchars($post['excerpt'])); ?></div>
                        <hr>
                        <?php endif; ?>
                        <div class="body"><?php echo nl2br(htmlspecialchars($post['content'] ?? '')); ?></div>
                    </div>

                    <div class="section">
                        <div class="section-header">📜 Workflow History</div>
                        <div class="section-body">
                            <?php if (!empty($history)): ?>
                            <table class="history-table">
                                <thead><tr><th>Date</th><th>From</th><th>To</th><th>User</th><th>Comment</th></tr></thead>
                                <tbody>
                                    <?php foreach ($history as $entry): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($entry['created_at'] ?? ''); ?></td>
                                        <td><span class="badge badge-default"><?php echo htmlspecialchars($statusLabels[$entry['from_status']] ?? $entry['from_status'] ?: '—'); ?></span></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($statusLabels[$entry['to_status']] ?? $entry['to_status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($entry['user_name'] ?? '—'); ?></td>
                                        <td><?php echo htmlspecialchars($entry['comment'] ?? ''); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php else: ?>
                            <p style="color:var(--xp-text-light);font-size:13px;">No workflow history yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="review-actions">
                    <h3>Transition</h3>
                    <form method="post" action="/admin/workflow/transition">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="post_id" value="<?php echo (int)$post['id']; ?>">

                        <textarea class="comment-box" name="comment" placeholder="Add a comment (optional)"></textarea>

                        <?php if (in_array('pending_review', $nextStatuses)): ?>
                        <button type="submit" name="to_status" value="pending_review" class="action-btn btn-draft">📤 Submit for Review</button>
                        <?php endif; ?>
                        <?php if (in_array('approved', $nextStatuses)): ?>
                        <button type="submit" name="to_status" value="approved" class="action-btn btn-approve">✅ Approve</button>
                        <?php endif; ?>
                        <?php if (in_array('rejected', $nextStatuses)): ?>
                        <button type="submit" name="to_status" value="rejected" class="action-btn btn-reject">❌ Reject</button>
                        <?php endif; ?>
                        <?php if (in_array('published', $nextStatuses)): ?>
                        <button type="submit" name="to_status" value="published" class="action-btn btn-publish">🚀 Publish</button>
                        <?php endif; ?>
                        <?php if (in_array('draft', $nextStatuses)): ?>
                        <button type="submit" name="to_status" value="draft" class="action-btn btn-draft">✏️ Move to Draft</button>
                        <?php endif; ?>
                        <?php if (in_array('archived', $nextStatuses)): ?>
                        <button type="submit" name="to_status" value="archived" class="action-btn btn-draft">📦 Archive</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>