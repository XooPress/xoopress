<?php
/**
 * Search Results View
 *
 * @package XooPress
 * @subpackage Modules\Content\Views
 */

/** @var array $results */
$items = $results['items'] ?? [];
$total = $results['total'] ?? 0;
$page = $results['page'] ?? 1;
$totalPages = $results['totalPages'] ?? 1;
$query = $results['query'] ?? '';
$mode = $results['mode'] ?? 'none';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search: <?php echo htmlspecialchars($query ?: 'All'); ?> - XooPress</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .search-form { max-width: 600px; margin: 0 auto 24px; display: flex; gap: 8px; }
        .search-form input[type="text"] { flex: 1; padding: 10px 14px; border: 2px solid var(--xp-border); border-radius: 6px; font-size: 15px; }
        .search-form input[type="text"]:focus { border-color: var(--xp-primary); outline: none; }
        .search-form button { padding: 10px 24px; background: var(--xp-primary); color: #fff; border: none; border-radius: 6px; font-size: 14px; cursor: pointer; }
        .search-form button:hover { opacity: 0.9; }
        .search-meta { text-align: center; font-size: 13px; color: var(--xp-text-light); margin-bottom: 20px; }
        .search-highlight { background: #fff3cd; padding: 1px 3px; border-radius: 2px; }
        .result-item { background: var(--xp-card-bg); border: 1px solid var(--xp-border); border-radius: 6px; padding: 20px; margin-bottom: 12px; }
        .result-item h3 { margin: 0 0 6px; font-size: 16px; }
        .result-item h3 a { color: var(--xp-primary); text-decoration: none; }
        .result-item h3 a:hover { text-decoration: underline; }
        .result-item .meta { font-size: 12px; color: var(--xp-text-light); margin-bottom: 8px; }
        .result-item .excerpt { font-size: 14px; line-height: 1.5; color: var(--xp-text); }
        .result-item .excerpt mark { background: #fff3cd; padding: 1px 3px; border-radius: 2px; }
        .empty-state { text-align: center; padding: 48px 20px; color: var(--xp-text-light); }
        .empty-state .icon { font-size: 48px; margin-bottom: 12px; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a { display: inline-block; padding: 6px 12px; margin: 0 3px; border: 1px solid var(--xp-border); border-radius: 4px; text-decoration: none; font-size: 13px; color: var(--xp-text); }
        .pagination a.active { background: var(--xp-primary); color: #fff; border-color: var(--xp-primary); }
        .pagination a:hover:not(.active) { background: #f0f0f1; }
    </style>
</head>
<body>
<div class="wrap" style="max-width: 720px; margin: 40px auto; padding: 0 16px;">
    <h1 style="text-align:center;margin-bottom:8px;">🔍 Search</h1>

    <form class="search-form" method="get" action="/search">
        <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search posts, pages, and more…" minlength="2" autofocus>
        <button type="submit">Search</button>
    </form>

    <?php if (!empty($query)): ?>
    <div class="search-meta">
        <?php if ($total > 0): ?>
            Found <strong><?php echo $total; ?></strong> result<?php echo $total !== 1 ? 's' : ''; ?> for "<?php echo htmlspecialchars($query); ?>"
            <?php if ($mode === 'fulltext'): ?>
                <span style="margin-left:8px;font-size:11px;background:#e5f5fa;padding:2px 8px;border-radius:3px;">FULLTEXT</span>
            <?php elseif ($mode === 'like'): ?>
                <span style="margin-left:8px;font-size:11px;background:#f0f0f1;padding:2px 8px;border-radius:3px;">LIKE</span>
            <?php endif; ?>
        <?php else: ?>
            No results found for "<?php echo htmlspecialchars($query); ?>"
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($items)): ?>
        <?php foreach ($items as $item): ?>
        <div class="result-item">
            <h3>
                <?php if (!empty($item['url'])): ?>
                <a href="<?php echo htmlspecialchars($item['url']); ?>">
                    <?php echo !empty($item['title_highlighted']) ? $item['title_highlighted'] : htmlspecialchars($item['title']); ?>
                </a>
                <?php else: ?>
                    <?php echo !empty($item['title_highlighted']) ? $item['title_highlighted'] : htmlspecialchars($item['title']); ?>
                <?php endif; ?>
            </h3>
            <div class="meta">
                Type: <?php echo htmlspecialchars($item['content_type'] ?? 'post'); ?>
                <?php if (!empty($item['meta_data'])): 
                    $meta = json_decode($item['meta_data'], true); ?>
                    <?php if (!empty($meta['status'])): ?> | Status: <?php echo htmlspecialchars($meta['status']); ?><?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="excerpt">
                <?php echo !empty($item['excerpt_highlighted']) ? $item['excerpt_highlighted'] : htmlspecialchars(mb_substr(strip_tags($item['content'] ?? ''), 0, 200)); ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="/search?q=<?php echo urlencode($query); ?>&page=<?php echo $p; ?>" class="<?php echo $p === $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

    <?php elseif (!empty($query)): ?>
    <div class="empty-state">
        <div class="icon">🔍</div>
        <p>No results found for your search.</p>
        <p style="font-size:13px;">Try different keywords or browse the site.</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>