<?php

require_once __DIR__ . '/Core/Result.php';
require_once __DIR__ . '/Core/MiddlewareInterface.php';
require_once __DIR__ . '/Core/Router.php';
require_once __DIR__ . '/Core/Middleware/AuthMiddleware.php';
require_once __DIR__ . '/Core/Middleware/RoleMiddleware.php';

require_once __DIR__ . '/Config/Environment.php';
require_once __DIR__ . '/Config/Database.php';

require_once __DIR__ . '/App/Models/UserModel.php';
require_once __DIR__ . '/App/Models/HairstyleModel.php';
require_once __DIR__ . '/App/Models/ReservationModel.php';
require_once __DIR__ . '/App/Models/PaymentModel.php';
require_once __DIR__ . '/App/Models/BusinessHoursModel.php';
require_once __DIR__ . '/App/Models/WorkerScheduleModel.php';
require_once __DIR__ . '/App/Models/ExchangeRateModel.php';

require_once __DIR__ . '/App/Services/AuthService.php';
require_once __DIR__ . '/App/Services/HairstyleService.php';
require_once __DIR__ . '/App/Services/ReservationService.php';
require_once __DIR__ . '/App/Services/PaymentService.php';
require_once __DIR__ . '/App/Services/UserService.php';
require_once __DIR__ . '/App/Services/ScheduleService.php';
require_once __DIR__ . '/App/Services/ExchangeRateService.php';

require_once __DIR__ . '/App/Controllers/DashboardController.php';
require_once __DIR__ . '/App/Controllers/AuthController.php';
require_once __DIR__ . '/App/Controllers/ClientController.php';
require_once __DIR__ . '/App/Controllers/WorkerController.php';
require_once __DIR__ . '/App/Controllers/AdminController.php';
