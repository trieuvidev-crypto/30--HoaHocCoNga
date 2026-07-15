import { apiRequest } from './api.js';

const logoutButton = document.getElementById('logout-button');

if (logoutButton) {
    logoutButton.addEventListener('click', async () => {
        logoutButton.disabled = true;

        await apiRequest('/api/v1/auth/logout', { method: 'POST', body: {} });

        window.location.href = '/login';
    });
}
