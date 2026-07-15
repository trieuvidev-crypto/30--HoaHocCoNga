<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1>Khóa học của tôi</h1>
        <p>Quản lý toàn bộ khóa học bạn đã tạo.</p>
    </div>
    <a href="/teacher/courses/create" class="btn btn--primary">
        <?= \App\Core\View::svg('plus') ?>
        <span>Tạo khóa học mới</span>
    </a>
</div>

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
                    <strong><?= \App\Core\View::e(match ($course['status']) {
                        'draft' => 'Bản nháp',
                        'published' => 'Đã xuất bản',
                        'archived' => 'Đã lưu trữ',
                        'in_review' => 'Đang chờ duyệt',
                        default => $course['status'],
                    }) ?></strong>
                    · <?= \App\Core\View::e($course['enrollment_count']) ?> học viên
                    · <?= \App\Core\View::e(number_format((float) $course['price'])) ?>đ
                </p>
                <a href="/teacher/courses/<?= \App\Core\View::e($course['uuid']) ?>/edit" class="btn btn--outline btn--block">
                    <?= \App\Core\View::svg('edit') ?>
                    <span>Chỉnh sửa</span>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
