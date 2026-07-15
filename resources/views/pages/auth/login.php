<div class="auth-shell">
    <div class="auth-card card card--floating">
        <div class="auth-card__logo">
            <?= \App\Core\View::svg('logo') ?>
            <span>HoaHocCoNga.Com</span>
        </div>

        <h1 style="font-size: var(--text-title); margin-bottom: var(--space-2);">Đăng nhập</h1>
        <p style="margin-bottom: var(--space-6);">Tiếp tục hành trình chinh phục Hóa học của bạn.</p>

        <div class="alert alert--danger" id="login-error" role="alert"></div>

        <form id="login-form" novalidate>
            <div class="field">
                <label class="field__label" for="identifier">Email hoặc tên đăng nhập</label>
                <input class="field__input" type="text" id="identifier" name="identifier" autocomplete="username" required>
            </div>

            <div class="field">
                <label class="field__label" for="password">Mật khẩu</label>
                <div style="position: relative;">
                    <input class="field__input" type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" id="toggle-password" aria-label="Hiện mật khẩu"
                        style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color: var(--color-text-muted); padding: 0;">
                        <span id="toggle-password-icon"><?= \App\Core\View::svg('eye') ?></span>
                    </button>
                </div>
            </div>

            <div style="text-align: right; margin-bottom: var(--space-5);">
                <a href="/forgot-password" style="font-size: var(--text-small);">Quên mật khẩu?</a>
            </div>

            <button type="submit" class="btn btn--primary btn--block" id="login-submit">
                <span class="btn__spinner"></span>
                <span class="btn__label">Đăng nhập</span>
            </button>
        </form>

        <div class="auth-card__footer">
            Chưa có tài khoản? <a href="/register">Đăng ký ngay</a>
        </div>
    </div>
</div>

<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/auth.js')) ?>"></script>
