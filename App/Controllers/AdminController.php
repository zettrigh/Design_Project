<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\PaymentModel;
use App\Services\UserService;
use App\Services\HairstyleService;
use App\Services\ReservationService;
use App\Services\PaymentService;

/**
 * App\Controllers\AdminController
 *
 * Controlador "thin" para las acciones exclusivas del administrador:
 * gestión de trabajadores, estadísticas y visión general del sistema.
 * Hereda las capacidades del worker (CRUD catálogo + reservas).
 *
 * Principios aplicados:
 *   - SRP: Solo maneja peticiones HTTP de administración.
 *   - DIP: Depende de servicios inyectados.
 *   - Thin Controller: Solo valida inputs y delega al servicio.
 *
 * @package App\Controllers
 */
class AdminController
{
    /**
     * @var UserService Servicio de gestión de usuarios/trabajadores.
     */
    private UserService $userService;

    /**
     * @var HairstyleService Servicio de peinados.
     */
    private HairstyleService $hairstyleService;

    /**
     * @var ReservationService Servicio de reservas.
     */
    private ReservationService $reservationService;

    /**
     * @var PaymentService Servicio de pagos.
     */
    private PaymentService $paymentService;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $dbConnection Conexión PDO activa.
     */
    public function __construct(\PDO $dbConnection)
    {
        $userModel        = new UserModel($dbConnection);
        $hairstyleModel   = new HairstyleModel($dbConnection);
        $reservationModel = new ReservationModel($dbConnection);
        $paymentModel     = new PaymentModel($dbConnection);

        $this->userService        = new UserService($userModel);
        $this->hairstyleService   = new HairstyleService($hairstyleModel);
        $this->reservationService = new ReservationService($reservationModel, $hairstyleModel);
        $this->paymentService     = new PaymentService(
            $paymentModel,
            $reservationModel,
            $hairstyleModel
        );
    }

    // ── Gestión de Trabajadores ──────────────────────────────

    /**
     * Lista todos los trabajadores (POST AJAX).
     *
     * @return void
     */
    public function listWorkers(): void
    {
        header('Content-Type: application/json');
        $workers = $this->userService->getAllWorkers();
        echo json_encode(['success' => true, 'workers' => $workers]);
        exit;
    }

    /**
     * Crea un nuevo trabajador (POST AJAX).
     *
     * @return void
     */
    public function storeWorker(): void
    {
        header('Content-Type: application/json');

        $username = $_POST['username'] ?? '';
        $email    = $_POST['email']    ?? '';
        $password = $_POST['password'] ?? '';

        $result = $this->userService->createWorker($username, $email, $password);
        echo json_encode($result);
        exit;
    }

    /**
     * Actualiza un trabajador existente (POST AJAX).
     *
     * @return void
     */
    public function updateWorker(): void
    {
        header('Content-Type: application/json');

        $id       = intval($_POST['id'] ?? 0);
        $username = $_POST['username'] ?? '';
        $email    = $_POST['email']    ?? '';

        $result = $this->userService->updateWorker($id, $username, $email);
        echo json_encode($result);
        exit;
    }

    /**
     * Elimina un trabajador (POST AJAX).
     *
     * @return void
     */
    public function deleteWorker(): void
    {
        header('Content-Type: application/json');

        $id = intval($_POST['id'] ?? 0);

        $result = $this->userService->deleteWorker($id);
        echo json_encode($result);
        exit;
    }

    /**
     * Resetea la contraseña de un trabajador (POST AJAX).
     *
     * @return void
     */
    public function resetWorkerPassword(): void
    {
        header('Content-Type: application/json');

        $id          = intval($_POST['id'] ?? 0);
        $newPassword = $_POST['new_password'] ?? '';

        $result = $this->userService->resetWorkerPassword($id, $newPassword);
        echo json_encode($result);
        exit;
    }

    // ── Hereda acciones de Worker (CRUD Peinados + Reservas) ──

    /**
     * Crea un nuevo peinado (POST AJAX). Heredado de Worker.
     *
     * @return void
     */
    public function storeHairstyle(): void
    {
        header('Content-Type: application/json');

        $name        = $_POST['name']        ?? '';
        $description = $_POST['description'] ?? '';
        $price       = floatval($_POST['price'] ?? 0.0);
        $imageUrl    = $_POST['image_url']    ?? '';
        $status      = $_POST['status']       ?? 'active';

        $result = $this->hairstyleService->createHairstyle($name, $description, $price, $imageUrl, $status);
        echo json_encode($result);
        exit;
    }

    /**
     * Actualiza un peinado (POST AJAX). Heredado de Worker.
     *
     * @return void
     */
    public function updateHairstyle(): void
    {
        header('Content-Type: application/json');

        $id          = intval($_POST['id'] ?? 0);
        $name        = $_POST['name']        ?? '';
        $description = $_POST['description'] ?? '';
        $price       = floatval($_POST['price'] ?? 0.0);
        $imageUrl    = $_POST['image_url']    ?? '';
        $status      = $_POST['status']       ?? 'active';

        $result = $this->hairstyleService->updateHairstyle($id, $name, $description, $price, $imageUrl, $status);
        echo json_encode($result);
        exit;
    }

    /**
     * Elimina un peinado (POST AJAX). Heredado de Worker.
     *
     * @return void
     */
    public function deleteHairstyle(): void
    {
        header('Content-Type: application/json');

        $id = intval($_POST['id'] ?? 0);

        $result = $this->hairstyleService->deleteHairstyle($id);
        echo json_encode($result);
        exit;
    }

    /**
     * Actualiza el estado de una reserva (POST AJAX). Heredado de Worker.
     *
     * @return void
     */
    public function updateReservation(): void
    {
        header('Content-Type: application/json');

        $id     = intval($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';

        $result = $this->reservationService->updateReservationStatus($id, $status);
        echo json_encode($result);
        exit;
    }

    // ── Pagos ────────────────────────────────────────────────

    /**
     * Obtiene el historial de pagos (POST AJAX).
     *
     * @return void
     */
    public function listPayments(): void
    {
        header('Content-Type: application/json');
        $payments = $this->paymentService->getAllPayments();
        echo json_encode(['success' => true, 'payments' => $payments]);
        exit;
    }
}
