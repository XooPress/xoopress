<?php $pageTitle = 'Dashboard - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <header class="admin-header">
                <h1>Dashboard</h1>
            </header>
            <div class="admin-stats">
                <div class="stat-card">
                    <h3>Users</h3>
                    <p class="stat-number"><?= (int)($userCount ?? 0) ?></p>
                </div>
                <div class="stat-card">
                    <h3>Modules</h3>
                    <p class="stat-number"><?= is_countable($modules) ? count($modules) : 0 ?></p>
                </div>
                <div class="stat-card">
                    <h3>Version</h3>
                    <p class="stat-number"><?= htmlspecialchars($version) ?></p>
                </div>
            </div>

            <h2 style="margin-top:30px;font-size:1.2rem;">Installed Modules</h2>
            <table class="admin-table" style="margin-top:10px;">
                <thead>
                    <tr>
                        <th>Module</th>
                        <th>Version</th>
                        <th>Description</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!is_array($modules) || empty($modules)): ?>
                    <tr><td colspan="4" style="text-align:center;color:#999;">No modules installed.</td></tr>
                    <?php else: ?>
                    <?php foreach ($modules as $mod): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($mod['name'] ?? '') ?></strong></td>
                        <td><?= htmlspecialchars($mod['version'] ?? '') ?></td>
                        <td><?= htmlspecialchars($mod['description'] ?? '') ?></td>
                        <td><?= htmlspecialchars($mod['author'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
<?php include __DIR__ . '/_admin_footer.php'; ?>