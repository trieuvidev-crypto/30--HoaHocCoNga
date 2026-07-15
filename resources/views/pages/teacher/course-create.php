<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1>Tạo khóa học mới</h1>
        <p>Điền thông tin cơ bản — bạn có thể chỉnh sửa và thêm nội dung sau khi tạo.</p>
    </div>
</div>

<div class="card" style="max-width: 640px;">
    <div class="alert alert--danger" id="course-create-error" role="alert"></div>

    <form id="course-create-form" novalidate>
        <div class="field">
            <label class="field__label" for="title">Tên khóa học</label>
            <input class="field__input" type="text" id="title" name="title" required maxlength="200">
        </div>

        <div class="field">
            <label class="field__label" for="short_description">Mô tả ngắn</label>
            <textarea class="field__input" id="short_description" name="short_description" rows="3" maxlength="500"></textarea>
        </div>

        <div class="field">
            <label class="field__label" for="course_type">Loại khóa học</label>
            <select class="field__input" id="course_type" name="course_type">
                <option value="paid">Trả phí</option>
                <option value="free">Miễn phí</option>
                <option value="combo">Combo</option>
                <option value="live">Trực tuyến (Live)</option>
            </select>
        </div>

        <div class="field">
            <label class="field__label" for="price">Giá (VNĐ)</label>
            <input class="field__input" type="number" id="price" name="price" min="0" step="1000" value="0">
        </div>

        <button type="submit" class="btn btn--primary btn--block" id="course-create-submit">
            <span class="btn__spinner"></span>
            <span class="btn__label">Tạo khóa học</span>
        </button>
    </form>
</div>

<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/teacher-course-create.js')) ?>"></script>
