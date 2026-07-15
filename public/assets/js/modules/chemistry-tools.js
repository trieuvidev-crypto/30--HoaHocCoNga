import { apiRequest } from './api.js';

function setLoading(button, loading) {
    button.disabled = loading;
    button.classList.toggle('is-loading', loading);
}

function showError(el, message) {
    el.textContent = message;
    el.classList.add('is-visible');
}

function hideError(el) {
    el.classList.remove('is-visible');
    el.textContent = '';
}

// ---- Equation Balancer ----
const balanceBtn = document.getElementById('balance-btn');

if (balanceBtn) {
    balanceBtn.addEventListener('click', async () => {
        const errorBox = document.getElementById('balance-error');
        const resultBox = document.getElementById('balance-result');
        const equationEl = document.getElementById('balance-equation');

        hideError(errorBox);
        resultBox.classList.remove('is-visible');

        const reactants = document.getElementById('reactants').value.split(',').map((s) => s.trim()).filter(Boolean);
        const products = document.getElementById('products').value.split(',').map((s) => s.trim()).filter(Boolean);

        if (reactants.length === 0 || products.length === 0) {
            showError(errorBox, 'Vui lòng nhập ít nhất một chất tham gia và một sản phẩm.');

            return;
        }

        setLoading(balanceBtn, true);

        const { ok, body } = await apiRequest('/api/v1/chemistry/balance', {
            method: 'POST',
            body: { reactants, products },
        });

        setLoading(balanceBtn, false);

        if (!ok) {
            showError(errorBox, body.message || 'Không thể cân bằng phương trình.');

            return;
        }

        const left = body.data.reactants.map((r) => (r.coefficient > 1 ? `${r.coefficient}${r.formula}` : r.formula)).join(' + ');
        const right = body.data.products.map((p) => (p.coefficient > 1 ? `${p.coefficient}${p.formula}` : p.formula)).join(' + ');

        equationEl.textContent = `${left} → ${right}`;
        resultBox.classList.add('is-visible');
    });
}

// ---- Molar Mass ----
const molarBtn = document.getElementById('molar-btn');

if (molarBtn) {
    molarBtn.addEventListener('click', async () => {
        const errorBox = document.getElementById('molar-error');
        const resultBox = document.getElementById('molar-result');
        const valueEl = document.getElementById('molar-value');
        const stepsEl = document.getElementById('molar-steps');

        hideError(errorBox);
        resultBox.classList.remove('is-visible');

        const formula = document.getElementById('molar-formula').value.trim();

        if (!formula) {
            showError(errorBox, 'Vui lòng nhập công thức hóa học.');

            return;
        }

        setLoading(molarBtn, true);

        const { ok, body } = await apiRequest(`/api/v1/chemistry/calculator/molar-mass?formula=${encodeURIComponent(formula)}`);

        setLoading(molarBtn, false);

        if (!ok) {
            showError(errorBox, body.message || 'Không thể tính khối lượng mol.');

            return;
        }

        valueEl.textContent = `M = ${body.data.result} g/mol`;
        stepsEl.innerHTML = body.data.steps.map((step) => `<div>${step}</div>`).join('');
        resultBox.classList.add('is-visible');
    });
}
