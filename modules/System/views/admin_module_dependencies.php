<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Module Dependencies') ?> - <?= __('XooPress Admin') ?></title>
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .dep-tree { list-style:none; padding-left:0; margin:0; }
        .dep-tree ul { list-style:none; padding-left:24px; margin:8px 0; border-left:2px solid #e0e0e0; }
        .dep-tree li { padding:4px 0; position:relative; }
        .dep-tree li::before { content:'\2514'; position:absolute; left:-20px; color:#bbb; }
        .dep-node { display:inline-flex; align-items:center; gap:8px; padding:6px 12px; border-radius:6px; font-size:0.9rem; }
        .dep-node.installed-active { background:#e8f5e9; border:1px solid #a5d6a7; }
        .dep-node.installed-inactive { background:#fff3e0; border:1px solid #ffcc80; }
        .dep-node.not-installed { background:#f5f5f5; border:1px solid #e0e0e0; color:#999; }
        .dep-node.missing { background:#ffebee; border:1px solid #ef9a9a; color:#c62828; }
        .dep-node.cycle { background:#f3e5f5; border:1px solid #ce93d8; }
        .reverse-card { background:#f9f9f9; border:1px solid #e0e0e0; border-radius:8px; padding:16px; margin-bottom:12px; }
        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:0.75rem; font-weight:600; }
        .badge-green { background:#e8f5e9; color:#2e7d32; }
        .badge-orange { background:#fff3e0; color:#e65100; }
        .badge-gray { background:#f5f5f5; color:#757575; }
    </style>
</head>
<body class="admin-page">
    <div class="admin-layout">
        <nav class="admin-sidebar">
            <div class="admin-brand">
                <img src="/images/xp-logo.svg" alt="XooPress" style="height:32px;vertical-align:middle;margin-right:8px;">
                <span style="font-size:1.1rem;font-weight:700;">XooPress</span>
            </div>
            <ul class="admin-nav">
                <?php if (!empty($adminMenu)): ?>
                <?php foreach ($adminMenu as $menuItem): ?>
                <?php
                    $menuUrl = $menuItem['url'] ?? '';
                    $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl);
                ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/"><?= __('View Site') ?></a></li>
                <li><a href="/logout"><?= __('Logout') ?></a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <div class="admin-header">
                <h2><?= __('Dependencies') ?>: <?= htmlspecialchars(is_array($module['definition']) ? ($module['definition']['name'] ?? $module['name']) : $module['name']) ?></h2>
                <a href="/admin/modules" class="btn btn-secondary btn-sm"><?= __('Back to Modules') ?></a>
            </div>

            <div style="display:flex;gap:24px;flex-wrap:wrap;">
                <!-- Dependency Tree -->
                <div style="flex:2;min-width:300px;">
                    <h3><?= __('Dependency Tree') ?></h3>
                    <?php if ($graph): ?>
                    <?php
                        $renderDepNode = function($node) {
                            $cls = '';
                            $label = '';
                            if (!empty($node['missing'])) {
                                $cls = 'missing';
                                $label = 'Missing';
                            } elseif (!empty($node['cycle'])) {
                                $cls = 'cycle';
                                $label = 'Cycle';
                            } elseif ($node['installed'] && $node['active']) {
                                $cls = 'installed-active';
                                $label = 'Active';
                            } elseif ($node['installed']) {
                                $cls = 'installed-inactive';
                                $label = 'Inactive';
                            } else {
                                $cls = 'not-installed';
                                $label = 'Not Installed';
                            }
                            return '<span class="dep-node ' . $cls . '">'
                                . htmlspecialchars($node['name'])
                                . ' <span style="font-size:0.75rem;color:#666;">v' . htmlspecialchars($node['version']) . '</span>'
                                . ' <span class="dep-status">' . $label . '</span>'
                                . '</span>';
                        };
                        
                        $renderTree = function($node) use (&$renderTree, $renderDepNode) {
                            $html = '<li>' . $renderDepNode($node);
                            if (!empty($node['children'])) {
                                $html .= '<ul>';
                                foreach ($node['children'] as $child) {
                                    $html .= $renderTree($child);
                                }
                                $html .= '</ul>';
                            }
                            $html .= '</li>';
                            return $html;
                        };
                    ?>
                    <ul class="dep-tree">
                        <?= $renderTree($graph) ?>
                    </ul>
                    <?php else: ?>
                    <p style="color:#888;"><?= __('No dependency graph available.') ?></p>
                    <?php endif; ?>
                </div>

                <!-- Reverse Dependencies -->
                <div style="flex:1;min-width:250px;">
                    <h3><?= __('Modules That Depend on This') ?></h3>
                    <?php if (!empty($reverseDeps)): ?>
                        <?php foreach ($reverseDeps as $rd): ?>
                        <div class="reverse-card">
                            <strong><?= htmlspecialchars($rd['name']) ?></strong>
                            <span style="float:right;">
                                <?php if ($rd['installed'] && $rd['active']): ?>
                                    <span class="badge badge-green"><?= __('Active') ?></span>
                                <?php elseif ($rd['installed']): ?>
                                    <span class="badge badge-orange"><?= __('Inactive') ?></span>
                                <?php else: ?>
                                    <span class="badge badge-gray"><?= __('Not Installed') ?></span>
                                <?php endif; ?>
                            </span>
                            <div style="font-size:0.85rem;color:#666;margin-top:4px;">v<?= htmlspecialchars($rd['version']) ?></div>
                            <a href="/admin/modules/dependencies/<?= urlencode($rd['name']) ?>" style="font-size:0.85rem;"><?= __('View Dependencies') ?></a>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <p style="color:#888;"><?= __('No modules depend on this one.') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>