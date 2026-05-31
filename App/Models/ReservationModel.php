<?php

namespace App\Models;

// ─────────────────────────────────────────────────────────────
// App\Models\ReservationModel
// Capa de acceso a datos para la entidad Reservation (Apartados).
// ─────────────────────────────────────────────────────────────

class ReservationModel {

    private \PDO $db;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Crea un nuevo apartado
    public function createReservation(int $userId, int $hairstyleId): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO reservations (user_id, hairstyle_id, status)
                 VALUES (:user_id, :hairstyle_id, 'pending')"
            );
            $stmt->bindParam(':user_id',      $userId,      \PDO::PARAM_INT);
            $stmt->bindParam(':hairstyle_id', $hairstyleId, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error creando reservación: ' . $e->getMessage());
            return false;
        }
    }

    // Verifica si un usuario ya apartó un peinado activo (evitar duplicados de reserva pendientes o confirmadas)
    public function isAlreadyReserved(int $userId, int $hairstyleId): bool {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM reservations 
                 WHERE user_id = :user_id AND hairstyle_id = :hairstyle_id AND status != 'cancelled'
                 LIMIT 1"
            );
            $stmt->bindParam(':user_id',      $userId,      \PDO::PARAM_INT);
            $stmt->bindParam(':hairstyle_id', $hairstyleId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log('Error verificando reserva previa: ' . $e->getMessage());
            return false;
        }
    }

    // Obtiene las reservas de un usuario con detalles del peinado
    public function getUserReservations(int $userId): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.id, r.status, r.reserved_at, h.name as hairstyle_name, h.price, h.image_url, h.description
                 FROM reservations r
                 JOIN hairstyles h ON r.hairstyle_id = h.id
                 WHERE r.user_id = :user_id
                 ORDER BY r.id DESC"
            );
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error en getUserReservations: ' . $e->getMessage());
            return [];
        }
    }

    // Obtiene todas las reservas (para panel administrativo)
    public function getAllReservations(): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.id, r.status, r.reserved_at, u.username, u.email, h.name as hairstyle_name, h.price
                 FROM reservations r
                 JOIN users u ON r.user_id = u.id
                 JOIN hairstyles h ON r.hairstyle_id = h.id
                 ORDER BY r.id DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error en getAllReservations: ' . $e->getMessage());
            return [];
        }
    }

    // Actualiza el estado de un apartado
    public function updateReservationStatus(int $reservationId, string $status): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE reservations 
                 SET status = :status 
                 WHERE id = :id"
            );
            $stmt->bindParam(':id',     $reservationId, \PDO::PARAM_INT);
            $stmt->bindParam(':status', $status,        \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error actualizando estado de reserva: ' . $e->getMessage());
            return false;
        }
    }

    // Obtiene estadísticas del sistema para el Dashboard del Administrador
    public function getSystemStats(): array {
        try {
            $stats = [];
            
            // Total peinados
            $stats['total_hairstyles'] = $this->db->query("SELECT COUNT(*) FROM hairstyles")->fetchColumn();
            
            // Reservas pendientes
            $stats['pending_reservations'] = $this->db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
            
            // Reservas confirmadas
            $stats['confirmed_reservations'] = $this->db->query("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'")->fetchColumn();
            
            // Clientes registrados (excluyendo admins)
            $stats['total_users'] = $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
            
            // Ingresos estimados (suma de peinados confirmados)
            $stats['estimated_revenue'] = $this->db->query("
                SELECT COALESCE(SUM(h.price), 0) 
                FROM reservations r 
                JOIN hairstyles h ON r.hairstyle_id = h.id 
                WHERE r.status = 'confirmed'
            ")->fetchColumn();

            return $stats;
        } catch (\PDOException $e) {
            error_log('Error obteniendo estadísticas: ' . $e->getMessage());
            return [
                'total_hairstyles' => 0,
                'pending_reservations' => 0,
                'confirmed_reservations' => 0,
                'total_users' => 0,
                'estimated_revenue' => 0.00
            ];
        }
    }
}
