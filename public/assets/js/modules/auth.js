import { apiRequest } from './api.js';

function setLoading(button, loading) {
    button.disabled = loading;
    button.classList.toggle('is-loading', loading);
}

function showAlert(el, message) {
    if (!el) return;
    el.textContent = message;
    el.classList.add('is-visible');
}

function hideAlert(el) {
    if (!el) return;
    el.classList.remove('is-visible');
    el.textContent = '';
}

function clearFieldErrors(form) {
    form.querySelectorAll('[data-error-for]').forEach((el) => {
        el.textContent = '';
    });
    form.querySelectorAll('.field__input--error').forEach((el) => {
        el.classList.remove('field__input--error');
    });
}

function applyFieldErrors(form, errors) {
    Object.entries(errors || {}).forEach(([field, messages]) => {
        const errorEl = form.querySelector(`[data-error-for="${field}"]`);
        const inputEl = form.querySelector(`[name="${field}"]`);

        if (errorEl) {
            errorEl.textContent = Array.isArray(messages) ? messages[0] : String(messages);
        }

        if (inputEl) {
            inputEl.classList.add('field__input--error');
        }
    });
}

function initLoginForm() {
    const form = document.getElementById('login-form');

    if (!form) return;

    const errorBox = document.getElementById('login-error');
    const submitButton = document.getElementById('login-submit');

    const toggleBtn = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggle-password-icon');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', () => {
            const isHidden = passwordInput.type === 'password';
            passwordInput.type = isHidden ? 'text' : 'password';
            toggleBtn.setAttribute('aria-label', isHidden ? 'Ẩn mật khẩu' : 'Hiện mật khẩu');
        });
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert(errorBox);
        setLoading(submitButton, true);

        const { ok, body } = await apiRequest('/api/v1/auth/login', {
            method: 'POST',
            body: {
                identifier: form.identifier.value.trim(),
                password: form.password.value,
                via: 'session',
            },
        });

        setLoading(submitButton, false);

        if (!ok) {
            showAlert(errorBox, body.message || 'Đăng nhập thất bại. Vui lòng thử lại.');

            return;
        }

        const roles = (body.data && body.data.user && body.data.user.roles) || [];
        const isTeacher = roles.includes('teacher');

        window.location.href = isTeacher ? '/teacher/dashboard' : '/dashboard';
    });
}

function initRegisterForm() {
    const form = document.getElementById('register-form');

    if (!form) return;

    const errorBox = document.getElementById('register-error');
    const successBox = document.getElementById('register-success');
    const submitButton = document.getElementById('register-submit');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert(errorBox);
        hideAlert(successBox);
        clearFieldErrors(form);

        if (form.password.value !== form.password_confirmation.value) {
            applyFieldErrors(form, { password: ['Mật khẩu xác nhận không khớp.'] });

            return;
        }

        setLoading(submitButton, true);

        const { ok, body } = await apiRequest('/api/v1/auth/register', {
            method: 'POST',
            body: {
                display_name: form.display_name.value.trim(),
                username: form.username.value.trim(),
                email: form.email.value.trim(),
                password: form.password.value,
                password_confirmation: form.password_confirmation.value,
            },
        });

        setLoading(submitButton, false);

        if (!ok) {
            if (body.errors && Object.keys(body.errors).length > 0) {
                applyFieldErrors(form, body.errors);
            } else {
                showAlert(errorBox, body.message || 'Đăng ký thất bại. Vui lòng thử lại.');
            }

            return;
        }

        showAlert(successBox, body.message || 'Đăng ký thành công!');
        form.reset();

        setTimeout(() => {
            window.location.href = '/login';
        }, 1500);
    });
}

initLoginForm();
initRegisterForm();
