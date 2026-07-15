import { apiRequest } from './api.js';

const errorBox = document.getElementById('enroll-error');

function showError(message) {
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.add('is-visible');
}

async function checkout(courseUuid, button) {
    button.disabled = true;
    button.classList.add('is-loading');

    const { ok, status, body } = await apiRequest('/api/v1/orders/checkout', {
        method: 'POST',
        body: { course_uuid: courseUuid },
    });

    button.disabled = false;
    button.classList.remove('is-loading');

    if (status === 401) {
        window.location.href = '/login';

        return;
    }

    if (!ok) {
        showError(body.message || 'Không thể xử lý yêu cầu. Vui lòng thử lại.');

        return;
    }

    if (body.data && body.data.payment) {
        // Paid course: redirect to a payment page that shows the QR
        // (payment page itself is a follow-up task — for now, surface
        // the essential info so the flow is never a dead end).
        window.location.href = `/orders/${body.data.order.uuid}/payment`;
    } else {
        window.location.href = '/dashboard';
    }
}

document.querySelectorAll('[data-enroll-course], [data-checkout-course]').forEach((button) => {
    button.addEventListener('click', () => {
        const courseUuid = button.getAttribute('data-enroll-course') || button.getAttribute('data-checkout-course');
        checkout(courseUuid, button);
    });
});
