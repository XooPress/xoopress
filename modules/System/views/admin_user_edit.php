<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isNew ? 'Add New User' : 'Edit User' ?> - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
</head>
<body class="admin-page">
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <div class="admin-brand">
                <img src="/images/xp-logo.svg" alt="XooPress" style="height:32px;vertical-align:middle;margin-right:8px;">
                <span style="font-size:1.1rem;font-weight:700;">XooPress</span>
            </div>
            <ul class="admin-nav">
                <li><a href="/admin">Dashboard</a></li>
                <li><a href="/admin/posts">Posts</a></li>
                <li><a href="/admin/pages">Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users" class="active">Users</a></li>
                <li><a href="/admin/themes">Themes</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1><?= $isNew ? 'Add New User' : 'Edit User' ?></h1>
                <a href="/admin/users" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← Back to Users</a>
            </header>
            <form method="POST" action="/admin/users/save" style="max-width:600px;">
                <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= $user['id'] ?? '' ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                </div>
                <div class="form-group">
                    <label for="display_name">Display Name</label>
                    <input type="text" id="display_name" name="display_name" value="<?= htmlspecialchars($user['display_name'] ?? '') ?>" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                    <div class="form-hint">Leave empty to use username.</div>
                </div>
                <div class="form-group">
                    <label for="password"><?= $isNew ? 'Password' : 'New Password' ?></label>
                    <input type="password" id="password" name="password" <?= $isNew ? 'required' : '' ?> minlength="8" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                    <div class="form-hint"><?= $isNew ? 'At least 8 characters.' : 'Leave empty to keep current password.' ?></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;">
                    <div class="form-group">
                        <label for="role">Role</label>
                        <select id="role" name="role" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="subscriber" <?= ($user['role'] ?? '') === 'subscriber' ? 'selected' : '' ?>>Subscriber</option>
                            <option value="author" <?= ($user['role'] ?? '') === 'author' ? 'selected' : '' ?>>Author</option>
                            <option value="editor" <?= ($user['role'] ?? '') === 'editor' ? 'selected' : '' ?>>Editor</option>
                            <option value="admin" <?= ($user['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            <option value="banned" <?= ($user['status'] ?? '') === 'banned' ? 'selected' : '' ?>>Banned</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="padding:12px 30px;"><?= $isNew ? 'Create User' : 'Update User' ?></button>
                    <a href="/admin/users" class="btn btn-secondary" style="padding:12px 30px;">Cancel</a>
                </div>
            </form>
        </main>
    </div>
</body>
</html>