<div class="auth-shell">
    <div class="auth-card card card--floating">
        <div class="auth-card__logo">
            <?= \App\Core\View::svg('logo') ?>
            <span>HoaHocCoNga.Com</span>
        </div>

        <h1 style="font-size: var(--text-title); margin-bottom: var(--space-2);">Tạo tài khoản</h1>
        <p style="margin-bottom: var(--space-6);">Bắt đầu học Hóa học cùng hàng nghìn học sinh khác.</p>

        <div class="alert alert--danger" id="register-error" role="alert"></div>
        <div class="alert alert--success" id="register-success" role="status"></div>

        <form id="register-form" novalidate>
            <div class="field">
                <label class="field__label" for="display_name">Họ và tên</label>
                <input class="field__input" type="text" id="display_name" name="display_name" autocomplete="name" required>
                <div class="field__error" data-error-for="display_name"></div>
            </div>

            <div class="field">
                <label class="field__label" for="username">Tên đăng nhập</label>
                <input class="field__input" type="text" id="username" name="username" autocomplete="username" required>
                <div class="field__error" data-error-for="username"></div>
            </div>

            <div class="field">
                <label class="field__label" for="email">Email</label>
                <input class="field__input" type="email" id="email" name="email" autocomplete="email" required>
                <div class="field__error" data-error-for="email"></div>
            </div>

            <div class="field">
                <label class="field__label" for="password">Mật khẩu</label>
                <input class="field__input" type="password" id="password" name="password" autocomplete="new-password" required>
                <div class="field__error" data-error-for="password"></div>
            </div>

            <div class="field">
                <label class="field__label" for="password_confirmation">Xác nhận mật khẩu</label>
                <input class="field__input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" required>
            </div>

            <button type="submit" class="btn btn--primary btn--block" id="register-submit">
                <span class="btn__spinner"></span>
                <span class="btn__label">Đăng ký</span>
            </button>
        </form>

        <div class="auth-card__footer">
            Đã có tài khoản? <a href="/login">Đăng nhập</a>
        </div>
    </div>
</div>

<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/auth.js')) ?>"></script>
