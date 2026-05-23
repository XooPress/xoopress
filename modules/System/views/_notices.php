<?php
/**
 * Admin Notices Partial
 * 
 * Displays success/error/warning/info messages stored in session.
 * Include this file in any admin view to show flash messages.
 * 
 * Usage: <?php include __DIR__ . '/_notices.php'; ?>
 * 
 * Convention: set $_SESSION['admin_notice'] and $_SESSION['admin_notice_type']
 * where type is one of: success, error, warning, info
 */
$notice = $_SESSION['admin_notice'] ?? $message ?? null;
$noticeType = $_SESSION['admin_notice_type'] ?? $messageType ?? null;
unset($_SESSION['admin_notice'], $_SESSION['admin_notice_type']);

if ($notice): ?>
<div class="admin-notice admin-notice-<?= htmlspecialchars($noticeType ?? 'info') ?>">
    <span class="admin-notice-icon">
        <?php if ($noticeType === 'success'): ?>✓<?php elseif ($noticeType === 'error'): ?>✕<?php elseif ($noticeType === 'warning'): ?>⚠<?php else: ?>ℹ<?php endif; ?>
    </span>
    <span class="admin-notice-text"><?= htmlspecialchars((string)$notice) ?></span>
    <button type="button" class="admin-notice-dismiss" onclick="this.parentElement.remove()">&times;</button>
</div>
<?php endif; ?>