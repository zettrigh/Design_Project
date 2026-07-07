<?php

namespace App\Services;

use App\Models\PaymentModel;
use App\Models\ReservationModel;
use App\Models\HairstyleModel;
use Core\Result;

class PaymentService
{
    private PaymentModel $paymentModel;
    private ReservationModel $reservationModel;
    private HairstyleModel $hairstyleModel;

    public function __construct(PaymentModel $paymentModel, ReservationModel $reservationModel, HairstyleModel $hairstyleModel)
    {
        $this->paymentModel = $paymentModel;
        $this->reservationModel = $reservationModel;
        $this->hairstyleModel = $hairstyleModel;
    }

    public function processPayment(int $reservationId, int $userId, string $paymentMethodId): Result
    {
        $reservation = $this->reservationModel->getReservationById($reservationId);
        if (!$reservation) {
            return Result::failure('Reserva no encontrada.');
        }
        if ((int)$reservation['user_id'] !== $userId) {
            return Result::failure('La reserva no pertenece al usuario actual.');
        }
        if ($reservation['status'] !== 'pending') {
            return Result::failure('La reserva no está pendiente de pago.');
        }

        $hairstyle = $this->hairstyleModel->getHairstyleById((int)$reservation['hairstyle_id']);
        if (!$hairstyle) {
            return Result::failure('Peinado no encontrado.');
        }

        $amount = (float) $hairstyle['price'];
        $transactionId = 'txn_' . uniqid('', true);

        $db = $this->reservationModel->getDb();
        try {
            $db->beginTransaction();

            $paymentId = $this->paymentModel->createPayment(
                $reservationId,
                (int)$reservation['user_id'],
                $amount,
                'USD',
                1.0,
                $amount,
                $paymentMethodId,
                $transactionId
            );

            $updated = $this->reservationModel->updateReservationStatus($reservationId, 'confirmed');
            if (!$updated) {
                throw new \RuntimeException('Error al actualizar el estado de la reserva.');
            }

            $db->commit();

            return Result::success(
                ['payment_id' => $paymentId, 'transaction_id' => $transactionId],
                'Pago procesado exitosamente.'
            );
        } catch (\Throwable $e) {
            $db->rollBack();
            return Result::failure('Error al procesar el pago: ' . $e->getMessage());
        }
    }

    public function getUserPayments(int $userId): array
    {
        return $this->paymentModel->getPaymentsByUser($userId);
    }

    public function getAllPayments(): array
    {
        return $this->paymentModel->getAllPayments();
    }
}
