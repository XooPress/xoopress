<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revision Comparison - <?= htmlspecialchars($post['title'] ?? '') ?> - XooPress</title>
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .diff-section { border:1px solid #ddd; border-radius:4px; margin-bottom:15px; }
        .diff-section h3 { margin:0; padding:10px 15px; background:#f1f1f1; border-bottom:1px solid #ddd; font-size:0.95rem; }
        .diff-body { padding:15px; }
        .diff-body .old { background:#fff0f0; border-left:4px solid #c00; padding:12px; margin-bottom:8px; }
        .diff-body .new { background:#f0fff0; border-left:4px solid #0a0; padding:12px; }
        .diff-label { display:inline-block; padding:2px 8px; border-radius:3px; font-size:0.75rem; font-weight:600; margin-bottom:6px; }
        .diff-label.old { background:#c00; color:#fff; }
        .diff-label.new { background:#0a0; color:#fff; }
        .no-change { color:#888; font-style:italic; }
        .revision-meta { background:#f9f9f9; border:1px solid #ddd; border-radius:4px; padding:15px; margin-bottom:20px; }
        .revision-meta dt { font-weight:600; color:#555; font-size:0.85rem; }
        .revision-meta dd { margin:0 0 8px 0; }
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
                <?php $menuUrl = $menuItem['url'] ?? ''; $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl); ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Revision Comparison</h1>
                <div style="display:flex;gap:10px;">
                    <a href="/admin/posts/revisions/<?= (int)($post['id'] ?? 0) ?>" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← All Revisions</a>
                    <a href="/admin/posts/revision/restore/<?= (int)($post['id'] ?? 0) ?>/<?= (int)($revision['id'] ?? 0) ?>" class="btn btn-primary" style="font-size:0.85rem;padding:8px 16px;" onclick="return confirm('Restore this revision?')">Restore This Revision</a>
                </div>
            </header>

            <div class="revision-meta">
                <dl>
                    <dt>Revision Date</dt>
                    <dd><?= htmlspecialchars($revision['revision_date'] ?? '') ?></dd>
                    <dt>Author</dt>
                    <dd><?= htmlspecialchars($revision['author_name'] ?? 'Unknown') ?></dd>
                    <dt>Status at Revision</dt>
                    <dd><?= htmlspecialchars($revision['status'] ?? 'Unknown') ?></dd>
                </dl>
            </div>

            <?php foreach (['title' => 'Title', 'content' => 'Content', 'excerpt' => 'Excerpt'] as $field => $label): ?>
            <div class="diff-section">
                <h3><?= $label ?></h3>
                <div class="diff-body">
                    <?php if (!empty($diff[$field]['changed'])): ?>
                    <div class="old">
                        <div class="diff-label old">Old (Revision)</div>
                        <?php if ($field === 'content'): ?>
                            <div style="max-height:300px;overflow-y:auto;"><?= nl2br(htmlspecialchars($diff[$field]['old'])) ?></div>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($diff[$field]['old'])) ?>
                        <?php endif; ?>
                    </div>
                    <div class="new">
                        <div class="diff-label new">New (Current)</div>
                        <?php if ($field === 'content'): ?>
                            <div style="max-height:300px;overflow-y:auto;"><?= nl2br(htmlspecialchars($diff[$field]['new'])) ?></div>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($diff[$field]['new'])) ?>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="no-change">No change in <?= strtolower($label) ?>.</div>
                    <div style="padding:10px;background:#f9f9f9;border-radius:4px;margin-top:8px;">
                        <?php if ($field === 'content'): ?>
                            <div style="max-height:200px;overflow-y:auto;"><?= nl2br(htmlspecialchars($revision[$field] ?? '')) ?></div>
                        <?php else: ?>
                            <?= nl2br(htmlspecialchars($revision[$field] ?? '')) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </main>
    </div>
</body>
</html>