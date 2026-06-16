<?php

namespace App\Models;

/**
 * App\Models\PaymentModel
 *
 * Capa de acceso a datos para la entidad Payment (Pagos).
 * Registra y consulta transacciones de pago procesadas por la pasarela.
 *
 * Principios aplicados:
 *   - SRP: Solo accede a la tabla `payments`.
 *   - DIP: Recibe PDO por inyección de dependencias.
 *
 * @package App\Models
 */
class PaymentModel
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
     * Registra un nuevo pago en la base de datos.
     *
     * @param array{
     *     reservation_id: int,
     *     user_id: int,
     *     amount: float,
     *     currency: string,
     *     exchange_rate: float,
     *     amount_usd: float,
     *     payment_method: string,
     *     transaction_id: string,
     *     status: string
     * } $paymentData Datos del pago a registrar.
     * @return int|false ID del pago insertado o false en caso de error.
     */
    public function createPayment(array $paymentData): int|false
    {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO payments
                    (reservation_id, user_id, amount, currency, exchange_rate, amount_usd, payment_method, transaction_id, status)
                 VALUES
                    (:reservation_id, :user_id, :amount, :currency, :exchange_rate, :amount_usd, :payment_method, :transaction_id, :status)"
            );

            $stmt->execute([
                ':reservation_id' => $paymentData['reservation_id'],
                ':user_id'        => $paymentData['user_id'],
                ':amount'         => $paymentData['amount'],
                ':currency'       => $paymentData['currency'],
                ':exchange_rate'  => $paymentData['exchange_rate'],
                ':amount_usd'     => $paymentData['amount_usd'],
                ':payment_method' => $paymentData['payment_method'],
                ':transaction_id' => $paymentData['transaction_id'],
                ':status'         => $paymentData['status'],
            ]);

            return (int) $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log('Error creando pago: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene un pago por su ID.
     *
     * @param int $id ID del pago.
     * @return array|false Datos del pago o false si no existe.
     */
    public function getPaymentById(int $id): array|false
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM payments WHERE id = :id LIMIT 1"
            );
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() === 1 ? $stmt->fetch() : false;
        } catch (\PDOException $e) {
            error_log('Error obteniendo pago: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene todos los pagos de un usuario.
     *
     * @param int $userId ID del usuario.
     * @return array<int, array> Lista de pagos del usuario.
     */
    public function getPaymentsByUserId(int $userId): array
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT p.*, h.name as hairstyle_name
                 FROM payments p
                 JOIN reservations r ON p.reservation_id = r.id
                 JOIN hairstyles h ON r.hairstyle_id = h.id
                 WHERE p.user_id = :user_id
                 ORDER BY p.created_at DESC"
            );
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error obteniendo pagos del usuario: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene todos los pagos registrados (para panel admin).
     *
     * @return array<int, array> Lista de todos los pagos con datos del usuario y peinado.
     */
    public function getAllPayments(): array
    {
        try {
            $stmt = $this->db->query(
                "SELECT p.*, u.username, u.email, h.name as hairstyle_name
                 FROM payments p
                 JOIN users u ON p.user_id = u.id
                 JOIN reservations r ON p.reservation_id = r.id
                 JOIN hairstyles h ON r.hairstyle_id = h.id
                 ORDER BY p.created_at DESC"
            );
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error obteniendo todos los pagos: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Verifica si ya existe un pago para una reserva específica.
     *
     * @param int $reservationId ID de la reserva.
     * @return bool True si ya existe un pago completado.
     */
    public function hasPaymentForReservation(int $reservationId): bool
    {
        try {
            $stmt = $this->db->prepare(
                "SELECT id FROM payments
                 WHERE reservation_id = :reservation_id AND status = 'completed'
                 LIMIT 1"
            );
            $stmt->bindParam(':reservation_id', $reservationId, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log('Error verificando pago: ' . $e->getMessage());
            return false;
        }
    }
}
