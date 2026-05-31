<?php

namespace App\Controllers;

use App\Models\HairstyleModel;
use App\Models\ReservationModel;

// ─────────────────────────────────────────────────────────────
// App\Controllers\DashboardController
// Lógica de negocio para los paneles de administración y usuario,
// incluyendo reservas AJAX y CRUD de peinados.
// ─────────────────────────────────────────────────────────────

class DashboardController {

    private \PDO $db;
    private HairstyleModel $hairstyleModel;
    private ReservationModel $reservationModel;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->hairstyleModel = new HairstyleModel($dbConnection);
        $this->reservationModel = new ReservationModel($dbConnection);
    }

    // ── Helpers ──────────────────────────────────────────────

    // Sanitiza inputs HTTP para prevenir XSS
    private function sanitize(string $data): string {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    // Verifica que el usuario tenga sesión activa
    private function enforceSession(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            header('Location: /HomeWorks/Design_Project/login');
            exit;
        }

        // Validar contra la base de datos
        $userId = intval($_SESSION['user_id']);
        $stmt = $this->db->prepare("SELECT id, role, username FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            $this->logout();
        }

        // Sincronizar rol y username en la sesión
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        // Timeout por inactividad (30 min)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            $this->logout(true);
        }
        $_SESSION['last_activity'] = time();
    }

    // Verifica que el usuario tenga sesión activa para peticiones AJAX
    private function enforceSessionAjax(): void {
        if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Sesión no iniciada o inválida. Por favor, inicia sesión.',
                'redirect' => '/HomeWorks/Design_Project/login'
            ]);
            exit;
        }

        $userId = intval($_SESSION['user_id']);
        $stmt = $this->db->prepare("SELECT id, role, username FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $userId, \PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch();

        if (!$user) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_destroy();
            }
            echo json_encode([
                'success' => false,
                'message' => 'El usuario no existe en el sistema. Sesión cerrada.',
                'redirect' => '/HomeWorks/Design_Project/login'
            ]);
            exit;
        }

        // Sincronizar rol y username
        $_SESSION['role'] = $user['role'];
        $_SESSION['username'] = $user['username'];

        // Timeout por inactividad (30 min)
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
            if (session_status() === PHP_SESSION_ACTIVE) {
                $_SESSION = [];
                session_destroy();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Tu sesión ha expirado por inactividad.',
                'redirect' => '/HomeWorks/Design_Project/login?timeout=1'
            ]);
            exit;
        }
        $_SESSION['last_activity'] = time();
    }

    // Verifica que el usuario tenga rol de administrador
    private function enforceAdmin(): void {
        $this->enforceSession();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            header('Location: /HomeWorks/Design_Project/dashboard');
            exit;
        }
    }

    // Verifica que el usuario tenga rol de administrador para peticiones AJAX
    private function enforceAdminAjax(): void {
        $this->enforceSessionAjax();
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
            echo json_encode([
                'success' => false,
                'message' => 'Acceso denegado. No tienes permisos de administrador.',
                'redirect' => '/HomeWorks/Design_Project/dashboard'
            ]);
            exit;
        }
    }

    // Destruye la sesión
    public function logout(bool $timeout = false): void {
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
        header($timeout ? 'Location: /HomeWorks/Design_Project/login?timeout=1' : 'Location: /HomeWorks/Design_Project/login');
        exit;
    }

    // Renderiza una vista pasando variables al scope
    private function render(string $view, array $data = []): void {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }

    // ── Acciones HTML ────────────────────────────────────────

    // GET /dashboard
    public function index(): void {
        $this->enforceSession();

        $role = $_SESSION['role'] ?? 'user';
        $username = !empty($_SESSION['username']) ? $_SESSION['username'] : 'Usuario';
        $userId = $_SESSION['user_id'] ?? 0;

        if ($role === 'admin') {
            // Cargar datos para el administrador
            $stats = $this->reservationModel->getSystemStats();
            $hairstyles = $this->hairstyleModel->getAllHairstyles();
            $reservations = $this->reservationModel->getAllReservations();

            $this->render('dashboard_admin', [
                'username' => $username,
                'stats' => $stats,
                'hairstyles' => $hairstyles,
                'reservations' => $reservations
            ]);
        } else {
            // Cargar datos para el cliente/usuario regular
            $hairstyles = $this->hairstyleModel->getAllActiveHairstyles();
            $reservations = $this->reservationModel->getUserReservations($userId);

            $this->render('dashboard_user', [
                'username' => $username,
                'hairstyles' => $hairstyles,
                'reservations' => $reservations
            ]);
        }
    }

    // ── Acciones de API (AJAX JSON) ──────────────────────────

    // POST /user/reserve
    public function reserveHairstyle(): void {
        header('Content-Type: application/json');
        
        $this->enforceSessionAjax();

        $userId = $_SESSION['user_id'];
        $hairstyleId = intval($_POST['hairstyle_id'] ?? 0);

        if ($hairstyleId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Identificador de peinado inválido.']);
            exit;
        }

        // Verificar existencia del peinado
        $hairstyle = $this->hairstyleModel->getHairstyleById($hairstyleId);
        if (!$hairstyle || $hairstyle['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'El peinado seleccionado no está disponible para venta.']);
            exit;
        }

        // Verificar duplicidad de reserva
        if ($this->reservationModel->isAlreadyReserved($userId, $hairstyleId)) {
            echo json_encode(['success' => false, 'message' => 'Ya tienes una reserva activa o confirmada para este peinado.']);
            exit;
        }

        // Crear la reservación
        if ($this->reservationModel->createReservation($userId, $hairstyleId)) {
            echo json_encode(['success' => true, 'message' => '¡Has apartado tu peinado con éxito! Nuestro equipo revisará la reserva.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Ocurrió un error en el servidor al intentar registrar la reserva.']);
            exit;
        }
    }

    // POST /admin/hairstyles/store
    public function adminStoreHairstyle(): void {
        header('Content-Type: application/json');
        $this->enforceAdminAjax();

        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0.0);
        $imageUrl = $this->sanitize($_POST['image_url'] ?? '');
        $status = $this->sanitize($_POST['status'] ?? 'active');

        if (empty($name) || empty($description) || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'El nombre, la descripción y un precio válido son obligatorios.']);
            exit;
        }

        if (empty($imageUrl)) {
            // Default placeholder if none provided
            $imageUrl = '/HomeWorks/Design_Project/src/img/braid_box.png';
        }

        if ($this->hairstyleModel->createHairstyle($name, $description, $price, $imageUrl, $status)) {
            echo json_encode(['success' => true, 'message' => 'Peinado agregado con éxito al catálogo.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al guardar el peinado en la base de datos.']);
            exit;
        }
    }

    // POST /admin/hairstyles/update
    public function adminUpdateHairstyle(): void {
        header('Content-Type: application/json');
        $this->enforceAdminAjax();

        $id = intval($_POST['id'] ?? 0);
        $name = $this->sanitize($_POST['name'] ?? '');
        $description = $this->sanitize($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0.0);
        $imageUrl = $this->sanitize($_POST['image_url'] ?? '');
        $status = $this->sanitize($_POST['status'] ?? 'active');

        if ($id <= 0 || empty($name) || empty($description) || $price <= 0) {
            echo json_encode(['success' => false, 'message' => 'Faltan campos obligatorios para actualizar el peinado.']);
            exit;
        }

        if (empty($imageUrl)) {
            $imageUrl = '/HomeWorks/Design_Project/src/img/braid_box.png';
        }

        if ($this->hairstyleModel->updateHairstyle($id, $name, $description, $price, $imageUrl, $status)) {
            echo json_encode(['success' => true, 'message' => 'Peinado actualizado correctamente.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'No se realizaron cambios o el peinado no existe.']);
            exit;
        }
    }

    // POST /admin/hairstyles/delete
    public function adminDeleteHairstyle(): void {
        header('Content-Type: application/json');
        $this->enforceAdminAjax();

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'ID de peinado inválido.']);
            exit;
        }

        if ($this->hairstyleModel->deleteHairstyle($id)) {
            echo json_encode(['success' => true, 'message' => 'Peinado eliminado del sistema de manera exitosa.']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'No se pudo eliminar el peinado. Podría estar vinculado a una reserva activa.']);
            exit;
        }
    }

    // POST /admin/reservations/update
    public function adminUpdateReservation(): void {
        header('Content-Type: application/json');
        $this->enforceAdminAjax();

        $id = intval($_POST['id'] ?? 0);
        $status = $this->sanitize($_POST['status'] ?? '');

        if ($id <= 0 || !in_array($status, ['pending', 'confirmed', 'cancelled'])) {
            echo json_encode(['success' => false, 'message' => 'Datos de reserva incorrectos.']);
            exit;
        }

        if ($this->reservationModel->updateReservationStatus($id, $status)) {
            echo json_encode(['success' => true, 'message' => 'El estado de la reserva se actualizó a: ' . ucfirst($status)]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al actualizar el estado de la reserva en el servidor.']);
            exit;
        }
    }
}
