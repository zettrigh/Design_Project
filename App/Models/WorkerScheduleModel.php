<?php

namespace App\Models;

class WorkerScheduleModel
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function getByWorker(int $workerId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, worker_id, day_of_week, start_time, end_time, is_active
             FROM worker_schedules WHERE worker_id = :worker_id ORDER BY day_of_week ASC"
        );
        $stmt->execute([':worker_id' => $workerId]);
        return $stmt->fetchAll();
    }

    public function getByWorkerAndDay(int $workerId, int $dayOfWeek): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, worker_id, day_of_week, start_time, end_time, is_active
             FROM worker_schedules WHERE worker_id = :worker_id AND day_of_week = :day LIMIT 1"
        );
        $stmt->execute([':worker_id' => $workerId, ':day' => $dayOfWeek]);
        return $stmt->fetch();
    }

    public function upsert(int $workerId, int $dayOfWeek, string $startTime, string $endTime, bool $isActive): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO worker_schedules (worker_id, day_of_week, start_time, end_time, is_active)
             VALUES (:worker_id, :day, :start_time, :end_time, :is_active)
             ON DUPLICATE KEY UPDATE start_time = :start_time2, end_time = :end_time2, is_active = :is_active2"
        );
        return $stmt->execute([
            ':worker_id'   => $workerId,
            ':day'         => $dayOfWeek,
            ':start_time'  => $startTime,
            ':end_time'    => $endTime,
            ':is_active'   => $isActive ? 1 : 0,
            ':start_time2' => $startTime,
            ':end_time2'   => $endTime,
            ':is_active2'  => $isActive ? 1 : 0,
        ]);
    }

    public function getWorkersAvailableForDate(int $dayOfWeek, string $date, string $startTime, string $endTime): array
    {
        $stmt = $this->db->prepare(
            "SELECT ws.worker_id, u.username, ws.start_time, ws.end_time
             FROM worker_schedules ws
             JOIN users u ON u.id = ws.worker_id
             WHERE ws.day_of_week = :day AND ws.is_active = 1
             AND ws.start_time <= :start_time AND ws.end_time >= :end_time
             AND ws.worker_id NOT IN (
                 SELECT r.worker_id FROM reservations r
                 WHERE r.appointment_date = :date AND r.status != 'cancelled'
                 AND r.appointment_time < :end_time2 AND :start_time2 < r.end_time
             )
             ORDER BY u.username"
        );
        $stmt->execute([
            ':day'          => $dayOfWeek,
            ':start_time'   => $startTime,
            ':end_time'     => $endTime,
            ':date'         => $date,
            ':start_time2'  => $startTime,
            ':end_time2'    => $endTime,
        ]);
        return $stmt->fetchAll();
    }

    public function getActiveScheduleByWorkerAndDay(int $workerId, int $dayOfWeek): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, worker_id, day_of_week, start_time, end_time, is_active
             FROM worker_schedules WHERE worker_id = :worker_id AND day_of_week = :day AND is_active = 1 LIMIT 1"
        );
        $stmt->execute([':worker_id' => $workerId, ':day' => $dayOfWeek]);
        return $stmt->fetch();
    }

    public function getAllActiveWorkers(): array
    {
        $stmt = $this->db->query(
            "SELECT u.id, u.username, u.email
             FROM users u
             WHERE u.role = 'worker' AND u.id IN (
                 SELECT DISTINCT ws.worker_id FROM worker_schedules ws WHERE ws.is_active = 1
             )
             ORDER BY u.username"
        );
        return $stmt->fetchAll();
    }
}
