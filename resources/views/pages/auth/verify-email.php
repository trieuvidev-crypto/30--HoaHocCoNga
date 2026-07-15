<div class="auth-shell">
    <div class="auth-card card card--floating" style="text-align: center;">
        <div class="auth-card__logo" style="justify-content: center;">
            <?= \App\Core\View::svg('logo') ?>
            <span>HoaHocCoNga.Com</span>
        </div>

        <?php if ($success): ?>
            <div class="alert alert--success is-visible"><?= \App\Core\View::e($message) ?></div>
            <a href="/login" class="btn btn--primary btn--block">Đăng nhập ngay</a>
        <?php else: ?>
            <div class="alert alert--danger is-visible"><?= \App\Core\View::e($message) ?></div>
            <a href="/register" class="btn btn--outline btn--block">Quay lại đăng ký</a>
        <?php endif; ?>
    </div>
</div>
