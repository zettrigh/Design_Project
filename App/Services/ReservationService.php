<?php

namespace App\Services;

use App\Models\ReservationModel;
use App\Models\HairstyleModel;

/**
 * App\Services\ReservationService
 *
 * Capa de servicio para la gestión de reservas (apartados).
 * Centraliza la lógica de creación, consulta y actualización de reservas.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja lógica de reservas.
 *   - DIP: Depende de ReservationModel y HairstyleModel (abstracciones).
 *   - DRY: Centraliza reglas de negocio (duplicidad, validación de existencia).
 *
 * @package App\Services
 */
class ReservationService
{
    /**
     * @var ReservationModel Modelo de acceso a datos de reservas.
     */
    private ReservationModel $reservationModel;

    /**
     * @var HairstyleModel Modelo de acceso a datos de peinados.
     */
    private HairstyleModel $hairstyleModel;

    /**
     * Estados válidos para una reserva.
     *
     * @var array<string>
     */
    private const VALID_STATUSES = ['pending', 'confirmed', 'cancelled'];

    /**
     * Constructor con inyección de dependencias.
     *
     * @param ReservationModel $reservationModel Modelo de reservas.
     * @param HairstyleModel   $hairstyleModel   Modelo de peinados.
     */
    public function __construct(ReservationModel $reservationModel, HairstyleModel $hairstyleModel)
    {
        $this->reservationModel = $reservationModel;
        $this->hairstyleModel   = $hairstyleModel;
    }

    /**
     * Crea una nueva reserva (apartado) para un cliente.
     *
     * Valida:
     *   1. Que el peinado exista y esté activo.
     *   2. Que el usuario no tenga ya una reserva activa para ese peinado.
     *
     * @param int $userId      ID del cliente.
     * @param int $hairstyleId ID del peinado a reservar.
     * @return array{success: bool, message: string, reservation_id?: int}
     */
    public function createReservation(int $userId, int $hairstyleId): array
    {
        if ($hairstyleId <= 0) {
            return ['success' => false, 'message' => 'Identificador de peinado inválido.'];
        }

        // Verificar existencia y estado del peinado
        $hairstyle = $this->hairstyleModel->getHairstyleById($hairstyleId);
        if (!$hairstyle || $hairstyle['status'] !== 'active') {
            return ['success' => false, 'message' => 'El peinado seleccionado no está disponible para venta.'];
        }

        // Verificar duplicidad de reserva
        if ($this->reservationModel->isAlreadyReserved($userId, $hairstyleId)) {
            return ['success' => false, 'message' => 'Ya tienes una reserva activa o confirmada para este peinado.'];
        }

        // Crear la reserva
        if ($this->reservationModel->createReservation($userId, $hairstyleId)) {
            return [
                'success'        => true,
                'message'        => '¡Has apartado tu peinado con éxito! Nuestro equipo revisará la reserva.',
                'reservation_id' => $this->reservationModel->getLastInsertId(),
            ];
        }

        return ['success' => false, 'message' => 'Ocurrió un error en el servidor al intentar registrar la reserva.'];
    }

    /**
     * Actualiza el estado de una reserva (para admin/worker).
     *
     * @param int    $reservationId ID de la reserva.
     * @param string $status        Nuevo estado.
     * @return array{success: bool, message: string}
     */
    public function updateReservationStatus(int $reservationId, string $status): array
    {
        if ($reservationId <= 0 || !in_array($status, self::VALID_STATUSES)) {
            return ['success' => false, 'message' => 'Datos de reserva incorrectos.'];
        }

        if ($this->reservationModel->updateReservationStatus($reservationId, $status)) {
            return ['success' => true, 'message' => 'El estado de la reserva se actualizó a: ' . ucfirst($status)];
        }

        return ['success' => false, 'message' => 'Error al actualizar el estado de la reserva en el servidor.'];
    }

    /**
     * Obtiene las reservas de un usuario específico (para client).
     *
     * @param int $userId ID del usuario.
     * @return array<int, array{id: int, status: string, reserved_at: string, hairstyle_name: string, price: float, image_url: string, description: string}>
     */
    public function getUserReservations(int $userId): array
    {
        return $this->reservationModel->getUserReservations($userId);
    }

    /**
     * Obtiene todas las reservas del sistema (para admin/worker).
     *
     * @return array<int, array{id: int, status: string, reserved_at: string, username: string, email: string, hairstyle_name: string, price: float}>
     */
    public function getAllReservations(): array
    {
        return $this->reservationModel->getAllReservations();
    }

    /**
     * Obtiene estadísticas del sistema para el dashboard administrativo.
     *
     * @return array{total_hairstyles: int, pending_reservations: int, confirmed_reservations: int, total_users: int, total_workers: int, estimated_revenue: float}
     */
    public function getSystemStats(): array
    {
        return $this->reservationModel->getSystemStats();
    }
}
