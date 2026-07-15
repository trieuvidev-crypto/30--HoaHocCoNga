<div style="display: grid; grid-template-columns: 1fr; gap: var(--space-6);">
    <?php if ($activeLesson === null): ?>
        <div class="empty-state card">
            <p>Khóa học chưa có nội dung nào.</p>
        </div>
    <?php else: ?>
        <div class="card">
            <h2 style="margin-bottom: var(--space-2);"><?= \App\Core\View::e($activeLesson['title']) ?></h2>
            <?php if (!empty($activeLesson['summary'])): ?>
                <p><?= \App\Core\View::e($activeLesson['summary']) ?></p>
            <?php endif; ?>

            <?php if ($activeLesson['content_type'] === 'video'): ?>
                <div style="background:#000; border-radius: var(--radius-md); aspect-ratio: 16/9; display:flex; align-items:center; justify-content:center; color:#fff;">
                    <?php if (empty($activeLesson['video_media_id'])): ?>
                        <span style="font-size: var(--text-small); opacity: .7;">Video đang được xử lý.</span>
                    <?php else: ?>
                        <span style="font-size: var(--text-small); opacity: .7;">Trình phát video (media #<?= \App\Core\View::e($activeLesson['video_media_id']) ?>)</span>
                    <?php endif; ?>
                </div>
            <?php elseif (!empty($activeLesson['content_body'])): ?>
                <div><?= nl2br(\App\Core\View::e($activeLesson['content_body'])) ?></div>
            <?php else: ?>
                <p style="color: var(--color-text-muted);">Nội dung bài học đang được cập nhật.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3 style="margin-bottom: var(--space-4);">Nội dung khóa học</h3>

        <?php foreach ($chapters as $chapter): ?>
            <div style="margin-bottom: var(--space-4);">
                <div style="font-weight: 700; margin-bottom: var(--space-2);"><?= \App\Core\View::e($chapter['title']) ?></div>
                <?php foreach ($chapter['lessons'] as $lesson): ?>
                    <a href="?lesson=<?= \App\Core\View::e($lesson['uuid']) ?>"
                       style="display:block; padding: var(--space-2) var(--space-3); border-radius: var(--radius-sm);
                              color: <?= $activeLesson && $lesson['uuid'] === $activeLesson['uuid'] ? 'var(--color-primary)' : 'var(--color-text-secondary)' ?>;
                              background: <?= $activeLesson && $lesson['uuid'] === $activeLesson['uuid'] ? 'rgba(37,99,235,0.08)' : 'transparent' ?>;">
                        <?= \App\Core\View::e($lesson['title']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>
