import { apiRequest } from './api.js';

const errorBox = document.getElementById('payment-action-error');
const successBox = document.getElementById('payment-action-success');

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

function removeRow(paymentUuid) {
    const row = document.querySelector(`[data-payment-row="${paymentUuid}"]`);

    if (row) {
        row.remove();
    }
}

document.querySelectorAll('.confirm-payment-btn').forEach((button) => {
    button.addEventListener('click', async () => {
        const paymentUuid = button.getAttribute('data-payment-uuid');
        button.disabled = true;

        const { ok, body } = await apiRequest(`/administrator/payments/${paymentUuid}/confirm`, {
            method: 'POST',
            body: {},
        });

        if (!ok) {
            button.disabled = false;
            showError(body.message || 'Không thể xác nhận giao dịch.');

            return;
        }

        showSuccess('Đã xác nhận thanh toán. Học viên được cấp quyền truy cập khóa học.');
        removeRow(paymentUuid);
    });
});

document.querySelectorAll('.reject-payment-btn').forEach((button) => {
    button.addEventListener('click', async () => {
        const paymentUuid = button.getAttribute('data-payment-uuid');
        const reason = window.prompt('Lý do từ chối giao dịch này:');

        if (!reason) {
            return;
        }

        button.disabled = true;

        const { ok, body } = await apiRequest(`/administrator/payments/${paymentUuid}/reject`, {
            method: 'POST',
            body: { reason },
        });

        if (!ok) {
            button.disabled = false;
            showError(body.message || 'Không thể từ chối giao dịch.');

            return;
        }

        showSuccess('Đã từ chối giao dịch.');
        removeRow(paymentUuid);
    });
});
