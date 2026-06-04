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