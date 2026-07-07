<?php

namespace App\Models;

class BusinessHoursModel
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function getAll(): array
    {
        $stmt = $this->db->query(
            "SELECT id, day_of_week, open_time, close_time, is_active
             FROM business_hours ORDER BY day_of_week ASC"
        );
        return $stmt->fetchAll();
    }

    public function getByDay(int $dayOfWeek): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, day_of_week, open_time, close_time, is_active
             FROM business_hours WHERE day_of_week = :day LIMIT 1"
        );
        $stmt->execute([':day' => $dayOfWeek]);
        return $stmt->fetch();
    }

    public function getActiveHoursByDay(int $dayOfWeek): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, day_of_week, open_time, close_time, is_active
             FROM business_hours WHERE day_of_week = :day AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([':day' => $dayOfWeek]);
        return $stmt->fetch();
    }

    public function upsert(int $dayOfWeek, string $openTime, string $closeTime, bool $isActive): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO business_hours (day_of_week, open_time, close_time, is_active)
             VALUES (:day, :open_time, :close_time, :is_active)
             ON DUPLICATE KEY UPDATE open_time = :open_time2, close_time = :close_time2, is_active = :is_active2"
        );
        return $stmt->execute([
            ':day'         => $dayOfWeek,
            ':open_time'   => $openTime,
            ':close_time'  => $closeTime,
            ':is_active'   => $isActive ? 1 : 0,
            ':open_time2'  => $openTime,
            ':close_time2' => $closeTime,
            ':is_active2'  => $isActive ? 1 : 0,
        ]);
    }
}
