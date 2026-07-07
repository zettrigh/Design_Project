<?php

namespace App\Controllers;

use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\PaymentModel;
use App\Models\BusinessHoursModel;
use App\Models\WorkerScheduleModel;
use App\Services\HairstyleService;
use App\Services\ReservationService;
use App\Services\ScheduleService;
use App\Services\PaymentService;
use App\Services\UserService;
use App\Models\UserModel;

class WorkerController
{
    protected HairstyleService $hairstyleService;
    protected ReservationService $reservationService;
    protected ScheduleService $scheduleService;
    protected ?PaymentService $paymentService = null;
    protected ?UserService $userService = null;

    public function __construct(\PDO $dbConnection)
    {
        $userModel           = new UserModel($dbConnection);
        $hairstyleModel      = new HairstyleModel($dbConnection);
        $reservationModel    = new ReservationModel($dbConnection);
        $paymentModel        = new PaymentModel($dbConnection);
        $businessHoursModel  = new BusinessHoursModel($dbConnection);
        $workerScheduleModel = new WorkerScheduleModel($dbConnection);

        $this->userService        = new UserService($userModel);
        $this->hairstyleService   = new HairstyleService($hairstyleModel);
        $this->reservationService = new ReservationService($reservationModel, $hairstyleModel);
        $this->paymentService     = new PaymentService($paymentModel, $reservationModel, $hairstyleModel);
        $this->scheduleService    = new ScheduleService($businessHoursModel, $workerScheduleModel, $reservationModel, $hairstyleModel);
    }

    public function storeHairstyle(): void
    {
        header('Content-Type: application/json');
        $name            = $_POST['name']            ?? '';
        $description     = $_POST['description']     ?? '';
        $price           = floatval($_POST['price'] ?? 0.0);
        $imageUrl        = $_POST['image_url']        ?? '';
        $status          = $_POST['status']           ?? 'active';
        $durationMinutes = intval($_POST['duration_minutes'] ?? 60);
        echo json_encode($this->hairstyleService->createHairstyle($name, $description, $price, $imageUrl, $status, $durationMinutes)->toArray());
        exit;
    }

    public function updateHairstyle(): void
    {
        header('Content-Type: application/json');
        $id              = intval($_POST['id'] ?? 0);
        $name            = $_POST['name']            ?? '';
        $description     = $_POST['description']     ?? '';
        $price           = floatval($_POST['price'] ?? 0.0);
        $imageUrl        = $_POST['image_url']        ?? '';
        $status          = $_POST['status']           ?? 'active';
        $durationMinutes = intval($_POST['duration_minutes'] ?? 60);
        echo json_encode($this->hairstyleService->updateHairstyle($id, $name, $description, $price, $imageUrl, $status, $durationMinutes)->toArray());
        exit;
    }

    public function deleteHairstyle(): void
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        echo json_encode($this->hairstyleService->deleteHairstyle($id)->toArray());
        exit;
    }

    public function updateReservation(): void
    {
        header('Content-Type: application/json');
        $id     = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        echo json_encode($this->reservationService->updateReservationStatus($id, $status)->toArray());
        exit;
    }

    public function getMySchedule(): void
    {
        header('Content-Type: application/json');
        $workerId = $_SESSION['user_id'] ?? 0;
        echo json_encode(['success' => true, 'schedule' => $this->scheduleService->getWorkerSchedule($workerId)]);
        exit;
    }

    public function updateMySchedule(): void
    {
        header('Content-Type: application/json');

        $workerId = $_SESSION['user_id'] ?? 0;

        $raw = $_POST['schedule'] ?? '';
        $scheduleData = [];
        if (!empty($raw) && is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $scheduleData = $decoded;
            }
        }

        if (empty($scheduleData)) {
            echo json_encode(['success' => false, 'message' => 'No se recibieron datos de horario.']);
            exit;
        }

        echo json_encode($this->scheduleService->updateWorkerSchedule($workerId, $scheduleData)->toArray());
        exit;
    }
}
