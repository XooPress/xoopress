<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - XooPress</title>
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body class="admin-page">
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <h2>XooPress Admin</h2>
            <ul class="admin-nav">
                <li><a href="/admin" class="active">Dashboard</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1>Dashboard</h1>
            </header>
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Users</h3>
                    <p class="stat-number">0</p>
                </div>
                <div class="stat-card">
                    <h3>Modules</h3>
                    <p class="stat-number">2</p>
                </div>
                <div class="stat-card">
                    <h3>Version</h3>
                    <p class="stat-number"><?= htmlspecialchars($version) ?></p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>