<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1>Chào giáo viên <?= \App\Core\View::e($user['display_name']) ?> 👋</h1>
        <p>Quản lý khóa học và theo dõi học viên của bạn.</p>
    </div>
    <a href="/teacher/courses/create" class="btn btn--primary">
        <?= \App\Core\View::svg('plus') ?>
        <span>Tạo khóa học mới</span>
    </a>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label">Tổng số khóa học</div>
        <div class="stat-card__value"><?= \App\Core\View::e(count($courses)) ?></div>
    </div>
</div>

<h2>Khóa học gần đây</h2>

<?php if (empty($courses)): ?>
    <div class="empty-state card">
        <p>Bạn chưa tạo khóa học nào.</p>
        <a href="/teacher/courses/create" class="btn btn--primary">Tạo khóa học đầu tiên</a>
    </div>
<?php else: ?>
    <div class="course-grid">
        <?php foreach ($courses as $course): ?>
            <div class="course-tile">
                <div class="course-tile__title"><?= \App\Core\View::e($course['title']) ?></div>
                <p style="font-size: var(--text-small); margin-bottom: var(--space-3);">
                    Trạng thái:
                    <strong><?= \App\Core\View::e(match ($course['status']) {
                        'draft' => 'Bản nháp',
                        'published' => 'Đã xuất bản',
                        'archived' => 'Đã lưu trữ',
                        'in_review' => 'Đang chờ duyệt',
                        default => $course['status'],
                    }) ?></strong>
                    · <?= \App\Core\View::e($course['enrollment_count']) ?> học viên
                </p>
                <a href="/teacher/courses/<?= \App\Core\View::e($course['uuid']) ?>/edit" class="btn btn--outline btn--block">
                    <?= \App\Core\View::svg('edit') ?>
                    <span>Chỉnh sửa</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: var(--space-6);">
        <a href="/teacher/courses">Xem tất cả khóa học →</a>
    </div>
<?php endif; ?>
