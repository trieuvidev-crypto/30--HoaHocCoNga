<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PaymentRepository
{
    public function __construct(private readonly Database $db)
    {
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne('SELECT * FROM payments WHERE id = :id', ['id' => $id]);
    }

    public function findByUuid(string $uuid): ?array
    {
        return $this->db->fetchOne('SELECT * FROM payments WHERE uuid = :uuid', ['uuid' => $uuid]);
    }

    public function findPendingByOrder(int $orderId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM payments WHERE order_id = :order_id AND status = 'pending' ORDER BY created_at DESC LIMIT 1",
            ['order_id' => $orderId]
        );
    }

    public function getActiveBankAccount(): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM payment_bank_accounts WHERE is_active = 1 ORDER BY id ASC LIMIT 1'
        );
    }

    public function findBankAccountById(int $id): ?array
    {
        return $this->db->fetchOne(
            'SELECT * FROM payment_bank_accounts WHERE id = :id',
            ['id' => $id]
        );
    }

    public function create(int $orderId, string $transactionNumber, float $amount, string $qrPayload, ?int $bankAccountId, string $expiresAt): array
    {
        $uuid = generate_uuid_v4();

        $this->db->query(
            'INSERT INTO payments (uuid, order_id, driver, transaction_number, bank_account_id, amount, qr_payload, status, expires_at, created_at, updated_at)
             VALUES (:uuid, :order_id, :driver, :txn, :bank_account_id, :amount, :qr_payload, :status, :expires_at, NOW(), NOW())',
            [
                'uuid' => $uuid,
                'order_id' => $orderId,
                'driver' => 'bank_qr',
                'txn' => $transactionNumber,
                'bank_account_id' => $bankAccountId,
                'amount' => $amount,
                'qr_payload' => $qrPayload,
                'status' => 'pending',
                'expires_at' => $expiresAt,
            ]
        );

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function markPaid(int $paymentId, int $verifiedBy, ?string $note): array
    {
        $this->db->query(
            "UPDATE payments SET status = 'paid', verified_by = :verified_by, verified_at = NOW(), verification_note = :note, updated_at = NOW() WHERE id = :id",
            ['verified_by' => $verifiedBy, 'note' => $note, 'id' => $paymentId]
        );

        return $this->findById($paymentId);
    }

    public function markFailed(int $paymentId, string $reason): array
    {
        $this->db->query(
            "UPDATE payments SET status = 'verification_failed', verification_note = :reason, updated_at = NOW() WHERE id = :id",
            ['reason' => $reason, 'id' => $paymentId]
        );

        return $this->findById($paymentId);
    }

    public function log(int $paymentId, string $event, ?int $actorUserId, ?array $context = null): void
    {
        $this->db->query(
            'INSERT INTO payment_logs (payment_id, event, actor_user_id, context, created_at)
             VALUES (:payment_id, :event, :actor_user_id, :context, NOW())',
            [
                'payment_id' => $paymentId,
                'event' => $event,
                'actor_user_id' => $actorUserId,
                'context' => $context !== null ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            ]
        );
    }

    /** @return array<int, array<string, mixed>> payments awaiting manual confirmation */
    public function findPendingConfirmations(int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT p.*, o.order_number, o.user_id FROM payments p
             INNER JOIN orders o ON o.id = p.order_id
             WHERE p.status = 'pending' AND p.expires_at > NOW()
             ORDER BY p.created_at ASC
             LIMIT {$limit}"
        );
    }
}
