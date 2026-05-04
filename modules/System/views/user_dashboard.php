<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('My Dashboard') ?> - <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .user-dashboard {
            max-width: 800px;
            margin: 0 auto;
        }
        .user-dashboard h1 {
            font-size: 1.5rem;
            color: #23282d;
            margin-bottom: 5px;
        }
        .user-dashboard .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 20px;
        }
        .dashboard-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 24px;
            text-align: center;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .dashboard-card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .dashboard-card .card-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            display: block;
        }
        .dashboard-card h3 {
            font-size: 1.1rem;
            color: #23282d;
            margin-bottom: 8px;
        }
        .dashboard-card p {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 16px;
        }
        .dashboard-card .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .user-info-box {
            background: #f8f9fa;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 30px;
        }
        .user-info-box h2 {
            font-size: 1.1rem;
            color: #23282d;
            margin-bottom: 12px;
        }
        .user-info-box .info-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            border-bottom: 1px solid #eee;
            font-size: 0.9rem;
        }
        .user-info-box .info-row:last-child {
            border-bottom: none;
        }
        .user-info-box .info-label {
            color: #888;
            font-weight: 600;
        }
        .user-info-box .info-value {
            color: #23282d;
        }
        .current-theme-badge {
            display: inline-block;
            background: #e8f0fe;
            color: #0073aa;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .alert {
            padding: 12px 18px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success {
            background: #ecf7ed;
            color: #46b450;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #fbeaea;
            color: #dc3232;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <div style="float:right;">
                <a href="/logout" style="padding:8px 16px;background:#dc3232;color:#fff;border-radius:4px;text-decoration:none;font-size:0.85rem;"><?= __('Logout') ?></a>
            </div>
            <img src="/images/xp-logo.svg" alt="XooPress Logo" style="height:48px;margin-bottom:10px;">
            <h1><?= htmlspecialchars($siteName) ?></h1>
            <p class="version"><?= __('My Dashboard') ?></p>
        </header>
        <main class="main">
            <div class="user-dashboard">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <div class="user-info-box">
                    <h2><?= __('Account Information') ?></h2>
                    <div class="info-row">
                        <span class="info-label"><?= __('Username') ?></span>
                        <span class="info-value"><?= htmlspecialchars($username) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('Display Name') ?></span>
                        <span class="info-value"><?= htmlspecialchars($displayName) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('Email') ?></span>
                        <span class="info-value"><?= htmlspecialchars($email) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('Role') ?></span>
                        <span class="info-value"><?= htmlspecialchars(ucfirst($role)) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label"><?= __('Your Theme') ?></span>
                        <span class="info-value">
                            <?php if (!empty($userTheme)): ?>
                                <span class="current-theme-badge"><?= htmlspecialchars($userThemeName) ?></span>
                                <a href="/user/themes" style="margin-left:10px;font-size:0.8rem;color:#0073aa;"><?= __('Change') ?></a>
                            <?php else: ?>
                                <span style="color:#888;"><?= __('Default site theme') ?></span>
                                <a href="/user/themes" style="margin-left:10px;font-size:0.8rem;color:#0073aa;"><?= __('Choose') ?></a>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="dashboard-cards">
                    <div class="dashboard-card">
                        <span class="card-icon">🎨</span>
                        <h3><?= __('Choose Theme') ?></h3>
                        <p><?= __('Pick a personal theme that only affects your browsing experience.') ?></p>
                        <a href="/user/themes" class="btn btn-primary"><?= __('Browse Themes') ?></a>
                    </div>
                    <div class="dashboard-card">
                        <span class="card-icon">🌐</span>
                        <h3><?= __('Language') ?></h3>
                        <p><?= __('Select your preferred language for the interface.') ?></p>
                        <select onchange="window.location.href='/locale/'+this.value" style="padding:10px;border:1px solid #ddd;border-radius:4px;font-size:0.9rem;width:100%;max-width:200px;">
                            <option value="en" <?= (!isset($_SESSION['locale']) || strpos($_SESSION['locale'] ?? '', 'en') === 0) ? 'selected' : '' ?>>English</option>
                            <option value="de" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'de') === 0 ? 'selected' : '' ?>>Deutsch</option>
                            <option value="fr" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'fr') === 0 ? 'selected' : '' ?>>Français</option>
                        </select>
                    </div>
                    <?php $role = $_SESSION['user_role'] ?? ''; ?>
                    <?php if (in_array($role, ['admin', 'editor', 'author'])): ?>
                    <div class="dashboard-card">
                        <span class="card-icon">📝</span>
                        <h3><?= __('My Posts') ?></h3>
                        <p><?= __('Write and manage your posts.') ?></p>
                        <a href="/admin/posts" class="btn btn-secondary"><?= __('Manage Posts') ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if ($role === 'admin'): ?>
                    <div class="dashboard-card">
                        <span class="card-icon">⚙️</span>
                        <h3><?= __('Admin Dashboard') ?></h3>
                        <p><?= __('Manage site settings, users, posts, and themes.') ?></p>
                        <a href="/admin" class="btn btn-secondary"><?= __('Go to Admin') ?></a>
                    </div>
                    <?php endif; ?>
                    <div class="dashboard-card">
                        <span class="card-icon">🏠</span>
                        <h3><?= __('Back to Site') ?></h3>
                        <p><?= __('Return to the main site homepage.') ?></p>
                        <a href="/" class="btn btn-secondary"><?= __('Visit Site') ?></a>
                    </div>
                </div>
            </div>
        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> XooPress. <?= __('All rights reserved.') ?></p>
        </footer>
    </div>
</body>
</html>