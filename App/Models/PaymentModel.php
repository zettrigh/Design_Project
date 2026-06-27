<?php

namespace App\Models;

class PaymentModel
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function createPayment(int $reservationId, int $userId, float $amount, string $currency, float $exchangeRate, float $amountUsd, string $paymentMethod, string $transactionId): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO payments (reservation_id, user_id, amount, currency, exchange_rate, amount_usd, payment_method, transaction_id, status)
             VALUES (:reservation_id, :user_id, :amount, :currency, :exchange_rate, :amount_usd, :payment_method, :transaction_id, 'completed')"
        );
        $stmt->execute([
            ':reservation_id'  => $reservationId,
            ':user_id'         => $userId,
            ':amount'          => $amount,
            ':currency'        => $currency,
            ':exchange_rate'   => $exchangeRate,
            ':amount_usd'      => $amountUsd,
            ':payment_method'  => $paymentMethod,
            ':transaction_id'  => $transactionId,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getPaymentById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, u.username AS user_name, h.name AS hairstyle_name
             FROM payments p
             JOIN users u ON u.id = p.user_id
             JOIN reservations r ON r.id = p.reservation_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             WHERE p.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getPaymentsByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT p.*, h.name AS hairstyle_name
             FROM payments p
             JOIN reservations r ON r.id = p.reservation_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             WHERE p.user_id = :user_id
             ORDER BY p.created_at DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getAllPayments(): array
    {
        $stmt = $this->db->query(
            "SELECT p.*, u.username AS user_name, h.name AS hairstyle_name
             FROM payments p
             JOIN users u ON u.id = p.user_id
             JOIN reservations r ON r.id = p.reservation_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             ORDER BY p.created_at DESC"
        );
        return $stmt->fetchAll();
    }

    public function updatePaymentStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE payments SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }
}
