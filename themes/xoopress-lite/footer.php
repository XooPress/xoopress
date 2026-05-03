        </div><!-- .container -->
    </main><!-- .site-content -->
    
    <footer class="site-footer">
        <div class="container">
            <p>© <?= date('Y') ?> <?= htmlspecialchars($siteName ?? 'XooPress') ?>. <?= __('All rights reserved.') ?></p>
        </div>
    </footer>
    
    <script>
    // Mobile menu toggle
    document.querySelector('.mobile-menu-toggle')?.addEventListener('click', function() {
        document.querySelector('.main-navigation').classList.toggle('active');
        this.classList.toggle('active');
    });
    </script>
    
    <?php if (!empty($footer)) echo $footer; ?>
</body>
</html>
