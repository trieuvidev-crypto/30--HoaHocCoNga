<section class="section">
    <div class="container">
        <div class="section__header">
            <h1>Khóa học Hóa học</h1>
            <p>Từ nền tảng THCS đến luyện thi THPT Quốc gia và Olympic Hóa học.</p>
        </div>

        <?php if (empty($courses)): ?>
            <div class="empty-state card">
                <p>Hiện chưa có khóa học nào được xuất bản. Vui lòng quay lại sau.</p>
            </div>
        <?php else: ?>
            <div class="public-course-grid">
                <?php foreach ($courses as $course): ?>
                    <a href="/courses/<?= \App\Core\View::e($course['slug']) ?>" class="public-course-card">
                        <div class="public-course-card__body">
                            <span class="public-course-card__badge"><?= \App\Core\View::e(match ($course['difficulty']) {
                                'beginner' => 'Cơ bản',
                                'intermediate' => 'Trung bình',
                                'advanced' => 'Nâng cao',
                                default => 'Chuyên sâu',
                            }) ?></span>
                            <h3 class="public-course-card__title"><?= \App\Core\View::e($course['title']) ?></h3>
                            <p><?= \App\Core\View::e($course['short_description'] ?? '') ?></p>
                            <div class="public-course-card__price">
                                <?php if ((float) $course['price'] <= 0): ?>
                                    Miễn phí
                                <?php else: ?>
                                    <?= \App\Core\View::e(number_format((float) ($course['sale_price'] ?? $course['price']))) ?>đ
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
