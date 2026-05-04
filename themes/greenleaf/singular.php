<?= $theme->getHeader() ?>

<div class="content-area">
    <?php if (isset($post)): ?>
        <article class="post-full">
            <header class="entry-header">
                <h1 class="entry-title"><?= htmlspecialchars($post['title']) ?></h1>
                <div class="entry-meta">
                    <span class="posted-on">📅 <?= __('Published on') ?> <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                    <?php if (!empty($post['category_name'])): ?>
                        <span class="cat-links"> | 📁 <?= htmlspecialchars($post['category_name']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($post['author_name'])): ?>
                        <span class="author"> | 👤 <?= htmlspecialchars($post['author_name']) ?></span>
                    <?php endif; ?>
                </div>
            </header>
            <div class="entry-content">
                <?= $post['rendered_content'] ?? $post['content'] ?>
            </div>
        </article>
    <?php else: ?>
        <div class="alert alert-info">
            <p><?= __('Post not found.') ?></p>
        </div>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>