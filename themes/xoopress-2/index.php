<?= $theme->getHeader() ?>

<div class="content-area">
    <div class="hero">
        <img src="<?= $theme->getThemeUri() ?>/assets/images/xp-logo.svg" alt="XooPress Logo" class="site-logo" style="height:64px;margin-bottom:15px;">
        <h1><?= __('Welcome to XooPress') ?></h1>
        <p><?= __('A modular CMS combining the best of XOOPS and WordPress.') ?></p>
        <p class="version" style="color:#999;font-size:0.9rem;margin-bottom:20px;"><?= __('Version') ?> <?= defined('XOO_PRESS_VERSION') ? XOO_PRESS_VERSION : '1.0.0' ?></p>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="actions">
            <a href="/login" class="btn btn-primary"><?= __('Login') ?></a>
            <a href="/register" class="btn btn-secondary"><?= __('Register') ?></a>
        </p>
        <?php endif; ?>
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
        <h1><?= __('Posts') ?></h1>
        <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <header class="entry-header">
                <h2 class="entry-title">
                    <a href="/posts/<?= htmlspecialchars($post['id']) ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h2>
                <div class="entry-meta">
                    <span class="posted-on"><?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                    <?php if (!empty($post['category_name'])): ?>
                    <span class="cat-links"> | <?= htmlspecialchars($post['category_name']) ?></span>
                    <?php endif; ?>
                </div>
            </header>
            <div class="entry-summary">
                <?php if (!empty($post['excerpt'])): ?>
                    <p><?= htmlspecialchars($post['excerpt']) ?></p>
                <?php else: ?>
                    <p><?= htmlspecialchars(mb_substr(strip_tags($post['content'] ?? ''), 0, 300)) ?>...</p>
                <?php endif; ?>
            </div>
            <a href="/posts/<?= htmlspecialchars($post['id']) ?>" class="btn btn-primary"><?= __('Read More') ?></a>
        </article>
        <?php endforeach; ?>
    <?php elseif (isset($post)): ?>
        <article class="post-full">
            <header class="entry-header">
                <h1 class="entry-title"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="entry-meta">
                    <span class="posted-on"><?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                </div>
            </header>
            <div class="entry-content">
                <?= $post['rendered_content'] ?? $post['content'] ?>
            </div>
        </article>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>
