<?php $pageTitle = 'Settings - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <header class="admin-header">
                <h1>Settings</h1>
            </header>
            <?php if (isset($message) && $message): ?>
                <div class="alert alert-<?= htmlspecialchars($messageType ?? 'info') ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            <div class="admin-form-container">
                <form method="POST" action="/admin/settings">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <div class="form-group">
                        <label for="site_name">Site Name</label>
                        <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'XooPress') ?>">
                    </div>
                    <div class="form-group">
                        <label for="site_description">Site Description</label>
                        <textarea id="site_description" name="site_description" rows="3"><?= htmlspecialchars($settings['site_description'] ?? 'A modular CMS combining XOOPS and WordPress concepts') ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="site_url">Site URL</label>
                        <input type="url" id="site_url" name="site_url" value="<?= htmlspecialchars($settings['site_url'] ?? 'http://localhost') ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>
