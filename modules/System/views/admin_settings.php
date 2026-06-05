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

                    <div class="section">
                        <div class="section-header">General Settings</div>
                        <div class="section-body">
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
                        </div>
                    </div>

                    <div class="section" style="margin-top:24px;">
                        <div class="section-header">🔐 Two-Factor Authentication</div>
                        <div class="section-body">
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="hidden" name="enforce_twofa" value="0">
                                    <input type="checkbox" name="enforce_twofa" value="1" <?= !empty($settings['enforce_twofa']) ? 'checked' : '' ?>>
                                    <span>Require two-factor authentication for all admin users</span>
                                </label>
                                <p class="help-text" style="font-size:12px;color:var(--xp-text-light);margin-top:4px;">
                                    When enabled, all users with the admin role must set up 2FA before accessing the admin panel.
                                </p>
                            </div>
                            <div class="form-group">
                                <label class="checkbox-label">
                                    <input type="hidden" name="enforce_twofa_editors" value="0">
                                    <input type="checkbox" name="enforce_twofa_editors" value="1" <?= !empty($settings['enforce_twofa_editors']) ? 'checked' : '' ?>>
                                    <span>Require two-factor authentication for editors</span>
                                </label>
                                <p class="help-text" style="font-size:12px;color:var(--xp-text-light);margin-top:4px;">
                                    When enabled, editors must also set up 2FA.
                                </p>
                            </div>
                            <div class="form-group">
                                <label for="twofa_issuer">2FA Issuer Name (shown in authenticator app)</label>
                                <input type="text" id="twofa_issuer" name="twofa_issuer" value="<?= htmlspecialchars($settings['twofa_issuer'] ?? 'XooPress') ?>">
                            </div>
                            <p style="font-size:13px;color:var(--xp-text-light);margin-top:8px;">
                                Users can manage their 2FA settings on the 
                                <a href="/admin/twofa/setup" style="color:var(--xp-primary);">Security page</a>.
                            </p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="margin-top:24px;">Save Settings</button>
                </form>
            </div>
<?php include __DIR__ . '/_admin_footer.php'; ?>