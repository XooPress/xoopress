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
            <p>© <?= date('Y') ?> <?= htmlspecialchars($siteName ?? 'XooPress') ?>. <?= __('All rights reserved.') ?></p>
        </div>
    </footer>

    <script>
    // Mobile menu toggle
    document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
        document.querySelector('.main-navigation').classList.toggle('active');
        this.classList.toggle('active');
    });
    
    // Optional: Save theme preference
    if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        // Dark mode is preferred
        console.log('Dark mode active');
    }
    </script>
    
    <?php if (!empty($footer)) echo $footer; ?>
</body>
</html>
