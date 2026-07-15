<section class="section">
    <div class="container" style="max-width: 520px;">
        <h1>Thanh toán đơn hàng</h1>
        <p>Mã đơn hàng: <strong><?= \App\Core\View::e($order['order_number']) ?></strong></p>

        <?php if ($order['status'] === 'paid' || $order['status'] === 'completed'): ?>
            <div class="alert alert--success is-visible">Đơn hàng đã được thanh toán thành công.</div>
            <a href="/dashboard" class="btn btn--primary btn--block">Vào Dashboard</a>
        <?php elseif ($payment === null || $bankAccount === null): ?>
            <div class="alert alert--danger is-visible">Không tìm thấy thông tin thanh toán cho đơn hàng này.</div>
        <?php else: ?>
            <div class="card">
                <h3>Chuyển khoản ngân hàng</h3>
                <table style="width: 100%; border-collapse: collapse; font-size: var(--text-small);">
                    <tr>
                        <td style="padding: var(--space-2) 0; color: var(--color-text-muted);">Ngân hàng</td>
                        <td style="padding: var(--space-2) 0; text-align: right; font-weight: 600;"><?= \App\Core\View::e($bankAccount['bank_name']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: var(--space-2) 0; color: var(--color-text-muted);">Số tài khoản</td>
                        <td style="padding: var(--space-2) 0; text-align: right; font-weight: 600;"><?= \App\Core\View::e($bankAccount['account_number']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: var(--space-2) 0; color: var(--color-text-muted);">Chủ tài khoản</td>
                        <td style="padding: var(--space-2) 0; text-align: right; font-weight: 600;"><?= \App\Core\View::e($bankAccount['account_holder']) ?></td>
                    </tr>
                    <tr>
                        <td style="padding: var(--space-2) 0; color: var(--color-text-muted);">Số tiền</td>
                        <td style="padding: var(--space-2) 0; text-align: right; font-weight: 800; color: var(--color-primary);"><?= \App\Core\View::e(number_format((float) $payment['amount'])) ?>đ</td>
                    </tr>
                    <tr>
                        <td style="padding: var(--space-2) 0; color: var(--color-text-muted);">Nội dung chuyển khoản</td>
                        <td style="padding: var(--space-2) 0; text-align: right; font-weight: 600; font-family: var(--font-mono);"><?= \App\Core\View::e($payment['transaction_number']) ?></td>
                    </tr>
                </table>

                <p style="margin-top: var(--space-5); font-size: var(--text-small);">
                    ⚠️ Vui lòng ghi <strong>chính xác nội dung chuyển khoản</strong> ở trên để hệ thống đối soát nhanh chóng.
                    Sau khi chuyển khoản, đội ngũ hỗ trợ sẽ xác nhận trong thời gian sớm nhất.
                </p>

                <p style="font-size: var(--text-caption); color: var(--color-text-muted);">
                    Giao dịch hết hạn lúc: <?= \App\Core\View::e($payment['expires_at']) ?>
                </p>
            </div>
        <?php endif; ?>
    </div>
</section>
