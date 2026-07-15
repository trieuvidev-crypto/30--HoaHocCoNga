<!doctype html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? \App\Core\View::e($pageTitle) . ' — ' : '' ?>HoaHocCoNga.Com</title>
    <meta name="description" content="Nền tảng học Hóa học trực tuyến hàng đầu dành cho học sinh THCS, THPT và luyện thi.">
    <meta name="csrf-token" content="<?= \App\Core\View::e($_SESSION['_csrf_token'] ?? '') ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/components.css')) ?>">
</head>
<body>
    <?= $content ?>

    <script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/theme.js')) ?>"></script>
</body>
</html>
