<?= $theme->getHeader() ?>

<div class="content-area">
    <?php if (isset($post)): ?>
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
                    <?php if (!empty($post['author_name'])): ?>
                        <span class="author">
                            👤 <?= htmlspecialchars($post['author_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </header>
            <div class="entry-content">
                <?= $post['rendered_content'] ?? $post['content'] ?>
            </div>
        </article>

        <nav class="post-navigation" aria-label="<?= __('Post navigation') ?>">
            <div class="nav-links">
                <div class="nav-previous">
                    <?php if (isset($prev_post) && $prev_post): ?>
                        <a href="/posts/<?= (int)$prev_post['id'] ?>" rel="prev">
                            <span class="nav-subtitle">← <?= __('Previous Post') ?></span>
                            <span class="nav-title"><?= htmlspecialchars($prev_post['title']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="nav-next">
                    <?php if (isset($next_post) && $next_post): ?>
                        <a href="/posts/<?= (int)$next_post['id'] ?>" rel="next">
                            <span class="nav-subtitle"><?= __('Next Post') ?> →</span>
                            <span class="nav-title"><?= htmlspecialchars($next_post['title']) ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    <?php else: ?>
        <div class="alert alert-info">
            <p><?= __('Post not found.') ?></p>
        </div>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>