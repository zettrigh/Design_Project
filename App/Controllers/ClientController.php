<?php

namespace App\Controllers;

use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\PaymentModel;
use App\Models\BusinessHoursModel;
use App\Models\WorkerScheduleModel;
use App\Services\ReservationService;
use App\Services\PaymentService;
use App\Services\ScheduleService;

class ClientController
{
    private ReservationService $reservationService;
    private PaymentService $paymentService;
    private ScheduleService $scheduleService;

    public function __construct(\PDO $dbConnection)
    {
        $hairstyleModel      = new HairstyleModel($dbConnection);
        $reservationModel    = new ReservationModel($dbConnection);
        $paymentModel        = new PaymentModel($dbConnection);
        $businessHoursModel  = new BusinessHoursModel($dbConnection);
        $workerScheduleModel = new WorkerScheduleModel($dbConnection);

        $this->reservationService = new ReservationService($reservationModel, $hairstyleModel);
        $this->paymentService     = new PaymentService($paymentModel, $reservationModel, $hairstyleModel);
        $this->scheduleService    = new ScheduleService($businessHoursModel, $workerScheduleModel, $reservationModel, $hairstyleModel);
    }

    public function getAvailableSlots(): void
    {
        header('Content-Type: application/json');

        $date        = $_POST['date']        ?? '';
        $hairstyleId = intval($_POST['hairstyle_id'] ?? 0);
        $workerId    = !empty($_POST['worker_id']) ? intval($_POST['worker_id']) : null;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido.']);
            exit;
        }
        if ($hairstyleId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Selecciona un peinado válido.']);
            exit;
        }

        echo json_encode($this->scheduleService->getAvailableSlots($date, $hairstyleId, $workerId));
        exit;
    }

    public function reserveHairstyle(): void
    {
        header('Content-Type: application/json');

        $userId      = $_SESSION['user_id'] ?? 0;
        $hairstyleId = intval($_POST['hairstyle_id'] ?? 0);
        $date        = $_POST['appointment_date'] ?? '';
        $time        = $_POST['appointment_time'] ?? '';
        $workerId    = !empty($_POST['worker_id']) ? intval($_POST['worker_id']) : null;

        if (empty($date) || empty($time)) {
            $result = $this->reservationService->createReservation($userId, $hairstyleId);
            echo json_encode($result->toArray());
            exit;
        }

        $result = $this->scheduleService->reserveWithSchedule($userId, $hairstyleId, $date, $time, $workerId);
        echo json_encode($result->toArray());
        exit;
    }

    public function processPayment(): void
    {
        header('Content-Type: application/json');

        $reservationId   = intval($_POST['reservation_id'] ?? 0);
        $paymentMethodId = $_POST['payment_method_id'] ?? 'pm_card_visa';

        echo json_encode($this->paymentService->processPayment($reservationId, $paymentMethodId)->toArray());
        exit;
    }

    public function getPayments(): void
    {
        header('Content-Type: application/json');

        $userId   = $_SESSION['user_id'] ?? 0;
        $payments = $this->paymentService->getUserPayments($userId);

        echo json_encode(['success' => true, 'payments' => $payments]);
        exit;
    }
}
