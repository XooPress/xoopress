<!DOCTYPE html>
<html <?= $htmlAttrs ?? 'lang="en"' ?>>
<head>
    <meta charset="<?= $charset ?? 'UTF-8' ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($title) ? htmlspecialchars($title) . ' - ' : '' ?><?= htmlspecialchars($siteName ?? 'XooPress') ?></title>
    <meta name="theme-color" content="#2D8B57">
    <link rel="icon" type="image/x-icon" href="<?= $theme->getThemeUri() ?>/assets/images/xp-favicon.ico">
    <link rel="shortcut icon" href="<?= $theme->getThemeUri() ?>/assets/images/xp-favicon.ico">
    <link rel="stylesheet" href="<?= $theme->getStylesheetUrl() ?>">
    <?php if (!empty($head)) echo $head; ?>
</head>
<body>
    <header class="site-header">
        <div class="container">
            <div class="site-branding">
                <h1><a href="<?= $homeUrl ?? '/' ?>">🌿 <?= htmlspecialchars($siteName ?? 'XooPress') ?></a></h1>
                <?php if (!empty($siteDescription)): ?>
                <p class="site-description"><?= htmlspecialchars($siteDescription) ?></p>
                <?php endif; ?>
            </div>
            <nav class="main-navigation">
                <ul>
                    <li class="<?= is_current_nav('/') ? 'current-menu-item' : '' ?>"><a href="/"><?= __('Home') ?></a></li>
                    <li class="<?= is_current_nav('/posts') ? 'current-menu-item' : '' ?>"><a href="/posts"><?= __('Posts') ?></a></li>
                    <?php $navPages = getNavPages(); ?>
                    <?php foreach ($navPages as $navPage): ?>
                    <li class="<?= is_current_nav('/posts/' . (int)$navPage['id']) ? 'current-menu-item' : '' ?>"><a href="/posts/<?= (int)$navPage['id'] ?>"><?= htmlspecialchars($navPage['title']) ?></a></li>
                    <?php endforeach; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="<?= is_current_nav('/user/dashboard') ? 'current-menu-item' : '' ?>"><a href="/user/dashboard"><?= __('Dashboard') ?></a></li>
                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <li class="<?= is_current_nav('/admin') ? 'current-menu-item' : '' ?>"><a href="/admin"><?= __('Admin') ?></a></li>
                    <?php endif; ?>
                    <li><a href="/logout"><?= __('Logout') ?></a></li>
                    <?php else: ?>
                    <li class="<?= is_current_nav('/login') ? 'current-menu-item' : '' ?>"><a href="/login"><?= __('Login') ?></a></li>
                    <?php endif; ?>
                    <li class="language-switcher">
                        <select onchange="window.location.href='/locale/'+this.value">
                            <option value="en" <?= (!isset($_SESSION['locale']) || strpos($_SESSION['locale'] ?? '', 'en') === 0) ? 'selected' : '' ?>>English</option>
                            <option value="de" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'de') === 0 ? 'selected' : '' ?>>Deutsch</option>
                            <option value="fr" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'fr') === 0 ? 'selected' : '' ?>>Français</option>
                        </select>
                    </li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="site-content">
        <div class="container">
