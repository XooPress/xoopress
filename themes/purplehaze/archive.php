<?= $theme->getHeader() ?>

<div class="content-area">
    <h1><?= __('Posts') ?></h1>

    <?php if (!empty($posts)): ?>
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
    <?php else: ?>
        <div class="alert alert-info">
            <p><?= __('No posts found. Check back soon for new content!') ?></p>
        </div>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>