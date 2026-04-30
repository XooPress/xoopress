<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> - Dashboard</title>
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><?= htmlspecialchars($siteName) ?></h1>
            <p class="version">Version <?= htmlspecialchars($version) ?></p>
        </header>
        <main class="main">
            <div class="hero">
                <h2>Welcome to XooPress</h2>
                <p>A modular CMS combining the best of XOOPS and WordPress.</p>
                <div class="actions">
                    <a href="/login" class="btn btn-primary">Login</a>
                    <a href="/admin" class="btn btn-secondary">Admin Dashboard</a>
                </div>
            </div>
            <div class="features">
                <div class="feature-card">
                    <h3>Modular Architecture</h3>
                    <p>Extend functionality with plug-and-play modules.</p>
                </div>
                <div class="feature-card">
                    <h3>MVC Pattern</h3>
                    <p>Clean separation of concerns with Model-View-Controller.</p>
                </div>
                <div class="feature-card">
                    <h3>i18n Ready</h3>
                    <p>Full internationalization support with gettext.</p>
                </div>
                <div class="feature-card">
                    <h3>PDO Database</h3>
                    <p>Secure database access with prepared statements.</p>
                </div>
            </div>
        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> XooPress. Licensed under GPLv3.</p>
        </footer>
    </div>
</body>
</html>