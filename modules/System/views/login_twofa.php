<?php
/**
 * 2FA Verification View
 *
 * @package XooPress
 * @subpackage Modules\System\Views
 */

/** @var string $csrfToken CSRF token */
/** @var string|null $error Error message */
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication - XooPress</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f0f0f1; }
        .twofa-container { background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 420px; width: 100%; }
        h1 { font-size: 24px; font-weight: 400; text-align: center; margin: 0 0 8px; color: #1d2327; }
        p { text-align: center; color: #646970; font-size: 14px; margin: 0 0 24px; }
        .error { background: #fbeaea; border: 1px solid #dc3232; color: #b32d2e; padding: 8px 12px; border-radius: 4px; margin-bottom: 16px; font-size: 13px; text-align: center; }
        label { display: block; font-size: 14px; font-weight: 600; margin-bottom: 6px; color: #3c434a; }
        input[type="text"] { width: 100%; padding: 8px 12px; border: 1px solid #8c8f94; border-radius: 4px; font-size: 24px; text-align: center; letter-spacing: 8px; font-family: monospace; box-sizing: border-box; }
        input[type="text"]:focus { border-color: #2271b1; box-shadow: 0 0 0 1px #2271b1; outline: none; }
        button { width: 100%; padding: 10px; background: #2271b1; color: #fff; border: none; border-radius: 4px; font-size: 14px; cursor: pointer; margin-top: 16px; }
        button:hover { background: #135e96; }
        .info-box { background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 4px; padding: 12px; margin-bottom: 16px; font-size: 13px; color: #646970; }
        .info-box strong { color: #3c434a; }
        .back-link { display: block; text-align: center; margin-top: 16px; font-size: 13px; }
        .back-link a { color: #2271b1; text-decoration: none; }
        .back-link a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="twofa-container">
        <h1>🔐 Two-Factor Authentication</h1>
        <p>Enter the verification code from your authenticator app.</p>
        
        <?php if (!empty($error)): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>Tip:</strong> If you've lost access to your authenticator app, you can use one of your recovery codes instead.
        </div>
        
        <form method="post" action="/login/twofa">
            <input type="hidden" name="_csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <label for="code">Verification Code</label>
            <input type="text" id="code" name="code" placeholder="000000" maxlength="6" autocomplete="off" inputmode="numeric" pattern="[0-9]*" autofocus>
            <button type="submit">Verify</button>
        </form>
        
        <div class="back-link">
            <a href="/login">← Back to login</a>
        </div>
    </div>
</body>
</html>