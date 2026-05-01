<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts - XooPress</title>
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?= _('Posts') ?></h1>
            <a href="/" class="btn btn-secondary"><?= _('Back to Home') ?></a>
        </header>
        <main class="main">
            <?php if (empty($posts)): ?>
            <div class="hero">
                <h2><?= _('No Posts Yet') ?></h2>
                <p><?= _('There are no published posts at this time.') ?></p>
            </div>
            <?php else: ?>
            <div class="posts-list">
                <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <h2><a href="/posts/<?= $post['id'] ?>"><?= htmlspecialchars($post['title']) ?></a></h2>
                    <p class="post-meta"><?= _('Published on') ?> <?= htmlspecialchars($post['published_at']) ?></p>
                    <p class="post-excerpt"><?= htmlspecialchars($post['excerpt'] ?? '') ?></p>
                </article>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> XooPress</p>
        </footer>
    </div>
</body>
</html>