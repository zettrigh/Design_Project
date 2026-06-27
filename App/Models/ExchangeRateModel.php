<?php

namespace App\Models;

class ExchangeRateModel
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function getRate(string $from, string $to): ?float
    {
        $stmt = $this->db->prepare(
            "SELECT rate FROM exchange_rates
             WHERE from_currency = :from AND to_currency = :to LIMIT 1"
        );
        $stmt->execute([':from' => strtoupper(trim($from)), ':to' => strtoupper(trim($to))]);
        $row = $stmt->fetch();
        return $row ? (float) $row['rate'] : null;
    }

    public function setRate(string $from, string $to, float $rate, ?int $updatedBy = null): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO exchange_rates (from_currency, to_currency, rate, updated_by)
             VALUES (:from, :to, :rate, :updated_by)
             ON DUPLICATE KEY UPDATE rate = :rate2, updated_by = :updated_by2"
        );
        return $stmt->execute([
            ':from'        => strtoupper(trim($from)),
            ':to'          => strtoupper(trim($to)),
            ':rate'        => $rate,
            ':updated_by'  => $updatedBy,
            ':rate2'       => $rate,
            ':updated_by2' => $updatedBy,
        ]);
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT er.*, u.username AS updated_by_name
             FROM exchange_rates er
             LEFT JOIN users u ON u.id = er.updated_by
             ORDER BY er.from_currency, er.to_currency"
        );
        return $stmt->fetchAll();
    }
}
