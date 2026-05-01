<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $post ? htmlspecialchars($post['title']) : __('Post Not Found') ?> - XooPress</title>
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>XooPress</h1>
            <a href="/posts" class="btn btn-secondary"><?= __('All Posts') ?></a>
        </header>
        <main class="main">
            <?php if ($post): ?>
            <article class="post-full">
                <h2><?= htmlspecialchars($post['title']) ?></h2>
                <p class="post-meta"><?= __('Published on') ?> <?= htmlspecialchars($post['published_at']) ?></p>
                <div class="post-content">
                    <?= $post['content'] ?>
                </div>
            </article>
            <?php else: ?>
            <div class="hero">
                <h2><?= __('Post Not Found') ?></h2>
                <p><?= __('The requested post could not be found.') ?></p>
                <a href="/posts" class="btn btn-primary"><?= __('All Posts') ?></a>
            </div>
            <?php endif; ?>
        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> XooPress</p>
        </footer>
    </div>
</body>
</html>