<section class="section">
    <div class="container tool-panel">
        <div class="section__header">
            <h1>Công cụ Hóa học</h1>
            <p>Cân bằng phương trình, tính toán nhanh — miễn phí, không cần đăng nhập.</p>
        </div>

        <div class="card" style="margin-bottom: var(--space-8);">
            <h3>Cân bằng phương trình hóa học</h3>
            <p style="font-size: var(--text-small);">Nhập công thức chất tham gia và sản phẩm, cách nhau bởi dấu phẩy.</p>

            <div class="field">
                <label class="field__label" for="reactants">Chất tham gia (VD: H2, O2)</label>
                <input class="field__input" type="text" id="reactants" placeholder="H2, O2">
            </div>

            <div class="field">
                <label class="field__label" for="products">Sản phẩm (VD: H2O)</label>
                <input class="field__input" type="text" id="products" placeholder="H2O">
            </div>

            <button type="button" class="btn btn--primary btn--block" id="balance-btn">
                <span class="btn__spinner"></span>
                <span class="btn__label">Cân bằng</span>
            </button>

            <div class="alert alert--danger" id="balance-error" role="alert"></div>

            <div class="tool-result" id="balance-result">
                <div class="tool-result__equation" id="balance-equation"></div>
            </div>
        </div>

        <div class="card">
            <h3>Tính khối lượng mol (M)</h3>

            <div class="field">
                <label class="field__label" for="molar-formula">Công thức hóa học</label>
                <input class="field__input" type="text" id="molar-formula" placeholder="H2SO4">
            </div>

            <button type="button" class="btn btn--outline btn--block" id="molar-btn">Tính khối lượng mol</button>

            <div class="alert alert--danger" id="molar-error" role="alert"></div>

            <div class="tool-result" id="molar-result">
                <div class="tool-result__equation" id="molar-value"></div>
                <div class="tool-result__steps" id="molar-steps"></div>
            </div>
        </div>
    </div>
</section>

<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/chemistry-tools.js')) ?>"></script>
