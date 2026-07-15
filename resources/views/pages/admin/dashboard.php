<div class="dashboard-topbar">
    <div class="dashboard-topbar__greeting">
        <h1>Bảng điều khiển quản trị</h1>
        <p>Tổng quan hệ thống HoaHocCoNga.Com.</p>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-card__label">Giao dịch chờ xác nhận</div>
        <div class="stat-card__value"><?= \App\Core\View::e($pendingPaymentsCount) ?></div>
    </div>
</div>

<?php if ($pendingPaymentsCount > 0): ?>
    <div class="card">
        <p>Có <strong><?= \App\Core\View::e($pendingPaymentsCount) ?></strong> giao dịch đang chờ xác nhận thanh toán.</p>
        <a href="/administrator/payments" class="btn btn--primary">Xử lý ngay</a>
    </div>
<?php endif; ?>
