<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1>Xin chào, <?= \App\Core\View::e($user['display_name']) ?> 👋</h1>
        <p>Chúc bạn có một buổi học Hóa học hiệu quả hôm nay.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label">Khóa học đang học</div>
        <div class="stat-card__value"><?= \App\Core\View::e(count($enrolledCourses)) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-card__label">Thông báo chưa đọc</div>
        <div class="stat-card__value"><?= \App\Core\View::e(count($unreadNotifications)) ?></div>
    </div>
</div>

<h2>Tiếp tục học</h2>

<?php if (empty($enrolledCourses)): ?>
    <div class="empty-state card">
        <p>Bạn chưa đăng ký khóa học nào.</p>
        <a href="/courses" class="btn btn--primary">Khám phá khóa học</a>
    </div>
<?php else: ?>
    <div class="course-grid">
        <?php foreach ($enrolledCourses as $course): ?>
            <div class="course-tile">
                <div class="course-tile__title"><?= \App\Core\View::e($course['title']) ?></div>
                <div class="progress-bar">
                    <div class="progress-bar__fill" style="width: <?= \App\Core\View::e((float) $course['completion_percent']) ?>%;"></div>
                </div>
                <p style="margin: 0; font-size: var(--text-small);">
                    Hoàn thành <?= \App\Core\View::e(round((float) $course['completion_percent'])) ?>%
                </p>
                <a href="/dashboard/course/<?= \App\Core\View::e($course['uuid']) ?>" class="btn btn--outline btn--block" style="margin-top: var(--space-4);">
                    Tiếp tục học
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
