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
                        <span>📅 <?= htmlspecialchars($post['published_at'] ?? $post['created_at']) ?></span>
                        <?php if (!empty($post['category_name'])): ?>
                            <span>📁 <?= htmlspecialchars($post['category_name']) ?></span>
                        <?php endif; ?>
                    </div>
                </header>
                <div class="entry-summary">
                    <p><?= htmlspecialchars(mb_substr(strip_tags($post['content'] ?? ''), 0, 300)) ?>...</p>
                </div>
                <a href="/posts/<?= htmlspecialchars($post['id']) ?>" class="btn btn-primary"><?= __('Read More →') ?></a>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <p><?= __('No posts found.') ?></p>
    <?php endif; ?>
</div>

<?= $theme->getFooter() ?>