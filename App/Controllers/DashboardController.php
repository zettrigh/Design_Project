<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\PaymentModel;
use App\Services\HairstyleService;
use App\Services\ReservationService;

/**
 * App\Controllers\DashboardController
 *
 * Controlador "thin" que enruta al dashboard correcto según el rol del usuario.
 * No contiene lógica de negocio; solo decide qué vista renderizar.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja el dispatch del dashboard.
 *   - DIP: Depende de servicios (abstracciones), no de modelos directamente.
 *   - Thin Controller: Solo lee la sesión y renderiza la vista apropiada.
 *
 * @package App\Controllers
 */
class DashboardController
{
    /**
     * @var \PDO Conexión PDO.
     */
    private \PDO $db;

    /**
     * @var HairstyleService Servicio de peinados.
     */
    private HairstyleService $hairstyleService;

    /**
     * @var ReservationService Servicio de reservas.
     */
    private ReservationService $reservationService;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param \PDO $dbConnection Conexión PDO activa.
     */
    public function __construct(\PDO $dbConnection)
    {
        $this->db = $dbConnection;
        $this->hairstyleService = new HairstyleService(new HairstyleModel($dbConnection));
        $this->reservationService = new ReservationService(
            new ReservationModel($dbConnection),
            new HairstyleModel($dbConnection)
        );
    }

    /**
     * Muestra el dashboard correspondiente al rol del usuario.
     *
     * - admin  → dashboard_admin (con estadísticas de sistema)
     * - worker → dashboard_worker (CRUD catálogo + reservas)
     * - client → dashboard_user (catálogo + reservas propias)
     *
     * @return void
     */
    public function index(): void
    {
        $role     = $_SESSION['role'] ?? 'client';
        $username = !empty($_SESSION['username']) ? $_SESSION['username'] : 'Usuario';
        $userId   = $_SESSION['user_id'] ?? 0;
        $baseUrl  = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');

        switch ($role) {
            case 'admin':
                $this->render('dashboard_admin', [
                    'username'     => $username,
                    'stats'        => $this->reservationService->getSystemStats(),
                    'hairstyles'   => $this->hairstyleService->getAllHairstyles(),
                    'reservations' => $this->reservationService->getAllReservations(),
                    'baseUrl'      => $baseUrl,
                ]);
                break;

            case 'worker':
                $this->render('dashboard_worker', [
                    'username'     => $username,
                    'hairstyles'   => $this->hairstyleService->getAllHairstyles(),
                    'reservations' => $this->reservationService->getAllReservations(),
                    'baseUrl'      => $baseUrl,
                ]);
                break;

            default: // client
                $this->render('dashboard_user', [
                    'username'     => $username,
                    'hairstyles'   => $this->hairstyleService->getActiveHairstyles(),
                    'reservations' => $this->reservationService->getUserReservations($userId),
                    'baseUrl'      => $baseUrl,
                ]);
                break;
        }
    }

    /**
     * Destruye la sesión y redirige al login.
     *
     * @param bool $timeout Indica si la sesión expiró por inactividad.
     * @return void
     */
    public function logout(bool $timeout = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params['path'], $params['domain'],
                    $params['secure'], $params['httponly']
                );
            }
            session_destroy();
        }

        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');
        header($timeout ? 'Location: ' . $baseUrl . '/login?timeout=1' : 'Location: ' . $baseUrl . '/login');
        exit;
    }

    /**
     * Renderiza una vista PHP pasándole variables al scope.
     *
     * @param string $view Nombre de la vista.
     * @param array  $data Variables para la vista.
     * @return void
     */
    private function render(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }
}
