        </div><!-- .container -->
    </main><!-- .site-content -->
    
    <footer class="site-footer">
        <div class="container">
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
