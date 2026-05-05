        </div><!-- .container -->
    </main><!-- .site-content -->
    
    <footer class="site-footer">
        <div class="container">
            <?php $footerPages = getFooterPages(); ?>
            <?php if (!empty($footerPages)): ?>
            <nav class="footer-menu">
                <?php $i = 0; foreach ($footerPages as $page): ?>
                <?php if ($i > 0): ?><span class="footer-menu-sep">|</span><?php endif; ?>
                <a href="/posts/<?= (int)$page['id'] ?>" class="footer-menu-link"><?= htmlspecialchars($page['title']) ?></a>
                <?php $i++; endforeach; ?>
            </nav>
            <?php endif; ?>
            <p>
                ✨ &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName ?? 'XooPress') ?>.
                <?= __('All rights reserved.') ?>
            </p>
            <p style="font-size: 0.875rem; margin-top: 0.5rem;">
                <?= __('Powered by') ?> <a href="https://xoopress.org" target="_blank" rel="noopener noreferrer">XooPress CMS</a>
            </p>
        </div>
    </footer>
    
    <!-- Additional Footer Scripts -->
    <?php if (!empty($footer)): ?>
        <?= $footer ?>
    <?php endif; ?>
</body>
</html>
