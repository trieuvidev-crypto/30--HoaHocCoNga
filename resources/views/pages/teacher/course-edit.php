<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1><?= \App\Core\View::e($course['title']) ?></h1>
        <p>
            Trạng thái: <strong><?= \App\Core\View::e(match ($course['status']) {
                'draft' => 'Bản nháp', 'published' => 'Đã xuất bản',
                'archived' => 'Đã lưu trữ', 'in_review' => 'Đang chờ duyệt',
                default => $course['status'],
            }) ?></strong>
        </p>
    </div>
    <div style="display:flex; gap: var(--space-3);">
        <?php if ($course['status'] !== 'published'): ?>
            <button type="button" class="btn btn--primary" id="publish-course-btn" data-course-uuid="<?= \App\Core\View::e($course['uuid']) ?>">Xuất bản</button>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert--danger" id="course-action-error" role="alert"></div>
<div class="alert alert--success" id="course-action-success" role="status"></div>

<h2>Chương &amp; bài học</h2>

<div id="chapters-list">
    <?php foreach ($chapters as $chapter): ?>
        <div class="card" style="margin-bottom: var(--space-4);">
            <h3 style="margin-bottom: var(--space-3);"><?= \App\Core\View::e($chapter['title']) ?></h3>

            <?php foreach ($chapter['lessons'] as $lesson): ?>
                <div style="padding: var(--space-2) 0; color: var(--color-text-secondary); display:flex; justify-content:space-between;">
                    <span><?= \App\Core\View::e($lesson['title']) ?></span>
                </div>
            <?php endforeach; ?>

            <form class="add-lesson-form" data-chapter-id="<?= \App\Core\View::e($chapter['id']) ?>" style="margin-top: var(--space-4); display:flex; gap: var(--space-2);">
                <input class="field__input" type="text" name="title" placeholder="Tên bài học mới" required style="flex:1;">
                <button type="submit" class="btn btn--outline">
                    <?= \App\Core\View::svg('plus') ?>
                    <span>Thêm bài học</span>
                </button>
            </form>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <form id="add-chapter-form" style="display:flex; gap: var(--space-2);">
        <input class="field__input" type="text" name="title" placeholder="Tên chương mới" required style="flex:1;">
        <button type="submit" class="btn btn--primary">
            <?= \App\Core\View::svg('plus') ?>
            <span>Thêm chương</span>
        </button>
    </form>
</div>

<script>
    window.__COURSE_UUID__ = <?= json_encode($course['uuid']) ?>;
</script>
<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/teacher-course-edit.js')) ?>"></script>
