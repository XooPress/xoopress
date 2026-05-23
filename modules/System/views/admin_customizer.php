<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Customizer - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .customizer-layout { display:flex; gap:20px; align-items:flex-start; }
        .customizer-form { flex:1; max-width:700px; }
        .customizer-preview { width:400px; flex-shrink:0; position:sticky; top:20px; }
        .preview-frame { width:100%; height:500px; border:1px solid #e0e0e0; border-radius:8px; overflow:hidden; }
        .preview-frame iframe { width:100%; height:100%; border:none; }
        .setting-section { background:#fff; border:1px solid #e0e0e0; border-radius:6px; margin-bottom:15px; }
        .setting-section h3 { margin:0; padding:12px 15px; background:#f8f9fa; border-bottom:1px solid #e0e0e0; font-size:0.95rem; }
        .setting-body { padding:15px; }
        .setting-row { margin-bottom:12px; }
        .setting-row label { display:block; font-weight:500; margin-bottom:4px; font-size:0.85rem; color:#555; }
        .setting-row input[type="text"], .setting-row input[type="number"] { width:100%; padding:8px 10px; border:1px solid #ddd; border-radius:4px; font-size:0.9rem; }
        .setting-row input[type="color"] { width:60px; height:40px; padding:2px; border:1px solid #ddd; border-radius:4px; cursor:pointer; }
        .setting-row input:focus { outline:none; border-color:#0073aa; }
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
                <?php $menuUrl = $menuItem['url'] ?? ''; $isActive = (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) === $menuUrl); ?>
                <li><a href="<?= htmlspecialchars($menuUrl) ?>"<?= $isActive ? ' class="active"' : '' ?>><?= htmlspecialchars($menuItem['label'] ?? '') ?></a></li>
                <?php endforeach; ?>
                <?php endif; ?>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <div class="admin-header">
                <h1>Theme Customizer</h1>
                <p style="color:#666;font-size:0.9rem;">Customize the appearance of your active theme: <?= htmlspecialchars($themeName ?? '') ?></p>
            </div>
            <?php include __DIR__ . '/_notices.php'; ?>
            <div class="customizer-layout">
                <div class="customizer-form">
                    <form method="POST" action="/admin/themes/customize/save">
                        <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        
                        <div class="setting-section">
                            <h3>🎨 Colors</h3>
                            <div class="setting-body">
                                <div class="setting-row">
                                    <label>Primary Color</label>
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <input type="color" name="primary_color" value="<?= htmlspecialchars($settings['primary_color'] ?? '#4f46e5') ?>">
                                        <input type="text" name="primary_color_text" value="<?= htmlspecialchars($settings['primary_color'] ?? '#4f46e5') ?>" placeholder="#4f46e5" style="flex:1;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem;font-family:monospace;">
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <label>Primary Dark</label>
                                    <div style="display:flex;gap:10px;align-items:center;">
                                        <input type="color" name="primary_dark" value="<?= htmlspecialchars($settings['primary_dark'] ?? '#4338ca') ?>">
                                        <input type="text" name="primary_dark_text" value="<?= htmlspecialchars($settings['primary_dark'] ?? '#4338ca') ?>" placeholder="#4338ca" style="flex:1;padding:6px 10px;border:1px solid #ddd;border-radius:4px;font-size:0.85rem;font-family:monospace;">
                                    </div>
                                </div>
                                <div class="setting-row">
                                    <label>Background (Primary)</label>
                                    <input type="color" name="bg_primary" value="<?= htmlspecialchars($settings['bg_primary'] ?? '#ffffff') ?>">
                                </div>
                                <div class="setting-row">
                                    <label>Background (Secondary)</label>
                                    <input type="color" name="bg_secondary" value="<?= htmlspecialchars($settings['bg_secondary'] ?? '#f8f9fa') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="setting-section">
                            <h3>📝 Typography</h3>
                            <div class="setting-body">
                                <div class="setting-row">
                                    <label>Font Family</label>
                                    <input type="text" name="font_family" value="<?= htmlspecialchars($settings['font_family'] ?? '') ?>" placeholder="-apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif">
                                </div>
                                <div class="setting-row">
                                    <label>Text Color</label>
                                    <input type="color" name="text_primary" value="<?= htmlspecialchars($settings['text_primary'] ?? '#212529') ?>">
                                </div>
                                <div class="setting-row">
                                    <label>Text Secondary Color</label>
                                    <input type="color" name="text_secondary" value="<?= htmlspecialchars($settings['text_secondary'] ?? '#495057') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="setting-section">
                            <h3>📐 Layout</h3>
                            <div class="setting-body">
                                <div class="setting-row">
                                    <label>Container Width (px)</label>
                                    <input type="number" name="container_width" value="<?= htmlspecialchars($settings['container_width'] ?? '1280') ?>" placeholder="1280" min="640" max="1920" step="10">
                                </div>
                                <div class="setting-row">
                                    <label>Header Height (px)</label>
                                    <input type="number" name="header_height" value="<?= htmlspecialchars($settings['header_height'] ?? '70') ?>" placeholder="70" min="40" max="150" step="5">
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="font-size:1rem;padding:12px 30px;">Save Changes</button>
                        <a href="/admin/themes/customize/reset" class="btn btn-secondary" style="margin-left:10px;" onclick="return confirm('Reset all customizer settings to defaults?')">Reset to Defaults</a>
                    </form>
                </div>
                <div class="customizer-preview">
                    <h3 style="font-size:1rem;margin-bottom:10px;">Live Preview</h3>
                    <div class="preview-frame">
                        <iframe src="/" title="Theme Preview" id="customizer-preview"></iframe>
                    </div>
                    <p style="font-size:0.8rem;color:#888;margin-top:8px;">Save changes and refresh the preview to see updates.</p>
                </div>
            </div>
        </main>
    </div>
    <script>
    // Auto-sync color picker with text input
    document.querySelectorAll('input[type="color"]').forEach(function(picker) {
        var textInput = document.querySelector('input[name="' + picker.name + '_text"]');
        if (textInput) {
            picker.addEventListener('input', function() { textInput.value = this.value; });
            textInput.addEventListener('input', function() { 
                if (/^#[0-9a-f]{6}$/i.test(this.value)) picker.value = this.value; 
            });
        }
    });
    </script>
</body>
</html>