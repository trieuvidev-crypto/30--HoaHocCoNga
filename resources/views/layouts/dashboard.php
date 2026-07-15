<!doctype html>
<html lang="vi" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= isset($pageTitle) ? \App\Core\View::e($pageTitle) . ' — ' : '' ?>HoaHocCoNga.Com</title>
    <meta name="csrf-token" content="<?= \App\Core\View::e($_SESSION['_csrf_token'] ?? '') ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/tokens.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/base.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/components.css')) ?>">
    <link rel="stylesheet" href="<?= \App\Core\View::e(\App\Core\View::asset('css/dashboard.css')) ?>">
</head>
<body>
    <div class="dashboard-shell">
        <aside class="dashboard-sidebar">
            <a href="/dashboard" class="dashboard-sidebar__logo">
                <?= \App\Core\View::svg('logo') ?>
                <span>HoaHocCoNga</span>
            </a>

            <nav class="dashboard-nav">
                <?php foreach ($navItems ?? \App\Core\NavigationMenus::studentMenu($currentPath ?? '') as $item): ?>
                    <a href="<?= \App\Core\View::e($item['href']) ?>" class="dashboard-nav__item<?= $item['active'] ? ' is-active' : '' ?>">
                        <?= \App\Core\View::svg($item['icon']) ?>
                        <span><?= \App\Core\View::e($item['label']) ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <button type="button" id="logout-button" class="dashboard-nav__item" style="border: none; background: none; cursor: pointer; text-align: left; width: 100%;">
                <?= \App\Core\View::svg('logout') ?>
                <span>Đăng xuất</span>
            </button>
        </aside>

        <main class="dashboard-main">
            <?= $content ?>
        </main>
    </div>

    <script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/theme.js')) ?>"></script>
    <script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/dashboard.js')) ?>"></script>
</body>
</html>
