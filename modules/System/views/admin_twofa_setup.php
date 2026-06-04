<?php
/**
 * 2FA Setup View (Admin)
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var string $qrCodeUrl OTP Auth URL for QR code */
/** @var string $secret Base32 secret */
/** @var array $recoveryCodes Array of recovery codes */
/** @var bool $isEnabled Whether 2FA is currently enabled */
/** @var string $csrfToken CSRF token */
/** @var array $adminMenu Admin menu links */
/** @var string|null $message Status message */
/** @var string|null $messageType Message type */
?>
<?php $pageTitle = 'Two-Factor Authentication Setup - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
    <style>
        .setup-steps { counter-reset: step; }
        .step { margin-bottom: 24px; padding-left: 36px; position: relative; }
        .step::before { counter-increment: step; content: counter(step); position: absolute; left: 0; top: 0; width: 26px; height: 26px; background: var(--xp-primary); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 600; }
        .step h3 { font-size: 15px; font-weight: 600; margin-bottom: 6px; }
        .step p { font-size: 13px; color: var(--xp-text-light); margin-bottom: 8px; line-height: 1.5; }
        .qr-container { text-align: center; padding: 16px; background: #f6f7f7; border: 1px solid var(--xp-border); border-radius: 4px; display: inline-block; }
        .qr-image { width: 200px; height: 200px; }
        .secret-box { background: #f6f7f7; border: 1px solid var(--xp-border); border-radius: 4px; padding: 12px; font-family: monospace; font-size: 16px; letter-spacing: 2px; text-align: center; user-select: all; margin: 8px 0; }
        .recovery-codes { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0; }
        .recovery-code { background: #f6f7f7; border: 1px solid var(--xp-border); border-radius: 3px; padding: 8px 12px; font-family: monospace; font-size: 13px; text-align: center; user-select: all; }
        .action-btn { display: inline-block; padding: 8px 20px; background: var(--xp-primary); color: #fff; border-radius: 3px; text-decoration: none; font-size: 13px; border: none; cursor: pointer; }
        .action-btn:hover { opacity: 0.9; }
        .action-btn.danger { background: var(--xp-error); }
        .action-btn.success { background: var(--xp-success); }
        .disabled-msg { text-align: center; padding: 40px; }
        .disabled-msg p { font-size: 14px; color: var(--xp-text-light); margin-bottom: 16px; }
        .inline-code { background: #f0f0f1; padding: 1px 4px; border-radius: 2px; font-family: monospace; font-size: 12px; }
        .download-link { font-size: 12px; color: var(--xp-text-light); margin-top: 4px; }
        .download-link a { color: var(--xp-primary); }
    </style>
            <h1>🔐 Two-Factor Authentication</h1>

            <?php if (!empty($message)): ?>
            <div class="notice notice-<?php echo htmlspecialchars($messageType ?? 'info'); ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>

            <?php if ($isEnabled): ?>
            <!-- 2FA is currently enabled -->
            <div class="section">
                <div class="section-header">✅ Two-Factor Authentication is Active</div>
                <div class="section-body">
                    <p style="font-size:14px;color:var(--xp-text-light);margin-bottom:16px;">
                        Your account is protected with two-factor authentication.
                        Every time you log in, you'll be asked for a verification code from your authenticator app.
                    </p>
                    <a href="/admin/twofa/disable" class="action-btn danger" onclick="return confirm('Are you sure you want to disable two-factor authentication?');">
                        🗑️ Disable Two-Factor Authentication
                    </a>
                </div>
            </div>
            <?php else: ?>
            <!-- 2FA not enabled - show setup wizard -->
            <div class="section">
                <div class="section-header">📱 Set Up Two-Factor Authentication</div>
                <div class="section-body">
                    <div class="setup-steps">
                        <div class="step">
                            <h3>Install an Authenticator App</h3>
                            <p>Download and install an authenticator app on your phone or desktop. Popular options include:</p>
                            <ul style="font-size:13px;color:var(--xp-text-light);margin-left:20px;line-height:1.8;">
                                <li><strong>Google Authenticator</strong> (iOS / Android)</li>
                                <li><strong>Authy</strong> (iOS / Android / Desktop)</li>
                                <li><strong>Microsoft Authenticator</strong> (iOS / Android)</li>
                                <li><strong>1Password</strong> / <strong>Bitwarden</strong> (if you use a password manager)</li>
                            </ul>
                        </div>

                        <div class="step">
                            <h3>Scan the QR Code</h3>
                            <p>Open your authenticator app and scan the QR code below to add your account.</p>
                            <div class="qr-container">
                                <div class="qr-image">
                                    <?php if (!empty($qrCodeUrl)): ?>
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode($qrCodeUrl); ?>" 
                                         alt="QR Code for 2FA setup"
                                         style="width:200px;height:200px;"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                                    <div style="display:none;font-size:13px;color:var(--xp-text-light);padding:40px;text-align:center;">
                                        Could not load QR code image.<br>
                                        Use the manual setup code below.
                                    </div>
                                    <?php else: ?>
                                    <div style="width:200px;height:200px;display:flex;align-items:center;justify-content:center;font-size:13px;color:var(--xp-text-light);">
                                        Click "Enable 2FA" to generate setup QR code.
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <p class="download-link">
                                If you can't scan the QR code, use this setup key manually:
                            </p>
                            <div class="secret-box"><?php echo htmlspecialchars($secret ?? 'N/A'); ?></div>
                        </div>

                        <div class="step">
                            <h3>Verify Your Setup</h3>
                            <p>Enter the 6-digit code from your authenticator app to verify everything is working correctly.</p>
                            <form method="post" action="/admin/twofa/enable" style="margin-top:12px;">
                                <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <div style="display:flex;gap:8px;align-items:center;">
                                    <input type="text" name="code" placeholder="000000" maxlength="6" pattern="[0-9]*" inputmode="numeric" 
                                           style="padding:8px 12px;border:1px solid var(--xp-border);border-radius:3px;font-size:18px;letter-spacing:4px;font-family:monospace;width:140px;text-align:center;">
                                    <button type="submit" class="action-btn success">✅ Enable 2FA</button>
                                </div>
                            </form>
                        </div>

                        <?php if (!empty($recoveryCodes)): ?>
                        <div class="step">
                            <h3>Save Your Recovery Codes</h3>
                            <p>
                                <strong>Important:</strong> These recovery codes are <strong>only shown once</strong>.
                                Save them in a secure place. Each code can be used only once to access your account
                                if you lose access to your authenticator app.
                            </p>
                            <div class="recovery-codes">
                                <?php foreach ($recoveryCodes as $code): ?>
                                <div class="recovery-code"><?php echo htmlspecialchars($code); ?></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Generate new setup form -->
            <?php if (empty($qrCodeUrl)): ?>
            <div class="section">
                <div class="section-header">Getting Started</div>
                <div class="section-body" style="text-align:center;">
                    <p style="font-size:14px;color:var(--xp-text-light);margin-bottom:16px;">
                        Click the button below to generate a new 2FA setup code. You'll need an authenticator app
                        like Google Authenticator or Authy.
                    </p>
                    <form method="post" action="/admin/twofa/setup">
                        <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <button type="submit" class="action-btn">🔐 Generate Setup Code</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
<?php include __DIR__ . '/_admin_footer.php'; ?>