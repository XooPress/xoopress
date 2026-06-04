/**
 * XooPress - Password Visibility Toggle
 * Toggles password fields between hidden (password) and visible (text).
 */
function togglePasswordVisibility(btn) {
    var wrapper = btn.parentNode;
    var input = wrapper.querySelector('input[type="password"], input[type="text"]');
    if (!input) return;

    var isPassword = input.getAttribute('type') === 'password';
    input.setAttribute('type', isPassword ? 'text' : 'password');
    btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
    btn.classList.toggle('password-visible', isPassword);
}

/**
 * XooPress - Admin Sidebar Toggle (Hamburger Menu)
 * Handles showing/hiding the admin sidebar on mobile devices.
 */
function toggleAdminSidebar() {
    var sidebar = document.querySelector('.admin-sidebar');
    var overlay = document.querySelector('.admin-sidebar-overlay');
    if (!sidebar) return;
    sidebar.classList.toggle('open');
    if (overlay) {
        overlay.classList.toggle('open');
    }
}

function closeAdminSidebar() {
    var sidebar = document.querySelector('.admin-sidebar');
    var overlay = document.querySelector('.admin-sidebar-overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
}

// Close sidebar when clicking a nav link on mobile
document.addEventListener('DOMContentLoaded', function() {
    var sidebarLinks = document.querySelectorAll('.admin-nav a');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', function() {
            closeAdminSidebar();
        });
    });

    // Close sidebar when clicking overlay
    var overlay = document.querySelector('.admin-sidebar-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            closeAdminSidebar();
        });
    }

    // Close sidebar on window resize above breakpoint
    var resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 768) {
                closeAdminSidebar();
            }
        }, 100);
    });
});