<?php
/**
 * Admin Pagination Partial
 * 
 * Renders page navigation for admin listing tables.
 * Usage: <?php include __DIR__ . '/_pagination.php'; ?>
 * 
 * Expects: $page (current page), $totalPages, $baseUrl (URL pattern with :page placeholder)
 */
$page = max(1, (int)($page ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));
$baseUrl = $baseUrl ?? '';

if ($totalPages <= 1) return;

// Build query string preserving existing params
$existingParams = $_GET;
unset($existingParams['page']);

function paginationUrl(string $baseUrl, array $params, int $page): string {
    $params['page'] = $page;
    $query = http_build_query($params);
    // If baseUrl has :page, replace it; otherwise append as query param
    if (str_contains($baseUrl, ':page')) {
        $url = str_replace(':page', (string)$page, $baseUrl);
    } else {
        $url = $baseUrl . (str_contains($baseUrl, '?') ? '&' : '?') . http_build_query(array_merge($_GET, ['page' => $page]));
    }
    return $url;
}
?>
<div class="admin-pagination">
    <span class="admin-pagination-info">Page <?= $page ?> of <?= $totalPages ?></span>
    <div class="admin-pagination-links">
        <?php if ($page > 1): ?>
            <a href="<?= htmlspecialchars(paginationUrl($baseUrl, $existingParams, 1)) ?>" class="admin-pagination-link" title="First">&laquo;</a>
            <a href="<?= htmlspecialchars(paginationUrl($baseUrl, $existingParams, $page - 1)) ?>" class="admin-pagination-link" title="Previous">&lsaquo;</a>
        <?php endif; ?>
        
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        if ($start > 1) echo '<span class="admin-pagination-ellipsis">...</span>';
        for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= htmlspecialchars(paginationUrl($baseUrl, $existingParams, $i)) ?>" class="admin-pagination-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor;
        if ($end < $totalPages) echo '<span class="admin-pagination-ellipsis">...</span>'; ?>
        
        <?php if ($page < $totalPages): ?>
            <a href="<?= htmlspecialchars(paginationUrl($baseUrl, $existingParams, $page + 1)) ?>" class="admin-pagination-link" title="Next">&rsaquo;</a>
            <a href="<?= htmlspecialchars(paginationUrl($baseUrl, $existingParams, $totalPages)) ?>" class="admin-pagination-link" title="Last">&raquo;</a>
        <?php endif; ?>
    </div>
</div>
