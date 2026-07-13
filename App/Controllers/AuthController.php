<?php

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct(\PDO $dbConnection)
    {
        $userModel = new \App\Models\UserModel($dbConnection);
        $this->authService = new AuthService($userModel);
    }

    public function login(): void
    {
        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: ' . $baseUrl . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $email    = $_POST['email']    ?? '';
            $password = $_POST['password'] ?? '';
            $result = $this->authService->login($email, $password);
            if ($result->isSuccess()) {
                $value = $result->getValue();
                echo json_encode([
                    'success' => true,
                    'redirect' => $value['redirect'] ?? $baseUrl . '/dashboard',
                    'message' => $result->getMessage(),
                ]);
            } else {
                echo json_encode($result->toArray());
            }
            exit;
        }

        $this->render('login');
    }

    public function register(): void
    {
        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');

        if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
            header('Location: ' . $baseUrl . '/dashboard');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            $username        = $_POST['username']        ?? '';
            $email           = $_POST['email']           ?? '';
            $password        = $_POST['password']        ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $result = $this->authService->register($username, $email, $password, $passwordConfirm);
            if ($result->isSuccess()) {
                $value = $result->getValue();
                echo json_encode([
                    'success'  => true,
                    'redirect' => $value['redirect'] ?? $baseUrl . '/login',
                    'message'  => $result->getMessage(),
                ]);
            } else {
                echo json_encode($result->toArray());
            }
            exit;
        }

        $this->render('register');
    }

    private function render(string $view, array $data = []): void
    {
        extract($data);
        require_once __DIR__ . '/../Views/' . $view . '.php';
    }
}
