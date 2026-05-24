<?php
/**
 * Admin Header Partial
 *
 * Shared admin navigation sidebar and content wrapper.
 * Included by views that use the older admin layout pattern.
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var array $adminMenu */
?>
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
            <li><a href="/"><?= __('View Site') ?></a></li>
            <li><a href="/logout"><?= __('Logout') ?></a></li>
        </ul>
    </nav>
    <main class="admin-content">