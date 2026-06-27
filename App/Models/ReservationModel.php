<?php

namespace App\Models;

class ReservationModel
{
    private \PDO $db;

    private const BASE_COLUMNS = 'r.id, r.user_id, r.hairstyle_id, r.worker_id, r.appointment_date, r.appointment_time, r.end_time, r.status, r.reserved_at';

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function getAllReservations(): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::BASE_COLUMNS . ",
                    u.username AS user_name,
                    h.name AS hairstyle_name,
                    h.price,
                    h.image_url,
                    w.username AS worker_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             LEFT JOIN users w ON w.id = r.worker_id
             ORDER BY r.reserved_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserReservations(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::BASE_COLUMNS . ",
                    u.username AS user_name,
                    h.name AS hairstyle_name,
                    h.price,
                    h.image_url,
                    w.username AS worker_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             LEFT JOIN users w ON w.id = r.worker_id
             WHERE r.user_id = :user_id
             ORDER BY r.reserved_at DESC"
        );
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetchAll();
    }

    public function getReservationById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::BASE_COLUMNS . ",
                    u.username AS user_name,
                    h.name AS hairstyle_name,
                    h.price,
                    h.image_url,
                    h.duration_minutes,
                    w.username AS worker_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             LEFT JOIN users w ON w.id = r.worker_id
             WHERE r.id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function createReservation(int $userId, int $hairstyleId, ?int $workerId = null, ?string $appointmentDate = null, ?string $appointmentTime = null, ?string $endTime = null): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO reservations (user_id, hairstyle_id, worker_id, appointment_date, appointment_time, end_time, status)
             VALUES (:user_id, :hairstyle_id, :worker_id, :appointment_date, :appointment_time, :end_time, 'pending')"
        );
        $stmt->execute([
            ':user_id'           => $userId,
            ':hairstyle_id'      => $hairstyleId,
            ':worker_id'         => $workerId,
            ':appointment_date'  => $appointmentDate,
            ':appointment_time'  => $appointmentTime,
            ':end_time'          => $endTime,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateReservationStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("UPDATE reservations SET status = :status WHERE id = :id");
        return $stmt->execute([':status' => $status, ':id' => $id]);
    }

    public function updateReservationSchedule(int $id, int $workerId, string $appointmentDate, string $appointmentTime, string $endTime): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE reservations SET worker_id = :worker_id, appointment_date = :appointment_date,
             appointment_time = :appointment_time, end_time = :end_time WHERE id = :id"
        );
        return $stmt->execute([
            ':id'               => $id,
            ':worker_id'        => $workerId,
            ':appointment_date' => $appointmentDate,
            ':appointment_time' => $appointmentTime,
            ':end_time'         => $endTime,
        ]);
    }

    public function assignWorker(int $id, int $workerId): bool
    {
        $stmt = $this->db->prepare("UPDATE reservations SET worker_id = :worker_id WHERE id = :id");
        return $stmt->execute([':worker_id' => $workerId, ':id' => $id]);
    }

    public function hasTimeConflict(string $appointmentDate, string $appointmentTime, string $endTime, ?int $workerId = null, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM reservations
                WHERE appointment_date = :appointment_date
                AND status != 'cancelled'
                AND appointment_time < :end_time AND :appointment_time < end_time";

        $params = [
            ':appointment_date' => $appointmentDate,
            ':appointment_time' => $appointmentTime,
            ':end_time'         => $endTime,
        ];

        if ($workerId !== null) {
            $sql .= " AND worker_id = :worker_id";
            $params[':worker_id'] = $workerId;
        }

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() !== false;
    }

    public function getReservationsByDate(string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::BASE_COLUMNS . ",
                    u.username AS user_name,
                    h.name AS hairstyle_name,
                    h.price,
                    w.username AS worker_name
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             LEFT JOIN users w ON w.id = r.worker_id
             WHERE r.appointment_date = :date AND r.status != 'cancelled'
             ORDER BY r.appointment_time ASC"
        );
        $stmt->execute([':date' => $date]);
        return $stmt->fetchAll();
    }

    public function getReservationsByWorkerAndDate(int $workerId, string $date): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::BASE_COLUMNS . ",
                    u.username AS user_name,
                    h.name AS hairstyle_name,
                    h.price
             FROM reservations r
             JOIN users u ON u.id = r.user_id
             JOIN hairstyles h ON h.id = r.hairstyle_id
             WHERE r.worker_id = :worker_id AND r.appointment_date = :date AND r.status != 'cancelled'
             ORDER BY r.appointment_time ASC"
        );
        $stmt->execute([':worker_id' => $workerId, ':date' => $date]);
        return $stmt->fetchAll();
    }

    public function getDb(): \PDO
    {
        return $this->db;
    }

    public function getAllReservationsFiltered(?int $workerId = null, ?string $date = null, ?string $status = null): array
    {
        $sql = "SELECT " . self::BASE_COLUMNS . ",
                       u.username AS user_name, h.name AS hairstyle_name, h.price, h.image_url,
                       w.username AS worker_name
                FROM reservations r
                JOIN users u ON u.id = r.user_id
                JOIN hairstyles h ON h.id = r.hairstyle_id
                LEFT JOIN users w ON w.id = r.worker_id
                WHERE 1=1";
        $params = [];

        if ($workerId !== null) {
            $sql .= " AND r.worker_id = :worker_id";
            $params[':worker_id'] = $workerId;
        }
        if ($date !== null) {
            $sql .= " AND r.appointment_date = :date";
            $params[':date'] = $date;
        }
        if ($status !== null) {
            $sql .= " AND r.status = :status";
            $params[':status'] = $status;
        }

        $sql .= " ORDER BY r.reserved_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function countPending(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'pending'");
        return (int) $stmt->fetch()['total'];
    }

    public function countConfirmed(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) as total FROM reservations WHERE status = 'confirmed'");
        return (int) $stmt->fetch()['total'];
    }

    public function sumConfirmedRevenue(): float
    {
        $stmt = $this->db->query(
            "SELECT COALESCE(SUM(h.price), 0) as total
             FROM reservations r
             JOIN hairstyles h ON h.id = r.hairstyle_id
             WHERE r.status = 'confirmed'"
        );
        return (float) $stmt->fetch()['total'];
    }
}
