<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\HairstyleModel;
use App\Models\ReservationModel;
use App\Models\ExchangeRateModel;
use App\Services\HairstyleService;
use App\Services\ReservationService;

class DashboardController
{
    private \PDO $db;
    private HairstyleService $hairstyleService;
    private ReservationService $reservationService;

    public function __construct(\PDO $dbConnection)
    {
        $this->db = $dbConnection;
        $this->hairstyleService = new HairstyleService(new HairstyleModel($dbConnection));
        $this->reservationService = new ReservationService(
            new ReservationModel($dbConnection),
            new HairstyleModel($dbConnection)
        );
    }

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

            default:
                $rateModel = new ExchangeRateModel($this->db);
                $vesRate = $rateModel->getRate('USD', 'VES');
                $this->render('dashboard_user', [
                    'username'     => $username,
                    'hairstyles'   => $this->hairstyleService->getActiveHairstyles(),
                    'reservations' => $this->reservationService->getUserReservations($userId),
                    'ves_rate'     => $vesRate ?? 0,
                    'baseUrl'      => $baseUrl,
                ]);
                break;
        }
    }

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

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }
}
