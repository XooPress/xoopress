<?= $theme->getHeader() ?>

<div class="content-area">
    <div class="hero">
        <img src="<?= $theme->getThemeUri() ?>/assets/images/xp-logo.svg" alt="XooPress Logo" class="site-logo" style="height:64px;margin-bottom:15px;">
        <h1><?= __('Welcome to OrangeBlaze') ?></h1>
        <p><?= __('A vibrant CMS experience that sparks creativity and innovation.') ?></p>
        <p class="version" style="color:#FF6B35;font-size:0.9rem;margin-bottom:20px;"><?= __('Version') ?> <?= defined('XOO_PRESS_VERSION') ? XOO_PRESS_VERSION : '1.0.0' ?></p>
        <?php if (!isset($_SESSION['user_id'])): ?>
        <p class="actions">
            <a href="/login" class="btn btn-primary"><?= __('Get Started') ?></a>
            <a href="/register" class="btn btn-secondary"><?= __('Learn More') ?></a>
        </p>
        <?php endif; ?>
    </div>

    <div class="features">
        <div class="feature-card">
            <h3>🔥 <?= __('Blazing Fast') ?></h3>
            <p><?= __('Lightning-fast performance with optimized code.') ?></p>
        </div>
        <div class="feature-card">
            <h3>🎨 <?= __('Creative Freedom') ?></h3>
            <p><?= __('Express yourself with complete design control.') ?></p>
        </div>
        <div class="feature-card">
            <h3>🌍 <?= __('Global Ready') ?></h3>
            <p><?= __('Multi-language support out of the box.') ?></p>
        </div>
        <div class="feature-card">
            <h3>🔧 <?= __('Modular Power') ?></h3>
            <p><?= __('Extend functionality with plug-and-play modules.') ?></p>
        </div>
    </div>

    <?php if (!empty($posts)): ?>
        <h2><?= __('Latest Posts') ?></h2>
        <?php foreach ($posts as $post): ?>
        <article class="post-card">
            <header class="entry-header">
                <h2 class="entry-title">
                    <a href="/posts/<?= htmlspecialchars($post['id']) ?>">
                        <?= htmlspecialchars($post['title']) ?>
                    </a>
                </h2>
                <div class="entry-meta">
                    <span class="posted-on">📅 <?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                    <?php if (!empty($post['category_name'])): ?>
                    <span class="cat-links"> | 📂 <?= htmlspecialchars($post['category_name']) ?></span>
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
            <a href="/posts/<?= htmlspecialchars($post['id']) ?>" class="btn btn-primary"><?= __('Read More →') ?></a>
        </article>
        <?php endforeach; ?>
    <?php elseif (isset($post)): ?>
        <article class="post-full">
            <header class="entry-header">
                <h1 class="entry-title"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="entry-meta">
                    <span class="posted-on">📅 <?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                </div>
            </header>
            <div class="entry-content">
                <?= $post['rendered_content'] ?? $post['content'] ?>
            </div>
        </article>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>
