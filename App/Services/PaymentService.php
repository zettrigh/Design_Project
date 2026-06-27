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

    public function processPayment(int $reservationId, string $paymentMethodId): Result
    {
        $reservation = $this->reservationModel->getReservationById($reservationId);
        if (!$reservation) {
            return Result::failure('Reserva no encontrada.');
        }
        if ($reservation['status'] !== 'pending') {
            return Result::failure('La reserva no está pendiente de pago.');
        }

        $hairstyle = $this->hairstyleModel->getHairstyleById((int)$reservation['hairstyle_id']);
        if (!$hairstyle) {
            return Result::failure('Peinado no encontrado.');
        }

        $amount = (float) $hairstyle['price'];
        $transactionId = 'txn_' . uniqid();

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

        $this->reservationModel->updateReservationStatus($reservationId, 'confirmed');

        return Result::success(
            ['payment_id' => $paymentId, 'transaction_id' => $transactionId],
            'Pago procesado exitosamente.'
        );
    }

    public function createPayment(int $reservationId, int $userId, float $amount, string $currency, float $exchangeRate, float $amountUsd, string $paymentMethod, string $transactionId): Result
    {
        if ($amount <= 0) {
            return Result::failure('El monto debe ser mayor a cero.');
        }
        if (empty(trim($paymentMethod))) {
            return Result::failure('El método de pago es obligatorio.');
        }
        if (empty(trim($transactionId))) {
            return Result::failure('El ID de transacción es obligatorio.');
        }

        $paymentId = $this->paymentModel->createPayment(
            $reservationId, $userId, $amount, $currency, $exchangeRate, $amountUsd, $paymentMethod, $transactionId
        );

        return Result::success(['payment_id' => $paymentId], 'Pago registrado exitosamente.');
    }

    public function getPaymentById(int $id): array|false
    {
        return $this->paymentModel->getPaymentById($id);
    }

    public function getUserPayments(int $userId): array
    {
        return $this->paymentModel->getPaymentsByUser($userId);
    }

    public function getAllPayments(): array
    {
        return $this->paymentModel->getAllPayments();
    }

    public function updatePaymentStatus(int $id, string $status): Result
    {
        $validStatuses = ['pending', 'completed', 'failed', 'refunded'];
        if (!in_array($status, $validStatuses, true)) {
            return Result::failure('Estado de pago inválido.');
        }

        $updated = $this->paymentModel->updatePaymentStatus($id, $status);
        return $updated
            ? Result::success(null, 'Estado del pago actualizado.')
            : Result::failure('Error al actualizar el estado del pago.');
    }
}
