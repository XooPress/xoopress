<?= $theme->getHeader() ?>

<div class="content-area">
    <!-- Hero Section -->
    <div class="hero">
        <img 
            src="<?= $theme->getThemeUri() ?>/assets/images/xp-logo.svg" 
            alt="XooPress Logo" 
            class="site-logo" 
            style="height: 80px; width: auto; margin-bottom: 20px;"
        >
        <h1><?= __('Welcome to PurpleHaze') ?></h1>
        <p><?= __('Experience elegance and power with our sophisticated purple theme.') ?></p>
        <p class="version">
            <?= __('Version') ?> <?= defined('XOO_PRESS_VERSION') ? XOO_PRESS_VERSION : '1.0.0' ?>
        </p>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="actions">
                <a href="/login" class="btn btn-primary">
                    ✨ <?= __('Get Started') ?>
                </a>
                <a href="/register" class="btn btn-secondary">
                    🎨 <?= __('Explore Features') ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Features Grid -->
    <div class="features">
        <div class="feature-card">
            <h3>💎 <?= __('Premium Design') ?></h3>
            <p><?= __('Elegant glassmorphism effects and smooth animations for a modern feel.') ?></p>
        </div>
        <div class="feature-card">
            <h3>🎨 <?= __('Creative Freedom') ?></h3>
            <p><?= __('Express yourself with complete design control and flexible layouts.') ?></p>
        </div>
        <div class="feature-card">
            <h3>⚡ <?= __('Blazing Fast') ?></h3>
            <p><?= __('Optimized performance with minimal dependencies and clean code.') ?></p>
        </div>
        <div class="feature-card">
            <h3>🔒 <?= __('Secure & Safe') ?></h3>
            <p><?= __('Enterprise-grade security keeping your content protected.') ?></p>
        </div>
    </div>

    <!-- Posts Section -->
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
                        <span class="posted-on">
                            📅 <?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?>
                        </span>
                        <?php if (!empty($post['category_name'])): ?>
                            <span class="cat-links">
                                📁 <?= htmlspecialchars($post['category_name']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if (!empty($post['author_name'])): ?>
                            <span class="author">
                                👤 <?= htmlspecialchars($post['author_name']) ?>
                            </span>
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
                <a href="/posts/<?= htmlspecialchars($post['id']) ?>" class="btn btn-primary">
                    <?= __('Read More') ?> →
                </a>
            </article>
        <?php endforeach; ?>
        
        <!-- Pagination (if needed) -->
        <?php if (!empty($pagination)): ?>
            <div class="pagination">
                <?= $pagination ?>
            </div>
        <?php endif; ?>
        
    <?php elseif (isset($post)): ?>
        <!-- Single Post View -->
        <article class="post-full">
            <header class="entry-header">
                <h1 class="entry-title"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="entry-meta">
                    <span class="posted-on">
                        📅 <?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?>
                    </span>
                    <?php if (!empty($post['category_name'])): ?>
                        <span class="cat-links">
                            📁 <?= htmlspecialchars($post['category_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </header>
            <div class="entry-content">
                <?= $post['rendered_content'] ?? $post['content'] ?>
            </div>
        </article>
    <?php else: ?>
        <!-- No Content Message -->
        <div class="alert alert-info">
            <p><?= __('No posts found. Check back soon for new content!') ?></p>
        </div>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>
