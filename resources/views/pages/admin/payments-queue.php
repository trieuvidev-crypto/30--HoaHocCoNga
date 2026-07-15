<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1>Xác nhận thanh toán</h1>
        <p>Danh sách giao dịch chuyển khoản đang chờ xác nhận thủ công.</p>
    </div>
</div>

<div class="alert alert--danger" id="payment-action-error" role="alert"></div>
<div class="alert alert--success" id="payment-action-success" role="status"></div>

<?php if (empty($pendingPayments)): ?>
    <div class="empty-state card">
        <p>Không có giao dịch nào đang chờ xác nhận. 🎉</p>
    </div>
<?php else: ?>
    <div id="payments-queue">
        <?php foreach ($pendingPayments as $payment): ?>
            <div class="card" style="margin-bottom: var(--space-4);" data-payment-row="<?= \App\Core\View::e($payment['uuid']) ?>">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; gap: var(--space-4); flex-wrap: wrap;">
                    <div>
                        <div style="font-weight:700;">Đơn hàng #<?= \App\Core\View::e($payment['order_number']) ?></div>
                        <div style="font-size: var(--text-small); color: var(--color-text-secondary);">
                            Mã giao dịch: <span style="font-family: var(--font-mono);"><?= \App\Core\View::e($payment['transaction_number']) ?></span>
                        </div>
                        <div style="font-size: var(--text-title); font-weight:800; color: var(--color-primary); margin-top: var(--space-2);">
                            <?= \App\Core\View::e(number_format((float) $payment['amount'])) ?>đ
                        </div>
                        <div style="font-size: var(--text-caption); color: var(--color-text-muted);">
                            Hết hạn: <?= \App\Core\View::e($payment['expires_at']) ?>
                        </div>
                    </div>

                    <div style="display:flex; gap: var(--space-2);">
                        <button type="button" class="btn btn--primary confirm-payment-btn" data-payment-uuid="<?= \App\Core\View::e($payment['uuid']) ?>">
                            Xác nhận
                        </button>
                        <button type="button" class="btn btn--outline reject-payment-btn" data-payment-uuid="<?= \App\Core\View::e($payment['uuid']) ?>">
                            Từ chối
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script type="module" src="<?= \App\Core\View::e(\App\Core\View::asset('js/modules/admin-payments.js')) ?>"></script>
