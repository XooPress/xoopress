<?php $pageTitle = ($isNew ? 'Add New ' : 'Edit ') . 'User - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <header class="admin-header">
                <h1><?= $isNew ? 'Add New User' : 'Edit User' ?></h1>
                <a href="/admin/users" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← Back to Users</a>
            </header>
            <form method="POST" action="/admin/users/save" style="max-width:600px;">
                <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
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
                    <div class="password-field-wrapper">
                        <input type="password" id="password" name="password" <?= $isNew ? 'required' : '' ?> minlength="8" style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1rem;">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility(this)" aria-label="Show password">
                            <svg class="eye-closed" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                <line x1="1" y1="1" x2="23" y2="23"/>
                            </svg>
                            <svg class="eye-open" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
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
<?php include __DIR__ . '/_admin_footer.php'; ?>