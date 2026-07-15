<section class="section">
    <div class="container" style="max-width: 860px;">
        <span class="public-course-card__badge"><?= \App\Core\View::e(match ($course['difficulty']) {
            'beginner' => 'Cơ bản',
            'intermediate' => 'Trung bình',
            'advanced' => 'Nâng cao',
            default => 'Chuyên sâu',
        }) ?></span>

        <h1><?= \App\Core\View::e($course['title']) ?></h1>
        <p style="font-size: var(--text-body-lg);"><?= \App\Core\View::e($course['short_description'] ?? '') ?></p>

        <div class="card" style="margin: var(--space-8) 0;">
            <?php if ($isEnrolled): ?>
                <a href="/dashboard/course/<?= \App\Core\View::e($course['uuid']) ?>" class="btn btn--primary btn--block">Vào học ngay</a>
            <?php elseif ((float) $course['price'] <= 0): ?>
                <button type="button" class="btn btn--primary btn--block" data-enroll-course="<?= \App\Core\View::e($course['uuid']) ?>">Đăng ký miễn phí</button>
            <?php else: ?>
                <div style="font-size: var(--text-title); font-weight: 800; margin-bottom: var(--space-4);">
                    <?= \App\Core\View::e(number_format((float) ($course['sale_price'] ?? $course['price']))) ?>đ
                </div>
                <button type="button" class="btn btn--primary btn--block" data-checkout-course="<?= \App\Core\View::e($course['uuid']) ?>">Mua khóa học</button>
            <?php endif; ?>
            <div class="alert alert--danger" id="enroll-error" role="alert"></div>
        </div>

        <h2>Nội dung khóa học</h2>

        <?php if (empty($chapters)): ?>
            <p>Nội dung đang được cập nhật.</p>
        <?php else: ?>
            <?php foreach ($chapters as $chapter): ?>
                <div class="card" style="margin-bottom: var(--space-4);">
                    <h3 style="margin-bottom: var(--space-3);"><?= \App\Core\View::e($chapter['title']) ?></h3>
                    <?php foreach ($chapter['lessons'] as $lesson): ?>
                        <div style="padding: var(--space-2) 0; color: var(--color-text-secondary); display: flex; justify-content: space-between;">
                            <span><?= \App\Core\View::e($lesson['title']) ?></span>
                            <?php if ($lesson['is_preview']): ?><span style="color: var(--color-accent); font-size: var(--text-caption);">Xem thử</span><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/checkout.js')) ?>"></script>
