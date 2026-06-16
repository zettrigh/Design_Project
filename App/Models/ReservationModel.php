<?php

namespace App\Models;

/**
 * App\Models\ReservationModel
 *
 * Capa de acceso a datos para la entidad Reservation (Apartados).
 * Maneja todas las operaciones CRUD y consultas sobre la tabla `reservations`.
 *
 * @package App\Models
 */
class ReservationModel
{
    /**
     * Conexión PDO activa.
     *
     * @var \PDO
     */
    private \PDO $db;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $dbConnection Conexión PDO activa.
     */
    public function __construct(\PDO $dbConnection)
    {
        $this->db = $dbConnection;
    }

    /**
     * Crea una nueva reserva (apartado).
     *
     * @param int $userId      ID del cliente que reserva.
     * @param int $hairstyleId ID del peinado reservado.
     * @return bool True si la inserción fue exitosa.
     */
    public function createReservation(int $userId, int $hairstyleId): bool
    {
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

    /**
     * Retorna el último ID insertado en la tabla de reservas.
     *
     * @return int ID de la última inserción.
     */
    public function getLastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }

    /**
     * Verifica si un usuario ya tiene una reserva activa para un peinado.
     *
     * @param int $userId      ID del usuario.
     * @param int $hairstyleId ID del peinado.
     * @return bool True si ya existe una reserva no cancelada.
     */
    public function isAlreadyReserved(int $userId, int $hairstyleId): bool
    {
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

    /**
     * Obtiene las reservas de un usuario con detalles del peinado.
     *
     * @param int $userId ID del usuario.
     * @return array<int, array{id: int, status: string, reserved_at: string, hairstyle_name: string, price: float, image_url: string, description: string}>
     */
    public function getUserReservations(int $userId): array
    {
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

    /**
     * Obtiene todas las reservas del sistema (para panel admin/worker).
     *
     * @return array<int, array{id: int, status: string, reserved_at: string, username: string, email: string, hairstyle_name: string, price: float}>
     */
    public function getAllReservations(): array
    {
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

    /**
     * Obtiene una reserva por su ID.
     *
     * @param int $id ID de la reserva.
     * @return array|false Datos de la reserva o false si no existe.
     */
    public function getReservationById(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT r.*, h.name as hairstyle_name, h.price as hairstyle_price
                 FROM reservations r
                 JOIN hairstyles h ON r.hairstyle_id = h.id
                 WHERE r.id = :id LIMIT 1"
            );
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() === 1 ? $stmt->fetch() : false;
        } catch (\PDOException $e) {
            error_log('Error obteniendo reserva por ID: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualiza el estado de una reserva.
     *
     * @param int    $reservationId ID de la reserva.
     * @param string $status        Nuevo estado ('pending', 'confirmed', 'cancelled').
     * @return bool True si la actualización fue exitosa.
     */
    public function updateReservationStatus(int $reservationId, string $status): bool
    {
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

    /**
     * Obtiene estadísticas del sistema para el Dashboard administrativo.
     *
     * @return array{total_hairstyles: int, pending_reservations: int, confirmed_reservations: int, total_users: int, total_workers: int, estimated_revenue: float}
     */
    public function getSystemStats(): array
    {
        try {
            $stats = [];

            // Total peinados
            $stats['total_hairstyles'] = (int) $this->db->query("SELECT COUNT(*) FROM hairstyles")->fetchColumn();

            // Reservas pendientes
            $stats['pending_reservations'] = (int) $this->db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();

            // Reservas confirmadas
            $stats['confirmed_reservations'] = (int) $this->db->query("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'")->fetchColumn();

            // Clientes registrados (excluyendo admins y workers)
            $stats['total_users'] = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

            // Trabajadores registrados
            $stats['total_workers'] = (int) $this->db->query("SELECT COUNT(*) FROM users WHERE role = 'worker'")->fetchColumn();

            // Ingresos estimados (suma de peinados confirmados)
            $stats['estimated_revenue'] = (float) $this->db->query("
                SELECT COALESCE(SUM(h.price), 0)
                FROM reservations r
                JOIN hairstyles h ON r.hairstyle_id = h.id
                WHERE r.status = 'confirmed'
            ")->fetchColumn();

            return $stats;
        } catch (\PDOException $e) {
            error_log('Error obteniendo estadísticas: ' . $e->getMessage());
            return [
                'total_hairstyles'      => 0,
                'pending_reservations'  => 0,
                'confirmed_reservations'=> 0,
                'total_users'           => 0,
                'total_workers'         => 0,
                'estimated_revenue'     => 0.00,
            ];
        }
    }
}
