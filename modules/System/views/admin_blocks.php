<?php $pageTitle = 'Blocks - XooPress Admin'; include __DIR__ . '/_admin_header.php'; ?>
            <style>
                .shortcode-copy { cursor: pointer; background:#f0f6fc; border:1px solid #c8def5; border-radius:3px; padding:3px 8px; font-family:monospace; font-size:0.85rem; color:#0073aa; }
                .shortcode-copy:hover { background:#e0f0ff; }
            </style>
            <header class="admin-header">
                <h1>Content Blocks</h1>
                <a href="/admin/blocks/new" class="btn btn-primary" style="font-size:0.85rem;padding:8px 16px;">+ New Block</a>
            </header>

            <?php if (!empty($notice)): ?>
            <div style="padding:10px 15px;background:#d4edda;border:1px solid #c3e6cb;border-radius:4px;margin-bottom:15px;color:#155724;"><?= htmlspecialchars($notice) ?></div>
            <?php endif; ?>

            <div style="background:#fff;border:1px solid #ddd;border-radius:4px;">
                <table style="width:100%;border-collapse:collapse;">
                    <thead>
                        <tr style="background:#f5f5f5;">
                            <th style="padding:12px 16px;text-align:left;border-bottom:1px solid #ddd;">Title</th>
                            <th style="padding:12px 16px;text-align:left;border-bottom:1px solid #ddd;">Slug</th>
                            <th style="padding:12px 16px;text-align:left;border-bottom:1px solid #ddd;">Type</th>
                            <th style="padding:12px 16px;text-align:center;border-bottom:1px solid #ddd;">Active</th>
                            <th style="padding:12px 16px;text-align:left;border-bottom:1px solid #ddd;">Shortcode</th>
                            <th style="padding:12px 16px;text-align:right;border-bottom:1px solid #ddd;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($blocks)): ?>
                        <tr><td colspan="6" style="padding:20px;text-align:center;color:#888;">No content blocks yet. <a href="/admin/blocks/new">Create one</a>.</td></tr>
                        <?php else: ?>
                        <?php foreach ($blocks as $block): ?>
                        <tr style="border-top:1px solid #eee;">
                            <td style="padding:10px 16px;font-weight:500;"><?= htmlspecialchars($block['title'] ?? '') ?></td>
                            <td style="padding:10px 16px;color:#888;font-size:0.9rem;"><?= htmlspecialchars($block['slug'] ?? '') ?></td>
                            <td style="padding:10px 16px;"><span style="background:#e8f0fe;color:#0073aa;padding:2px 8px;border-radius:3px;font-size:0.8rem;"><?= htmlspecialchars($block['content_type'] ?? 'html') ?></span></td>
                            <td style="padding:10px 16px;text-align:center;"><?= !empty($block['is_active']) ? '✅' : '❌' ?></td>
                            <td style="padding:10px 16px;">
                                <span class="shortcode-copy" onclick="copyShortcode(this)" data-shortcode="[block slug=&#34;<?= htmlspecialchars($block['slug'] ?? '') ?>&#34;]">[block slug="<?= htmlspecialchars($block['slug'] ?? '') ?>"]</span>
                            </td>
                            <td style="padding:10px 16px;text-align:right;">
                                <a href="/admin/blocks/edit/<?= (int)$block['id'] ?>" class="btn btn-sm" style="padding:4px 12px;">Edit</a>
                                <a href="/admin/blocks/delete/<?= (int)$block['id'] ?>" class="btn btn-sm btn-danger" style="padding:4px 12px;" onclick="return confirm('Delete block &#34;<?= htmlspecialchars(addslashes($block['title'] ?? ''), ENT_QUOTES) ?>&#34;?')">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <script>
            function copyShortcode(el) {
                const text = el.getAttribute('data-shortcode');
                navigator.clipboard.writeText(text).then(() => {
                    const orig = el.textContent;
                    el.textContent = 'Copied!';
                    setTimeout(() => el.textContent = orig, 1500);
                }).catch(() => {
                    const ta = document.createElement('textarea');
                    ta.value = text;
                    document.body.appendChild(ta);
                    ta.select();
                    document.execCommand('copy');
                    document.body.removeChild(ta);
                    const orig = el.textContent;
                    el.textContent = 'Copied!';
                    setTimeout(() => el.textContent = orig, 1500);
                });
            }
            </script>
<?php include __DIR__ . '/_admin_footer.php'; ?>
