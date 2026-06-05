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

/**
 * XooPress - Password Strength Meter
 * Evaluates password strength in real-time and updates a visual bar.
 * Color thresholds: 0-25 Red (Weak), 26-50 Orange (Fair), 51-75 Yellow (Medium), 76-100 Green (Strong)
 */
function initPasswordStrengthMeter() {
    var passwordInput = document.getElementById('password');
    if (!passwordInput) return;

    var strengthBar = document.getElementById('password-strength-bar');
    var strengthText = document.getElementById('password-strength-text');
    var reqLength = document.getElementById('req-length');
    var reqUpper = document.getElementById('req-upper');
    var reqSpecial = document.getElementById('req-special');

    function evaluateStrength() {
        var val = passwordInput.value;
        var score = 0;

        // Length check (up to 25 points)
        var lengthOk = val.length >= 8;
        if (lengthOk) score += 25;

        var hasUpper = /[A-Z]/.test(val);
        if (hasUpper) score += 25;

        var hasLower = /[a-z]/.test(val);
        if (hasLower) score += 15;

        var hasNumber = /[0-9]/.test(val);
        if (hasNumber) score += 15;

        var hasSpecial = /[^a-zA-Z0-9]/.test(val);
        if (hasSpecial) score += 20;

        // Update strength bar
        if (strengthBar) {
            strengthBar.style.width = Math.min(score, 100) + '%';

            // Remove all color classes
            strengthBar.classList.remove('strength-red', 'strength-orange', 'strength-yellow', 'strength-green');

            if (score <= 25) {
                strengthBar.classList.add('strength-red');
            } else if (score <= 50) {
                strengthBar.classList.add('strength-orange');
            } else if (score <= 75) {
                strengthBar.classList.add('strength-yellow');
            } else {
                strengthBar.classList.add('strength-green');
            }
        }

        // Update strength label
        if (strengthText) {
            if (val.length === 0) {
                strengthText.textContent = '';
            } else if (score <= 25) {
                strengthText.textContent = 'Weak';
            } else if (score <= 50) {
                strengthText.textContent = 'Fair';
            } else if (score <= 75) {
                strengthText.textContent = 'Medium';
            } else {
                strengthText.textContent = 'Strong';
            }
        }

        // Update requirement checks
        if (reqLength) {
            reqLength.textContent = lengthOk ? '\u2713' : '\u2717';
            reqLength.className = lengthOk ? 'req-met' : 'req-unmet';
        }
        if (reqUpper) {
            reqUpper.textContent = hasUpper ? '\u2713' : '\u2717';
            reqUpper.className = hasUpper ? 'req-met' : 'req-unmet';
        }
        if (reqSpecial) {
            reqSpecial.textContent = hasSpecial ? '\u2713' : '\u2717';
            reqSpecial.className = hasSpecial ? 'req-met' : 'req-unmet';
        }
    }

    passwordInput.addEventListener('input', evaluateStrength);
    // Run once on page load in case of prefilled value
    evaluateStrength();
}

// Close sidebar when clicking overlay
document.addEventListener('DOMContentLoaded', function() {
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

    // Initialize password strength meter on registration page
    initPasswordStrengthMeter();
});
