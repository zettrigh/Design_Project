<?php

namespace App\Services;

use App\Models\ReservationModel;
use App\Models\HairstyleModel;
use Core\Result;

class ReservationService
{
    private ReservationModel $reservationModel;
    private HairstyleModel $hairstyleModel;

    public function __construct(ReservationModel $reservationModel, HairstyleModel $hairstyleModel)
    {
        $this->reservationModel = $reservationModel;
        $this->hairstyleModel   = $hairstyleModel;
    }

    public function getAllReservations(): array
    {
        return $this->reservationModel->getAllReservations();
    }

    public function getUserReservations(int $userId): array
    {
        return $this->reservationModel->getUserReservations($userId);
    }

    public function createReservation(int $userId, int $hairstyleId, ?int $workerId = null, ?string $appointmentDate = null, ?string $appointmentTime = null, ?string $endTime = null): Result
    {
        if (!in_array(strtoupper(date('l', strtotime($appointmentDate))), ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'])) {
            return Result::failure('Solo se pueden agendar citas de lunes a sábado.');
        }

        $hairstyle = $this->hairstyleModel->getHairstyleById($hairstyleId);
        if (!$hairstyle || $hairstyle['status'] !== 'active') {
            return Result::failure('El peinado seleccionado no está disponible.');
        }

        $reservationId = $this->reservationModel->createReservation(
            $userId, $hairstyleId, $workerId, $appointmentDate, $appointmentTime, $endTime
        );

        return Result::success(['reservation_id' => $reservationId], 'Reserva creada exitosamente.');
    }

    public function updateReservationStatus(int $id, string $status): Result
    {
        $validStatuses = ['pending', 'confirmed', 'cancelled'];
        if (!in_array($status, $validStatuses, true)) {
            return Result::failure('Estado de reserva inválido.');
        }

        $updated = $this->reservationModel->updateReservationStatus($id, $status);

        return $updated
            ? Result::success(null, 'Estado de la reserva actualizado.')
            : Result::failure('Error al actualizar el estado de la reserva.');
    }

    public function getSystemStats(): array
    {
        return [
            'total_users'           => (new \App\Models\UserModel($this->reservationModel->getDb()))->countByRole('client'),
            'total_workers'         => (new \App\Models\UserModel($this->reservationModel->getDb()))->countByRole('worker'),
            'total_hairstyles'      => count($this->hairstyleModel->getAllHairstyles()),
            'pending_reservations'  => $this->reservationModel->countPending(),
            'confirmed_reservations' => $this->reservationModel->countConfirmed(),
            'estimated_revenue'     => $this->reservationModel->sumConfirmedRevenue(),
        ];
    }
}
