<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Core\Database;
use App\Repositories\CourseRepository;
use App\Repositories\OrderRepository;
use RuntimeException;

final class OrderService
{
    public function __construct(
        private readonly OrderRepository $orders,
        private readonly CourseRepository $courses,
        private readonly Database $db
    ) {
    }

    /**
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    public function createFromCourse(int $studentId, string $courseUuid, ?string $couponCode = null): array
    {
        $course = $this->courses->findByUuid($courseUuid);

        if ($course === null) {
            throw new RuntimeException('Không tìm thấy khóa học.');
        }

        if ($course['status'] !== 'published') {
            throw new RuntimeException('Khóa học chưa được xuất bản, không thể mua.');
        }

        if ($this->orders->findActiveEnrollment((int) $course['id'], $studentId) !== null) {
            throw new RuntimeException('Bạn đã sở hữu khóa học này rồi.');
        }

        $unitPrice = (float) ($course['sale_price'] ?? $course['price']);
        $subtotal = $unitPrice;
        $discount = 0.0;
        $couponId = null;

        if ($couponCode !== null && trim($couponCode) !== '') {
            [$couponId, $discount] = $this->applyCoupon(trim($couponCode), $studentId, $subtotal);
        }

        return $this->db->transaction(function () use ($studentId, $course, $unitPrice, $subtotal, $discount, $couponId) {
            $order = $this->orders->create(
                $studentId,
                [[
                    'item_type' => 'course',
                    'course_id' => $course['id'],
                    'title_snapshot' => $course['title'],
                    'unit_price' => $unitPrice,
                    'quantity' => 1,
                    'line_total' => $unitPrice,
                ]],
                $subtotal,
                $discount,
                $couponId
            );

            if ($couponId !== null) {
                $this->db->query(
                    'INSERT INTO coupon_usage (coupon_id, user_id, order_id, used_at) VALUES (:coupon_id, :user_id, :order_id, NOW())',
                    ['coupon_id' => $couponId, 'user_id' => $studentId, 'order_id' => $order['id']]
                );
                $this->db->query('UPDATE coupons SET used_count = used_count + 1 WHERE id = :id', ['id' => $couponId]);
            }

            return $order;
        });
    }

    /**
     * @return array{0: int, 1: float} [coupon_id, discount_amount]
     * @throws RuntimeException with a Vietnamese, user-safe message
     */
    private function applyCoupon(string $code, int $userId, float $subtotal): array
    {
        $coupon = $this->db->fetchOne(
            'SELECT * FROM coupons WHERE code = :code AND is_active = 1',
            ['code' => $code]
        );

        if ($coupon === null) {
            throw new RuntimeException('Mã giảm giá không tồn tại hoặc đã bị vô hiệu hóa.');
        }

        $now = date('Y-m-d H:i:s');

        if ($coupon['starts_at'] !== null && $coupon['starts_at'] > $now) {
            throw new RuntimeException('Mã giảm giá chưa đến thời gian sử dụng.');
        }

        if ($coupon['expires_at'] !== null && $coupon['expires_at'] < $now) {
            throw new RuntimeException('Mã giảm giá đã hết hạn.');
        }

        if ($coupon['min_order_amount'] !== null && $subtotal < (float) $coupon['min_order_amount']) {
            throw new RuntimeException('Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã giảm giá này.');
        }

        if ($coupon['usage_limit'] !== null && (int) $coupon['used_count'] >= (int) $coupon['usage_limit']) {
            throw new RuntimeException('Mã giảm giá đã hết lượt sử dụng.');
        }

        $perUserLimit = $coupon['usage_limit_per_user'] ?? 1;
        $usedByUser = (int) ($this->db->fetchOne(
            'SELECT COUNT(*) AS total FROM coupon_usage WHERE coupon_id = :coupon_id AND user_id = :user_id',
            ['coupon_id' => $coupon['id'], 'user_id' => $userId]
        )['total'] ?? 0);

        if ($usedByUser >= (int) $perUserLimit) {
            throw new RuntimeException('Bạn đã sử dụng hết lượt cho phép với mã giảm giá này.');
        }

        $discount = $coupon['discount_type'] === 'percentage'
            ? $subtotal * ((float) $coupon['discount_value'] / 100)
            : (float) $coupon['discount_value'];

        if ($coupon['max_discount_amount'] !== null) {
            $discount = min($discount, (float) $coupon['max_discount_amount']);
        }

        $discount = min($discount, $subtotal);

        return [(int) $coupon['id'], $discount];
    }
}
