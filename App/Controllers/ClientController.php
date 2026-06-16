<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\PaymentModel;
use App\Services\ReservationService;
use App\Services\PaymentService;

/**
 * App\Controllers\ClientController
 *
 * Controlador "thin" para las acciones del cliente:
 * visualizar catálogo, realizar reservas y procesar pagos.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja peticiones HTTP del cliente.
 *   - DIP: Depende de servicios inyectados.
 *   - Thin Controller: Solo valida inputs y delega al servicio.
 *
 * @package App\Controllers
 */
class ClientController
{
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
        $hairstyleModel   = new HairstyleModel($dbConnection);
        $reservationModel = new ReservationModel($dbConnection);
        $paymentModel     = new PaymentModel($dbConnection);

        $this->reservationService = new ReservationService($reservationModel, $hairstyleModel);
        $this->paymentService     = new PaymentService(
            $paymentModel,
            $reservationModel,
            $hairstyleModel
        );
    }

    /**
     * Crea una nueva reserva (apartado) para el cliente (POST AJAX).
     *
     * @return void
     */
    public function reserveHairstyle(): void
    {
        header('Content-Type: application/json');

        $userId      = $_SESSION['user_id'] ?? 0;
        $hairstyleId = intval($_POST['hairstyle_id'] ?? 0);

        $result = $this->reservationService->createReservation($userId, $hairstyleId);
        echo json_encode($result);
        exit;
    }

    /**
     * Procesa el pago de una reserva (POST AJAX).
     *
     * Flujo:
     *   1. Valida la reserva.
     *   2. Obtiene el precio en USD.
     *   3. Procesa el pago a través de la pasarela.
     *   4. Registra el pago y confirma la reserva.
     *
     * @return void
     */
    public function processPayment(): void
    {
        header('Content-Type: application/json');

        $reservationId   = intval($_POST['reservation_id'] ?? 0);
        $paymentMethodId = $_POST['payment_method_id'] ?? 'pm_card_visa';

        $result = $this->paymentService->processPayment($reservationId, $paymentMethodId);
        echo json_encode($result);
        exit;
    }

    /**
     * Obtiene el historial de pagos del cliente (POST AJAX).
     *
     * @return void
     */
    public function getPayments(): void
    {
        header('Content-Type: application/json');

        $userId   = $_SESSION['user_id'] ?? 0;
        $payments = $this->paymentService->getUserPayments($userId);

        echo json_encode(['success' => true, 'payments' => $payments]);
        exit;
    }
}
