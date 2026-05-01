<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - XooPress</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
    <div style="text-align:right;max-width:400px;margin:20px auto 0;">
        <select onchange="window.location.href='/locale/'+this.value" style="padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem;">
            <option value="en" <?= (isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'en') === 0) || !isset($_SESSION['locale']) ? 'selected' : '' ?>>English</option>
            <option value="de" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'de') === 0 ? 'selected' : '' ?>>Deutsch</option>
            <option value="fr" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'fr') === 0 ? 'selected' : '' ?>>Français</option>
        </select>
    </div>
    <div class="login-container">
        <div class="login-box">
            <img src="/images/xp-logo.svg" alt="XooPress" style="height:48px;margin-bottom:10px;">
            <h1>XooPress</h1>
            <h2><?= __('Login') ?></h2>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <form method="POST" action="/login">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label for="username"><?= __('Username') ?></label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password"><?= __('Password') ?></label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= __('Login') ?></button>
            </form>
            <p class="login-footer">
                <?= __("Don't have an account? Register") ?> <a href="/register"><?= __('Register') ?></a>
            </p>
            <p class="login-footer"><a href="/"><?= __('Back to Home') ?></a></p>
        </div>
    </div>
</body>
</html>