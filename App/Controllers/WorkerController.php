<?php

namespace App\Controllers;

use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\BusinessHoursModel;
use App\Models\WorkerScheduleModel;
use App\Services\HairstyleService;
use App\Services\ReservationService;
use App\Services\ScheduleService;

class WorkerController
{
    private HairstyleService $hairstyleService;
    private ReservationService $reservationService;
    private ScheduleService $scheduleService;

    public function __construct(\PDO $dbConnection)
    {
        $hairstyleModel      = new HairstyleModel($dbConnection);
        $reservationModel    = new ReservationModel($dbConnection);
        $businessHoursModel  = new BusinessHoursModel($dbConnection);
        $workerScheduleModel = new WorkerScheduleModel($dbConnection);

        $this->hairstyleService   = new HairstyleService($hairstyleModel);
        $this->reservationService = new ReservationService($reservationModel, $hairstyleModel);
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
