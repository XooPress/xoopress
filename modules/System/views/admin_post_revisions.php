<?php $pageTitle = 'Post Revisions - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
    <style>
        .revision-item { display:flex; align-items:center; gap:15px; padding:12px 16px; border:1px solid #eee; border-radius:4px; margin-bottom:8px; background:#fff; }
        .revision-item:hover { border-color:#bbb; }
        .revision-item .rev-date { flex:1; font-weight:500; }
        .revision-item .rev-author { color:#888; font-size:0.9rem; }
        .revision-item .rev-status { font-size:0.8rem; padding:2px 8px; border-radius:3px; }
        .revision-item .rev-status.current { background:#d4edda; color:#155724; }
        .revision-item .rev-status.old { background:#f8f9fa; color:#666; }
        .revision-actions { display:flex; gap:6px; }
    </style>
            <header class="admin-header">
                <h1>Revisions: <?= htmlspecialchars($post['title'] ?? '') ?></h1>
                <div style="display:flex;gap:10px;">
                    <a href="/admin/posts/edit/<?= (int)($post['id'] ?? 0) ?>" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← Back to Edit</a>
                </div>
            </header>

            <?php if (empty($revisions)): ?>
            <div style="padding:20px;text-align:center;color:#888;background:#f9f9f9;border:1px solid #ddd;border-radius:4px;">
                No revisions saved yet. Revisions are created automatically each time you update a post.
            </div>
            <?php else: ?>
            <div style="margin-bottom:15px;color:#888;font-size:0.9rem;">
                Showing <?= count($revisions) ?> revision(s). The system keeps the latest 25 revisions per post.
            </div>
            <?php foreach ($revisions as $rev): ?>
            <?php $isCurrent = (int)$rev['id'] === (int)$currentRevisionId; ?>
            <div class="revision-item">
                <div class="rev-date"><?= htmlspecialchars($rev['revision_date'] ?? '') ?></div>
                <div class="rev-author">by <?= htmlspecialchars($rev['author_name'] ?? 'Unknown') ?></div>
                <span class="rev-status <?= $isCurrent ? 'current' : 'old' ?>">
                    <?= $isCurrent ? 'Current' : htmlspecialchars(ucfirst($rev['status'] ?? 'draft')) ?>
                </span>
                <div class="revision-actions">
                    <a href="/admin/posts/revision/<?= (int)($post['id'] ?? 0) ?>/<?= (int)$rev['id'] ?>" class="btn btn-sm" style="padding:4px 10px;">View Diff</a>
                    <?php if (!$isCurrent): ?>
                    <a href="/admin/posts/revision/restore/<?= (int)($post['id'] ?? 0) ?>/<?= (int)$rev['id'] ?>" class="btn btn-sm btn-primary" style="padding:4px 10px;" onclick="return confirm('Restore this revision?')">Restore</a>
                    <a href="/admin/posts/revision/delete/<?= (int)($post['id'] ?? 0) ?>/<?= (int)$rev['id'] ?>" class="btn btn-sm btn-danger" style="padding:4px 10px;" onclick="return confirm('Delete this revision?')">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
<?php include __DIR__ . '/_admin_footer.php'; ?>
