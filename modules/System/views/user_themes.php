<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __('Choose Theme') ?> - <?= htmlspecialchars($siteName) ?></title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .theme-chooser {
            max-width: 900px;
            margin: 0 auto;
        }
        .theme-chooser h1 {
            font-size: 1.5rem;
            color: #23282d;
            margin-bottom: 5px;
        }
        .theme-chooser .subtitle {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 25px;
        }
        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 20px;
        }
        .theme-card {
            background: #fff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
            transition: border-color 0.2s, box-shadow 0.2s;
            cursor: pointer;
        }
        .theme-card:hover {
            border-color: #0073aa;
            box-shadow: 0 2px 12px rgba(0,115,170,0.15);
        }
        .theme-card.selected {
            border-color: #0073aa;
            box-shadow: 0 0 0 3px rgba(0,115,170,0.2);
        }
        .theme-card.selected .theme-card-header {
            background: #0073aa;
            color: #fff;
        }
        .theme-card-header {
            background: #f5f5f5;
            padding: 12px 15px;
            font-weight: bold;
            font-size: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .theme-card-body {
            padding: 15px;
        }
        .theme-screenshot {
            width: 100%;
            height: 150px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #aaa;
            font-size: 0.85rem;
            margin-bottom: 12px;
            overflow: hidden;
            border-radius: 4px;
        }
        .theme-screenshot img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .theme-meta {
            font-size: 0.85rem;
            color: #888;
            margin-bottom: 8px;
        }
        .theme-card p {
            font-size: 0.9rem;
            color: #555;
            line-height: 1.5;
        }
        .check-mark {
            display: none;
            font-size: 1.2rem;
        }
        .theme-card.selected .check-mark {
            display: inline;
        }
        .btn-save {
            display: block;
            width: 100%;
            max-width: 300px;
            margin: 30px auto 0;
            padding: 14px 30px;
            font-size: 1.1rem;
            text-align: center;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #0073aa;
            font-size: 0.9rem;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .alert {
            padding: 12px 18px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .alert-success {
            background: #ecf7ed;
            color: #46b450;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #fbeaea;
            color: #dc3232;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <img src="/images/xp-logo.svg" alt="XooPress Logo" style="height:48px;margin-bottom:10px;">
            <h1><?= htmlspecialchars($siteName) ?></h1>
            <p class="version"><?= __('Choose Your Theme') ?></p>
        </header>
        <main class="main">
            <div class="theme-chooser">
                <h1><?= __('Personal Theme') ?></h1>
                <p class="subtitle"><?= __('Select a theme to use when browsing the site. This only affects your account.') ?></p>

                <?php if (isset($message)): ?>
                    <div class="alert alert-<?= $messageType ?? 'info' ?>"><?= htmlspecialchars($message) ?></div>
                <?php endif; ?>

                <form method="POST" action="/user/themes" id="themeForm">
                    <input type="hidden" name="_csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="theme" id="selectedTheme" value="<?= htmlspecialchars($userTheme) ?>">

                    <div class="theme-grid">
                        <?php if (empty($themes)): ?>
                            <p style="grid-column:1/-1;text-align:center;padding:40px;color:#888;">
                                <?= __('No themes available.') ?>
                            </p>
                        <?php else: ?>
                            <?php foreach ($themes as $name => $theme): ?>
                            <div class="theme-card <?= $name === $userTheme ? 'selected' : '' ?>" data-theme="<?= htmlspecialchars($name) ?>" onclick="selectTheme('<?= htmlspecialchars($name) ?>')">
                                <div class="theme-card-header">
                                    <?= htmlspecialchars($theme['name']) ?>
                                    <span class="check-mark">✓</span>
                                </div>
                                <div class="theme-card-body">
                                    <div class="theme-screenshot">
                                        <?php if ($theme['screenshot']): ?>
                                            <img src="/<?= htmlspecialchars($theme['screenshot']) ?>" alt="<?= htmlspecialchars($theme['name']) ?>">
                                        <?php else: ?>
                                            <?= __('No screenshot') ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="theme-meta">
                                        <?= __('Version') ?> <?= htmlspecialchars($theme['version']) ?>
                                        <?php if (!empty($theme['author'])): ?>
                                            | <?= htmlspecialchars($theme['author']) ?>
                                        <?php endif; ?>
                                    </div>
                                    <p><?= htmlspecialchars($theme['description']) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-save"><?= __('Save Theme Preference') ?></button>
                </form>

                <div style="text-align:center;">
                    <a href="/user/dashboard" class="back-link">← <?= __('Back to My Dashboard') ?></a>
                </div>
            </div>
        </main>
        <footer class="footer">
            <p>&copy; <?= date('Y') ?> XooPress. <?= __('All rights reserved.') ?></p>
        </footer>
    </div>

    <script>
        function selectTheme(name) {
            document.querySelectorAll('.theme-card').forEach(c => c.classList.remove('selected'));
            document.querySelector(`.theme-card[data-theme="${name}"]`).classList.add('selected');
            document.getElementById('selectedTheme').value = name;
        }
    </script>
</body>
</html>