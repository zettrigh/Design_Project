<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\PaymentModel;
use App\Models\BusinessHoursModel;
use App\Models\WorkerScheduleModel;
use App\Models\ExchangeRateModel;
use App\Services\UserService;
use App\Services\HairstyleService;
use App\Services\ReservationService;
use App\Services\PaymentService;
use App\Services\ScheduleService;
use App\Services\ExchangeRateService;

class AdminController
{
    private UserService $userService;
    private HairstyleService $hairstyleService;
    private ReservationService $reservationService;
    private PaymentService $paymentService;
    private ScheduleService $scheduleService;
    private ExchangeRateService $exchangeRateService;

    public function __construct(\PDO $dbConnection)
    {
        $userModel           = new UserModel($dbConnection);
        $hairstyleModel      = new HairstyleModel($dbConnection);
        $reservationModel    = new ReservationModel($dbConnection);
        $paymentModel        = new PaymentModel($dbConnection);
        $businessHoursModel  = new BusinessHoursModel($dbConnection);
        $workerScheduleModel = new WorkerScheduleModel($dbConnection);
        $exchangeRateModel   = new ExchangeRateModel($dbConnection);

        $this->userService        = new UserService($userModel);
        $this->hairstyleService   = new HairstyleService($hairstyleModel);
        $this->reservationService = new ReservationService($reservationModel, $hairstyleModel);
        $this->paymentService     = new PaymentService($paymentModel, $reservationModel, $hairstyleModel);
        $this->scheduleService    = new ScheduleService($businessHoursModel, $workerScheduleModel, $reservationModel, $hairstyleModel);
        $this->exchangeRateService = new ExchangeRateService();
        $this->exchangeRateService->setRateModel($exchangeRateModel);
    }

    // ── Gestión de Trabajadores ──

    public function listWorkers(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'workers' => $this->userService->getAllWorkers()]);
        exit;
    }

    public function storeWorker(): void
    {
        header('Content-Type: application/json');
        $username = $_POST['username'] ?? '';
        $email    = $_POST['email']    ?? '';
        $password = $_POST['password'] ?? '';
        echo json_encode($this->userService->createWorker($username, $email, $password)->toArray());
        exit;
    }

    public function updateWorker(): void
    {
        header('Content-Type: application/json');
        $id       = intval($_POST['id'] ?? 0);
        $username = $_POST['username'] ?? '';
        $email    = $_POST['email']    ?? '';
        echo json_encode($this->userService->updateWorker($id, $username, $email)->toArray());
        exit;
    }

    public function deleteWorker(): void
    {
        header('Content-Type: application/json');
        $id = intval($_POST['id'] ?? 0);
        echo json_encode($this->userService->deleteWorker($id)->toArray());
        exit;
    }

    public function resetWorkerPassword(): void
    {
        header('Content-Type: application/json');
        $id          = intval($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';
        echo json_encode($this->userService->resetWorkerPassword($id, $newPassword)->toArray());
        exit;
    }

    // ── CRUD Peinados ──

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

    // ── Horarios de Atención ──

    public function getBusinessHours(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'hours' => $this->scheduleService->getBusinessHours()]);
        exit;
    }

    public function updateBusinessHours(): void
    {
        header('Content-Type: application/json');

        $raw = $_POST['hours'] ?? '';
        $hoursData = [];
        if (!empty($raw) && is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $hoursData = $decoded;
            }
        }

        if (empty($hoursData)) {
            echo json_encode(['success' => false, 'message' => 'No se recibieron datos de horarios.']);
            exit;
        }

        echo json_encode($this->scheduleService->updateBusinessHours($hoursData)->toArray());
        exit;
    }

    public function getScheduleOverview(): void
    {
        header('Content-Type: application/json');

        $date = $_POST['date'] ?? date('Y-m-d');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        echo json_encode($this->scheduleService->getScheduleOverview($date));
        exit;
    }

    public function getWorkerSchedule(): void
    {
        header('Content-Type: application/json');

        $workerId = intval($_POST['worker_id'] ?? 0);
        if ($workerId <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de trabajador inválido.']);
            exit;
        }

        echo json_encode(['success' => true, 'schedule' => $this->scheduleService->getWorkerSchedule($workerId)]);
        exit;
    }

    // ── Pagos ──

    public function listPayments(): void
    {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'payments' => $this->paymentService->getAllPayments()]);
        exit;
    }

    // ── Tipo de Cambio ──

    public function getExchangeRate(): void
    {
        header('Content-Type: application/json');
        $result = $this->exchangeRateService->getManualRate('USD', 'VES');
        echo json_encode($result->toArray());
        exit;
    }

    public function setExchangeRate(): void
    {
        header('Content-Type: application/json');

        $rate = floatval($_POST['rate'] ?? 0);
        if ($rate <= 0) {
            echo json_encode(['success' => false, 'message' => 'La tasa debe ser mayor a cero.']);
            exit;
        }

        $adminId = intval($_SESSION['user_id'] ?? 0);
        echo json_encode($this->exchangeRateService->setManualRate('USD', 'VES', $rate, $adminId)->toArray());
        exit;
    }
}
