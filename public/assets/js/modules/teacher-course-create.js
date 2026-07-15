import { apiRequest } from './api.js';

const form = document.getElementById('course-create-form');

if (form) {
    const errorBox = document.getElementById('course-create-error');
    const submitButton = document.getElementById('course-create-submit');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        errorBox.classList.remove('is-visible');
        submitButton.disabled = true;
        submitButton.classList.add('is-loading');

        const { ok, body } = await apiRequest('/api/v1/courses', {
            method: 'POST',
            body: {
                title: form.title.value.trim(),
                short_description: form.short_description.value.trim(),
                course_type: form.course_type.value,
                price: Number(form.price.value) || 0,
            },
        });

        submitButton.disabled = false;
        submitButton.classList.remove('is-loading');

        if (!ok) {
            errorBox.textContent = body.message || 'Không thể tạo khóa học. Vui lòng thử lại.';
            errorBox.classList.add('is-visible');

            return;
        }

        window.location.href = `/teacher/courses/${body.data.uuid}/edit`;
    });
}
