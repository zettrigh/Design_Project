<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HairstyleModel;
use App\Models\ReservationModel;

/**
 * App\Controllers\WorkerController
 *
 * Controlador "thin" para las acciones operativas del trabajador:
 * CRUD del catálogo de peinados y gestión del estado de reservas.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja peticiones HTTP de operaciones worker.
 *   - DIP: Depende de HairstyleService y ReservationService.
 *   - Thin Controller: Solo valida inputs y delega al servicio.
 *
 * @package App\Controllers
 */
class WorkerController
{
    /**
     * @var \App\Services\HairstyleService Servicio de peinados.
     */
    private \App\Services\HairstyleService $hairstyleService;

    /**
     * @var \App\Services\ReservationService Servicio de reservas.
     */
    private \App\Services\ReservationService $reservationService;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $dbConnection Conexión PDO activa.
     */
    public function __construct(\PDO $dbConnection)
    {
        $this->hairstyleService = new \App\Services\HairstyleService(new HairstyleModel($dbConnection));
        $this->reservationService = new \App\Services\ReservationService(
            new ReservationModel($dbConnection),
            new HairstyleModel($dbConnection)
        );
    }

    /**
     * Crea un nuevo peinado en el catálogo (POST AJAX).
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
     * Actualiza un peinado existente en el catálogo (POST AJAX).
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
     * Elimina un peinado del catálogo (POST AJAX).
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
     * Actualiza el estado de una reserva (POST AJAX).
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
}
