<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class OrderRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM orders WHERE id = :id', ['id' => $id]);
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne('SELECT * FROM orders WHERE uuid = :uuid', ['uuid' => $uuid]);
    }

    /** @return array<int, array<string, mixed>> */
    public function findItemsByOrder(int $orderId): array
    {
        return $this->db->fetchAll('SELECT * FROM order_items WHERE order_id = :order_id', ['order_id' => $orderId]);
    }

    public function generateOrderNumber(): string
    {
        $prefix = config('payment.order.number_prefix', 'HHCN');

        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    /**
     * @param array<int, array{item_type: string, course_id: ?int, title_snapshot: string, unit_price: float, quantity: int, line_total: float}> $items
     */
    public function create(int $userId, array $items, float $subtotal, float $discount, ?int $couponId): array
    {
        $uuid = generate_uuid_v4();
        $orderNumber = $this->generateOrderNumber();
        $total = max(0, $subtotal - $discount);

        $this->db->query(
            'INSERT INTO orders (uuid, order_number, user_id, subtotal_amount, discount_amount, tax_amount, total_amount, coupon_id, status, expires_at, created_at, updated_at)
             VALUES (:uuid, :order_number, :user_id, :subtotal, :discount, 0, :total, :coupon_id, :status, :expires_at, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'coupon_id' => $couponId,
                'status' => $total <= 0 ? 'paid' : 'pending',
                'expires_at' => date('Y-m-d H:i:s', time() + (int) config('payment.order.expire_pending_after_minutes', 30) * 60),
            ]
        );

        $orderId = (int) $this->db->lastInsertId();

        foreach ($items as $item) {
            $this->db->query(
                'INSERT INTO order_items (uuid, order_id, item_type, course_id, title_snapshot, unit_price, quantity, line_total)
                 VALUES (:uuid, :order_id, :item_type, :course_id, :title_snapshot, :unit_price, :quantity, :line_total)',
                [
                    'uuid' => generate_uuid_v4(),
                    'order_id' => $orderId,
                    'item_type' => $item['item_type'],
                    'course_id' => $item['course_id'],
                    'title_snapshot' => $item['title_snapshot'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'line_total' => $item['line_total'],
                ]
            );
        }

        return $this->findById($orderId);
    }

    public function updateStatus(int $orderId, string $status): array
    {
        $paidAt = $status === 'paid' ? ', paid_at = NOW()' : '';

        $this->db->query(
            "UPDATE orders SET status = :status, updated_at = NOW() {$paidAt} WHERE id = :id",
            ['status' => $status, 'id' => $orderId]
        );

        return $this->findById($orderId);
    }

    public function findActiveEnrollment(int $courseId, int $studentId): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM course_enrollments WHERE course_id = :course_id AND student_id = :student_id',
            ['course_id' => $courseId, 'student_id' => $studentId]
        );
    }
}
