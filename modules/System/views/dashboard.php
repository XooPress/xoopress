<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <div style="float:right;">
                <select onchange="window.location.href='/locale/'+this.value" style="padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem;">
                    <option value="en" <?= (isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'en') === 0) || !isset($_SESSION['locale']) ? 'selected' : '' ?>>English</option>
                    <option value="de" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'de') === 0 ? 'selected' : '' ?>>Deutsch</option>
                    <option value="fr" <?= isset($_SESSION['locale']) && strpos($_SESSION['locale'], 'fr') === 0 ? 'selected' : '' ?>>Français</option>
                </select>
            </div>
            <img src="/images/xp-logo.svg" alt="XooPress Logo" class="site-logo" style="height:48px;margin-bottom:10px;">
            <h1><?= htmlspecialchars($siteName) ?></h1>
            <p class="version"><?= __('Version') ?> <?= htmlspecialchars($version) ?></p>
        </header>
        <main class="main">
            <div class="hero">
                <h2><?= __('Welcome to XooPress') ?></h2>
                <p><?= __('A modular CMS combining the best of XOOPS and WordPress.') ?></p>
                <div class="actions">
                    <a href="/login" class="btn btn-primary"><?= __('Login') ?></a>
                    <a href="/register" class="btn btn-secondary"><?= __('Register') ?></a>
                    <a href="/admin" class="btn btn-secondary"><?= __('Admin Dashboard') ?></a>
                </div>
            </div>

            <div class="features">
                <div class="feature-card">
                    <h3><?= __('Modular Architecture') ?></h3>
                    <p><?= __('Extend functionality with plug-and-play modules.') ?></p>
                </div>
                <div class="feature-card">
                    <h3><?= __('MVC Pattern') ?></h3>
                    <p><?= __('Clean separation of concerns with Model-View-Controller.') ?></p>
                </div>
                <div class="feature-card">
                    <h3><?= __('i18n Ready') ?></h3>
                    <p><?= __('Full internationalization support with gettext.') ?></p>
                </div>
                <div class="feature-card">
                    <h3><?= __('PDO Database') ?></h3>
                    <p><?= __('Secure database access with prepared statements.') ?></p>
                </div>
            </div>

            <?php if (!empty($posts)): ?>
            <div class="posts-section" style="margin-top:30px;">
                <h2 style="margin-bottom:20px;font-size:1.5rem;"><?= __('Latest Posts') ?></h2>
                <?php foreach ($posts as $post): ?>
                <article class="post-card" style="margin-bottom:20px;padding:20px;border:1px solid #e0e0e0;border-radius:6px;">
                    <h3 style="margin-bottom:8px;">
                        <a href="/posts/<?= htmlspecialchars($post['id']) ?>" style="color:#0073aa;text-decoration:none;">
                            <?= htmlspecialchars($post['title']) ?>
                        </a>
                    </h3>
                    <div class="post-meta" style="font-size:0.85rem;color:#888;margin-bottom:10px;">
                        <?php if (!empty($post['category_name'])): ?>
                        <span class="category" style="background:#f0f0f0;padding:2px 8px;border-radius:3px;">
                            <?= htmlspecialchars($post['category_name']) ?>
                        </span> &middot;
                        <?php endif; ?>
                        <span class="date"><?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                    </div>
                    <p class="excerpt" style="color:#555;line-height:1.6;">
                        <?php if (!empty($post['excerpt'])): ?>
                        <?= htmlspecialchars($post['excerpt']) ?>
                        <?php else: ?>
                        <?= htmlspecialchars(mb_substr(strip_tags($post['content'] ?? ''), 0, 200)) ?>...
                        <?php endif; ?>
                    </p>
                    <a href="/posts/<?= htmlspecialchars($post['id']) ?>" class="read-more" style="color:#0073aa;font-size:0.9rem;">
                        <?= __('Read More') ?> →
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> XooPress. <?= __('All rights reserved.') ?></p>
        </footer>
    </div>
</body>
</html>