<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - XooPress</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <script src="/js/xoopress.js" defer></script>
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
            <h2><?= __('Create Account') ?></h2>
            <?php if (!empty($errors)): ?>
                <?php foreach ($errors as $error): ?>
                    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            <?php endif; ?>
            <form method="POST" action="/register">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="form-group">
                    <label for="username"><?= __('Username') ?></label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required minlength="3" autofocus>
                </div>
                <div class="form-group">
                    <label for="email"><?= __('Email') ?></label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="password"><?= __('Password') ?></label>
                    <div class="password-field-wrapper">
                        <input type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this)" aria-label="Show password">
                            <svg class="eye-closed" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                            <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Password Strength Bar -->
                    <div class="password-strength-container">
                        <div class="password-strength-track">
                            <div id="password-strength-bar" class="password-strength-bar"></div>
                        </div>
                        <span id="password-strength-text" class="password-strength-label"></span>
                    </div>
                    <!-- Requirements Checklist -->
                    <ul class="password-requirements">
                        <li><span id="req-length" class="req-unmet">&#10007;</span> <?= __('At least 8 characters') ?></li>
                        <li><span id="req-upper" class="req-unmet">&#10007;</span> <?= __('At least 1 uppercase letter') ?></li>
                        <li><span id="req-special" class="req-unmet">&#10007;</span> <?= __('At least 1 special character') ?></li>
                    </ul>
                </div>
                <div class="form-group">
                    <label for="password_confirm"><?= __('Confirm Password') ?></label>
                    <div class="password-field-wrapper">
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this)" aria-label="Show password">
                            <svg class="eye-closed" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                            <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><?= __('Register') ?></button>
            </form>
            <p class="login-footer">
                <?= __('Already have an account? Login') ?> <a href="/login"><?= __('Login') ?></a>
            </p>
            <p class="login-footer"><a href="/"><?= __('Back to Home') ?></a></p>
        </div>
    </div>
</body>
</html>