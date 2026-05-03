<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php $contentType = ($type ?? 'post') === 'page' ? 'Page' : 'Post'; ?>
    <?php $listUrl = ($type ?? 'post') === 'page' ? '/admin/pages' : '/admin/posts'; ?>
    <title><?= $isNew ? 'Add New ' . $contentType : 'Edit ' . $contentType ?> - XooPress Admin</title>
    <link rel="icon" type="image/x-icon" href="/images/xp-favicon.ico">
    <link rel="shortcut icon" href="/images/xp-favicon.ico">
    <link rel="stylesheet" href="/css/xoopress.css">
    <style>
        .editor-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #ddd;
            margin-bottom: 0;
        }
        .editor-tab {
            padding: 10px 20px;
            border: 1px solid #ddd;
            border-bottom: none;
            border-radius: 4px 4px 0 0;
            background: #f5f5f5;
            cursor: pointer;
            font-size: 0.9rem;
            color: #666;
            margin-right: 4px;
            transition: all 0.15s;
            user-select: none;
        }
        .editor-tab:hover {
            background: #eee;
            color: #333;
        }
        .editor-tab.active {
            background: #fff;
            color: #0073aa;
            border-color: #0073aa;
            border-bottom-color: #fff;
            margin-bottom: -2px;
            font-weight: 600;
        }
        .editor-tab .badge {
            display: inline-block;
            font-size: 0.7rem;
            padding: 1px 6px;
            border-radius: 3px;
            margin-left: 4px;
            vertical-align: middle;
        }
        .editor-panel {
            display: none;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
        }
        .editor-panel.active {
            display: block;
        }
        .editor-panel textarea,
        .editor-panel .wysiwyg-editor {
            width: 100%;
            min-height: 400px;
            padding: 14px;
            border: none;
            font-family: 'Consolas', 'Monaco', 'Courier New', monospace;
            font-size: 0.95rem;
            line-height: 1.6;
            resize: vertical;
            outline: none;
        }
        .editor-panel .wysiwyg-editor {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .editor-panel textarea:focus {
            box-shadow: inset 0 0 0 2px #0073aa;
        }
        .editor-toolbar {
            display: flex;
            gap: 4px;
            padding: 8px 10px;
            background: #fafafa;
            border-bottom: 1px solid #eee;
            flex-wrap: wrap;
        }
        .editor-toolbar button {
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            background: #fff;
            cursor: pointer;
            font-size: 0.85rem;
            color: #555;
            transition: all 0.1s;
        }
        .editor-toolbar button:hover {
            background: #e8f0fe;
            border-color: #0073aa;
            color: #0073aa;
        }
        .editor-toolbar .separator {
            width: 1px;
            background: #ddd;
            margin: 0 4px;
        }
        .preview-panel {
            display: none;
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            min-height: 400px;
            background: #fff;
            line-height: 1.7;
        }
        .preview-panel.active {
            display: block;
        }
        .preview-toggle {
            display: flex;
            gap: 0;
            margin-left: auto;
        }
        .preview-toggle button {
            padding: 5px 12px;
            border: 1px solid #ddd;
            background: #f5f5f5;
            cursor: pointer;
            font-size: 0.8rem;
            color: #666;
        }
        .preview-toggle button.active {
            background: #0073aa;
            color: #fff;
            border-color: #0073aa;
        }
        .preview-toggle button:first-child {
            border-radius: 3px 0 0 3px;
        }
        .preview-toggle button:last-child {
            border-radius: 0 3px 3px 0;
        }
        .php-error {
            padding: 15px;
            background: #fdd;
            border: 1px solid #f99;
            border-radius: 4px;
            margin: 10px 0;
            color: #c00;
        }
        .editor-wrapper {
            margin-bottom: 20px;
        }
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
                <li><a href="/admin">Dashboard</a></li>
                <li><a href="/admin/posts" <?= ($type ?? 'post') !== 'page' ? 'class="active"' : '' ?>>Posts</a></li>
                <li><a href="/admin/pages" <?= ($type ?? 'post') === 'page' ? 'class="active"' : '' ?>>Pages</a></li>
                <li><a href="/admin/categories">Categories</a></li>
                <li><a href="/admin/users">Users</a></li>
                <li><a href="/admin/themes">Themes</a></li>
                <li><a href="/admin/settings">Settings</a></li>
                <li><a href="/">View Site</a></li>
                <li><a href="/logout">Logout</a></li>
            </ul>
        </nav>
        <main class="admin-content">
            <header class="admin-header">
                <h1><?= $isNew ? 'Add New ' . $contentType : 'Edit ' . $contentType ?></h1>
                <a href="<?= $listUrl ?>" class="btn btn-secondary" style="font-size:0.85rem;padding:8px 16px;">← Back to <?= $contentType ?>s</a>
            </header>
            <form method="POST" action="/admin/posts/save" style="max-width:900px;">
                <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= $post['id'] ?? '' ?>">
                <?php endif; ?>
                <input type="hidden" name="type" value="<?= htmlspecialchars($type ?? 'post') ?>">
                <input type="hidden" name="content_type" id="content_type_input" value="<?= htmlspecialchars($post['content_type'] ?? 'html') ?>">
                
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($post['title'] ?? '') ?>" required style="width:100%;padding:10px 14px;border:1px solid #ddd;border-radius:4px;font-size:1.1rem;">
                </div>
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($post['slug'] ?? '') ?>" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                    <div class="form-hint">Leave empty to auto-generate from title.</div>
                </div>
                
                <!-- Tabbed Editor -->
                <div class="editor-wrapper">
                    <label>Content</label>
                    <div class="editor-tabs" id="editorTabs">
                        <div class="editor-tab active" data-editor="wysiwyg" onclick="switchEditor('wysiwyg')">🎨 Visual</div>
                        <div class="editor-tab" data-editor="html" onclick="switchEditor('html')">🔤 HTML</div>
                        <div class="editor-tab" data-editor="markdown" onclick="switchEditor('markdown')">📝 Markdown</div>
                        <div class="editor-tab" data-editor="php" onclick="switchEditor('php')">⚡ PHP</div>
                        <div class="preview-toggle">
                            <button type="button" class="active" id="editBtn" onclick="togglePreview(false)">Edit</button>
                            <button type="button" id="previewBtn" onclick="togglePreview(true)">Preview</button>
                        </div>
                    </div>
                    
                    <!-- WYSIWYG Panel -->
                    <div class="editor-panel active" id="panel-wysiwyg">
                        <div class="editor-toolbar">
                            <button onclick="wrapSelection('wysiwyg', '<strong>', '</strong>')" title="Bold"><strong>B</strong></button>
                            <button onclick="wrapSelection('wysiwyg', '<em>', '</em>')" title="Italic"><em>I</em></button>
                            <button onclick="wrapSelection('wysiwyg', '<u>', '</u>')" title="Underline"><u>U</u></button>
                            <span class="separator"></span>
                            <button onclick="wrapSelection('wysiwyg', '<h2>', '</h2>')" title="Heading">H2</button>
                            <button onclick="wrapSelection('wysiwyg', '<h3>', '</h3>')" title="Subheading">H3</button>
                            <span class="separator"></span>
                            <button onclick="wrapSelection('wysiwyg', '<p>', '</p>')" title="Paragraph">¶</button>
                            <button onclick="wrapSelection('wysiwyg', '<blockquote>', '</blockquote>')" title="Blockquote">❝</button>
                            <button onclick="wrapSelection('wysiwyg', '<pre><code>', '</code></pre>')" title="Code">⟨/⟩</button>
                            <span class="separator"></span>
                            <button onclick="wrapSelection('wysiwyg', '<ul>\n<li>', '</li>\n</ul>')" title="List">•</button>
                            <button onclick="wrapSelection('wysiwyg', '<a href="">', '</a>')" title="Link">🔗</button>
                            <button onclick="insertTag('wysiwyg', '<img src=\"\" alt=\"\">')" title="Image">🖼</button>
                        </div>
                        <div class="wysiwyg-editor" id="wysiwyg-editor" contenteditable="true"><?= htmlspecialchars($post['content'] ?? '') ?></div>
                    </div>
                    
                    <!-- HTML Panel -->
                    <div class="editor-panel" id="panel-html">
                        <div class="editor-toolbar">
                            <button onclick="wrapSelection('html', '<strong>', '</strong>')"><strong>B</strong></button>
                            <button onclick="wrapSelection('html', '<em>', '</em>')"><em>I</em></button>
                            <button onclick="wrapSelection('html', '<h2>', '</h2>')">H2</button>
                            <button onclick="wrapSelection('html', '<h3>', '</h3>')">H3</button>
                            <button onclick="wrapSelection('html', '<p>', '</p>')">¶</button>
                            <button onclick="wrapSelection('html', '<blockquote>', '</blockquote>')">❝</button>
                            <button onclick="wrapSelection('html', '<pre><code>', '</code></pre>')">⟨/⟩</button>
                            <button onclick="wrapSelection('html', '<a href=\"\">', '</a>')">🔗</button>
                        </div>
                        <textarea id="html-editor" name="content"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Markdown Panel -->
                    <div class="editor-panel" id="panel-markdown">
                        <div class="editor-toolbar">
                            <button onclick="wrapSelection('markdown', '**', '**')"><strong>B</strong></button>
                            <button onclick="wrapSelection('markdown', '*', '*')"><em>I</em></button>
                            <button onclick="wrapSelection('markdown', '## ', '')">H2</button>
                            <button onclick="wrapSelection('markdown', '### ', '')">H3</button>
                            <button onclick="wrapSelection('markdown', '> ', '')">❝</button>
                            <button onclick="wrapSelection('markdown', '`', '`')">⟨code⟩</button>
                            <button onclick="wrapSelection('markdown', '[', '](url)')">🔗</button>
                            <button onclick="wrapSelection('markdown', '![', '](url)')">🖼</button>
                            <button onclick="wrapSelection('markdown', '- ', '')">•</button>
                            <button onclick="wrapSelection('markdown', '1. ', '')">1.</button>
                            <button onclick="wrapSelection('markdown', '```\n', '\n```')">⟨/⟩</button>
                        </div>
                        <textarea id="markdown-editor"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- PHP Panel -->
                    <div class="editor-panel" id="panel-php">
                        <div class="editor-toolbar">
                            <button onclick="wrapSelection('php', 'echo ', ';')">echo</button>
                            <button onclick="wrapSelection('php', 'if (', ') {\n    \n}')">if</button>
                            <button onclick="wrapSelection('php', 'foreach (', ' as $item) {\n    \n}')">foreach</button>
                            <button onclick="wrapSelection('php', 'for (', '; ; ) {\n    \n}')">for</button>
                            <button onclick="wrapSelection('php', 'function ', '() {\n    \n}')">fn</button>
                            <button onclick="wrapSelection('php', '$', '')">$var</button>
                            <button onclick="wrapSelection('php', 'return ', ';')">return</button>
                            <button onclick="wrapSelection('php', '// ', '')">//</button>
                        </div>
                        <textarea id="php-editor"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Preview Panel -->
                    <div class="preview-panel" id="previewPanel">
                        <div id="previewContent"><em>Click "Preview" to see the rendered content.</em></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="excerpt">Excerpt</label>
                    <textarea id="excerpt" name="excerpt" rows="3" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;"><?= htmlspecialchars($post['excerpt'] ?? '') ?></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:15px;">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="draft" <?= ($post['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= ($post['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
                            <option value="pending" <?= ($post['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category_id">Category</label>
                        <select id="category_id" name="category_id" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="">Uncategorized</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="language">Language</label>
                        <select id="language" name="language" style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;">
                            <option value="en_US" <?= ($post['language'] ?? 'en_US') === 'en_US' ? 'selected' : '' ?>>English</option>
                            <option value="de_DE" <?= ($post['language'] ?? '') === 'de_DE' ? 'selected' : '' ?>>Deutsch</option>
                            <option value="fr_FR" <?= ($post['language'] ?? '') === 'fr_FR' ? 'selected' : '' ?>>Français</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary" style="padding:12px 30px;"><?= $isNew ? 'Publish' : 'Update' ?></button>
                    <a href="<?= $listUrl ?>" class="btn btn-secondary" style="padding:12px 30px;">Cancel</a>
                </div>
            </form>
        </main>
    </div>

    <script>
        // ── Editor Switching ──────────────────────────────────
        let currentEditor = 'wysiwyg';
        
        function switchEditor(type) {
            currentEditor = type;
            
            // Update tabs
            document.querySelectorAll('.editor-tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.editor-tab[data-editor="${type}"]`).classList.add('active');
            
            // Update panels
            document.querySelectorAll('.editor-panel').forEach(p => p.classList.remove('active'));
            document.getElementById(`panel-${type}`).classList.add('active');
            
            // Update hidden input
            document.getElementById('content_type_input').value = type;
            
            // Hide preview if showing
            if (document.getElementById('previewPanel').classList.contains('active')) {
                togglePreview(false);
            }
            
            // Sync content from hidden textarea to active editor
            syncFromHidden();
        }
        
        // ── Content Sync ──────────────────────────────────────
        function getActiveContent() {
            const panel = document.getElementById(`panel-${currentEditor}`);
            if (!panel) return '';
            
            if (currentEditor === 'wysiwyg') {
                return document.getElementById('wysiwyg-editor').innerHTML;
            }
            const textarea = panel.querySelector('textarea');
            return textarea ? textarea.value : '';
        }
        
        function setActiveContent(value) {
            const panel = document.getElementById(`panel-${currentEditor}`);
            if (!panel) return;
            
            if (currentEditor === 'wysiwyg') {
                document.getElementById('wysiwyg-editor').innerHTML = value;
            } else {
                const textarea = panel.querySelector('textarea');
                if (textarea) textarea.value = value;
            }
        }
        
        // Sync from the hidden textarea[name=content] to the active editor
        function syncFromHidden() {
            const hidden = document.querySelector('textarea[name="content"]');
            if (hidden) {
                setActiveContent(hidden.value);
            }
        }
        
        // Sync from active editor to hidden textarea (before form submit)
        function syncToHidden() {
            const hidden = document.querySelector('textarea[name="content"]');
            if (hidden) {
                hidden.value = getActiveContent();
            }
        }
        
        // Sync on form submit
        document.querySelector('form').addEventListener('submit', function(e) {
            syncToHidden();
        });
        
        // ── Toolbar Helpers ───────────────────────────────────
        function wrapSelection(editor, before, after) {
            const panel = document.getElementById(`panel-${editor}`);
            if (!panel) return;
            
            if (editor === 'wysiwyg') {
                const editorEl = document.getElementById('wysiwyg-editor');
                const sel = window.getSelection();
                if (sel.rangeCount > 0 && editorEl.contains(sel.anchorNode)) {
                    const range = sel.getRangeAt(0);
                    const selected = range.toString();
                    const newNode = document.createElement('span');
                    newNode.innerHTML = before + selected + after;
                    range.deleteContents();
                    range.insertNode(newNode);
                } else {
                    // Insert at cursor or end
                    editorEl.innerHTML += before + after;
                }
                return;
            }
            
            const textarea = panel.querySelector('textarea');
            if (!textarea) return;
            
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selected = textarea.value.substring(start, end);
            const replacement = before + selected + after;
            
            textarea.value = textarea.value.substring(0, start) + replacement + textarea.value.substring(end);
            textarea.focus();
            textarea.selectionStart = start + before.length;
            textarea.selectionEnd = start + before.length + selected.length;
        }
        
        function insertTag(editor, tag) {
            const panel = document.getElementById(`panel-${editor}`);
            if (!panel) return;
            
            if (editor === 'wysiwyg') {
                const editorEl = document.getElementById('wysiwyg-editor');
                editorEl.innerHTML += tag;
                return;
            }
            
            const textarea = panel.querySelector('textarea');
            if (!textarea) return;
            
            const start = textarea.selectionStart;
            textarea.value = textarea.value.substring(0, start) + tag + textarea.value.substring(textarea.selectionEnd);
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = start + tag.length;
        }
        
        // ── Preview ───────────────────────────────────────────
        let previewActive = false;
        
        function togglePreview(show) {
            previewActive = show;
            
            document.getElementById('editBtn').classList.toggle('active', !show);
            document.getElementById('previewBtn').classList.toggle('active', show);
            
            document.querySelectorAll('.editor-panel').forEach(p => p.classList.toggle('active', !show));
            document.getElementById('previewPanel').classList.toggle('active', show);
            
            if (show) {
                updatePreview();
            }
        }
        
        function updatePreview() {
            const content = getActiveContent();
            const type = currentEditor;
            const preview = document.getElementById('previewContent');
            
            if (type === 'html' || type === 'wysiwyg') {
                preview.innerHTML = content || '<em>No content</em>';
            } else if (type === 'markdown') {
                // Simple client-side preview for Markdown
                let html = content
                    .replace(/^### (.+)$/gm, '<h3>$1</h3>')
                    .replace(/^## (.+)$/gm, '<h2>$1</h2>')
                    .replace(/^# (.+)$/gm, '<h1>$1</h1>')
                    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                    .replace(/\*(.+?)\*/g, '<em>$1</em>')
                    .replace(/```(\w*)\n([\s\S]+?)```/g, '<pre><code>$2</code></pre>')
                    .replace(/`(.+?)`/g, '<code>$1</code>')
                    .replace(/\[(.+?)\]\((.+?)\)/g, '<a href="$2">$1</a>')
                    .replace(/!\[(.+?)\]\((.+?)\)/g, '<img src="$2" alt="$1">')
                    .replace(/^> (.+)$/gm, '<blockquote>$1</blockquote>')
                    .replace(/^[-*] (.+)$/gm, '<li>$1</li>')
                    .replace(/^\d+\. (.+)$/gm, '<li>$1</li>')
                    .replace(/\n\n/g, '</p><p>')
                    .replace(/\n/g, '<br>');
                html = '<p>' + html + '</p>';
                preview.innerHTML = html || '<em>No content</em>';
            } else if (type === 'php') {
                preview.innerHTML = '<div style="color:#888;font-style:italic;">PHP preview is rendered server-side after saving.</div>' +
                    '<pre style="background:#f5f5f5;padding:15px;border-radius:4px;margin-top:10px;overflow-x:auto;">' +
                    escapeHtml(content) + '</pre>';
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // ── Auto-slug from title ──────────────────────────────
        document.getElementById('title').addEventListener('blur', function() {
            const slug = document.getElementById('slug');
            if (!slug.value) {
                slug.value = this.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/^-+|-+$/g, '') || 'untitled';
            }
        });
        
        // ── Init: restore active editor from saved content_type ──
        const savedType = document.getElementById('content_type_input').value;
        if (savedType && savedType !== 'html') {
            switchEditor(savedType);
        }
    </script>
</body>
</html>