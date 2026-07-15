<!doctype html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? \App\Core\View::e($pageTitle) . ' — ' : '' ?>HoaHocCoNga.Com</title>
    <meta name="description" content="<?= \App\Core\View::e($pageDescription ?? 'Nền tảng học Hóa học trực tuyến hàng đầu dành cho học sinh THCS, THPT và luyện thi.') ?>">
    <meta name="csrf-token" content="<?= \App\Core\View::e($_SESSION['_csrf_token'] ?? '') ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/public.css')) ?>">
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a href="/" class="site-header__logo">
                <?= \App\Core\View::svg('logo') ?>
                <span>HoaHocCoNga.Com</span>
            </a>

            <nav class="site-header__nav">
                <a href="/courses">Khóa học</a>
                <a href="/chemistry-tools">Công cụ Hóa học</a>
            </nav>

            <div class="site-header__actions">
                <?php if (!empty($_SESSION['auth_user_id'])): ?>
                    <a href="/dashboard" class="btn btn--outline">Vào học</a>
                <?php else: ?>
                    <a href="/login" class="btn btn--outline">Đăng nhập</a>
                    <a href="/register" class="btn btn--primary">Đăng ký</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main><?= $content ?></main>

    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?= date('Y') ?> HoaHocCoNga.Com — Nền tảng học Hóa học trực tuyến.</p>
        </div>
    </footer>

    <script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/theme.js')) ?>"></script>
</body>
</html>
