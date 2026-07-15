import { apiRequest } from './api.js';

const courseUuid = window.__COURSE_UUID__;
const errorBox = document.getElementById('course-action-error');
const successBox = document.getElementById('course-action-success');

function showError(message) {
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
    successBox.classList.remove('is-visible');
}

function showSuccess(message) {
    successBox.textContent = message;
    successBox.classList.add('is-visible');
    errorBox.classList.remove('is-visible');
}

// ---- Publish course ----
const publishBtn = document.getElementById('publish-course-btn');

if (publishBtn) {
    publishBtn.addEventListener('click', async () => {
        publishBtn.disabled = true;

        const { ok, body } = await apiRequest(`/api/v1/courses/${courseUuid}/publish`, { method: 'POST', body: {} });

        publishBtn.disabled = false;

        if (!ok) {
            showError(body.message || 'Không thể xuất bản khóa học.');

            return;
        }

        showSuccess('Xuất bản thành công!');
        setTimeout(() => window.location.reload(), 1000);
    });
}

// ---- Add chapter ----
const addChapterForm = document.getElementById('add-chapter-form');

if (addChapterForm) {
    addChapterForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const { ok, body } = await apiRequest(`/api/v1/courses/${courseUuid}/chapters`, {
            method: 'POST',
            body: { title: addChapterForm.title.value.trim() },
        });

        if (!ok) {
            showError(body.message || 'Không thể thêm chương.');

            return;
        }

        window.location.reload();
    });
}

// ---- Add lesson (one form per chapter, delegated) ----
document.querySelectorAll('.add-lesson-form').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const chapterId = form.getAttribute('data-chapter-id');

        const { ok, body } = await apiRequest(`/api/v1/courses/${courseUuid}/chapters/${chapterId}/lessons`, {
            method: 'POST',
            body: { title: form.title.value.trim() },
        });

        if (!ok) {
            showError(body.message || 'Không thể thêm bài học.');

            return;
        }

        window.location.reload();
    });
});
