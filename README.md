# MiMundoTrenzas — Sistema MVC con RBAC, Pagos y Clean Architecture

Sistema web completo para gestión de peinados de trenzas, construido con **PHP 8.1+ puro** siguiendo el patrón **MVC**, arquitectura limpia (**Clean Code**, **SOLID**, **DRY**) y soporte completo de roles y permisos (**RBAC**).

---

## Tabla de Contenidos

- [Descripción General](#descripción-general)
- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Variables de Entorno](#variables-de-entorno)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Arquitectura del Sistema](#arquitectura-del-sistema)
  - [Flujo de una Petición](#flujo-de-una-petición)
  - [Capa de Enrutamiento y Middlewares](#capa-de-enrutamiento-y-middlewares)
  - [Gestión de Roles y Permisos (RBAC)](#gestión-de-roles-y-permisos-rbac)
  - [Capa de Servicios](#capa-de-servicios)
  - [Inyección de Dependencias](#inyección-de-dependencias)
- [Pasarela de Pagos y API de Tipo de Cambio](#pasarela-de-pagos-y-api-de-tipo-de-cambio)
- [Base de Datos](#base-de-datos)
- [Seguridad](#seguridad)
- [Cumplimiento de Principios de Diseño](#cumplimiento-de-principios-de-diseño)
- [Cómo Agregar un Nuevo Módulo](#cómo-agregar-un-nuevo-módulo)

---

## Descripción General

**MiMundoTrenzas** es una plataforma web para la gestión integral de un negocio de peinados de trenzas. Permite a los clientes explorar un catálogo, realizar reservas y pagar de forma segura, mientras que los trabajadores y administradores gestionan el catálogo, las reservas y los usuarios desde paneles dedicados.

### Características Principales

- **RBAC (Role-Based Access Control):** Tres roles — `admin`, `worker`, `client` — con jerarquía de permisos.
- **Pasarela de Pagos:** Integración con Stripe y cálculo dinámico de precios en USD vía API externa de tipo de cambio.
- **Clean Architecture:** Separación en capas (Router → Middleware → Controller → Service → Model → View).
- **Inyección de Dependencias:** Todos los componentes reciben sus dependencias por constructor.
- **Configuración por .env:** Variables de entorno centralizadas (BD, API keys, URLs).
- **Middlewares:** Protección de rutas por autenticación y rol.
- **Vistas Presentacionales:** Lógica condicional eliminada de las vistas; toda la información se prepara en los controladores/servicios.
- **Documentación PHPDoc:** Todo el código PHP está documentado con estándares PHPDoc.

---

## Requisitos

| Tecnología   | Versión mínima           |
|--------------|--------------------------|
| PHP          | 8.1+                     |
| MySQL        | 5.7+ / MariaDB 10.3+    |
| Apache       | 2.4+ con `mod_rewrite`   |
| XAMPP        | 8.x (incluye todo lo anterior) |

---

## Instalación

### 1. Clonar o copiar el proyecto

```bash
# Dentro de la carpeta de XAMPP
C:\xampp\htdocs\HomeWorks\Design_Project\
```

### 2. Configurar variables de entorno

```bash
# Copiar el archivo de ejemplo
cp .env.example .env

# Editar .env con tus credenciales reales
# - DB_HOST, DB_NAME, DB_USER, DB_PASS
# - STRIPE_SECRET_KEY, STRIPE_PUBLISHABLE_KEY
# - EXCHANGE_RATE_API_KEY
```

### 3. Iniciar servicios

Iniciar **Apache** y **MySQL** desde el panel de control de XAMPP.

### 4. Acceder al sistema

```
http://localhost/HomeWorks/Design_Project/login
```

> La base de datos, tablas y datos iniciales se crean **automáticamente** al acceder al sistema por primera vez.

### 5. Credenciales por defecto

| Rol        | Usuario        | Email                          | Contraseña  |
|------------|----------------|--------------------------------|-------------|
| Admin      | `admin`        | `admin@mimundotrenzas.com`     | `admin1234` |
| Worker     | `trabajadora1` | `trabajadora@mimundotrenzas.com`| `worker1234`|

---

## Variables de Entorno

Todas las configuraciones se manejan a través del archivo `.env` en la raíz del proyecto. **Nunca subir `.env` al repositorio.**

| Variable                  | Descripción                                  | Ejemplo                          |
|---------------------------|----------------------------------------------|----------------------------------|
| `APP_NAME`                | Nombre de la aplicación                      | `MiMundoTrenzas`                 |
| `APP_ENV`                 | Entorno (`development`/`production`)          | `development`                    |
| `BASE_URL`                | URL base del proyecto                         | `/HomeWorks/Design_Project`      |
| `DB_HOST`                 | Host de la base de datos                     | `localhost`                      |
| `DB_NAME`                 | Nombre de la base de datos                   | `auth_system_db`                 |
| `DB_USER`                 | Usuario de la base de datos                  | `root`                           |
| `DB_PASS`                 | Contraseña de la base de datos               | ``                               |
| `DB_CHARSET`              | Character set                                | `utf8mb4`                        |
| `BASE_CURRENCY`           | Moneda base de precios (ISO 4217)            | `MXN`                            |
| `STRIPE_SECRET_KEY`       | Clave secreta de Stripe                      | `sk_test_...`                    |
| `STRIPE_PUBLISHABLE_KEY`  | Clave pública de Stripe                      | `pk_test_...`                    |
| `EXCHANGE_RATE_API_KEY`   | Clave de ExchangeRate-API                    | `abc123...`                      |
| `EXCHANGE_RATE_API_URL`   | URL base de la API de tipo de cambio         | `https://v6.exchangerate-api.com/v6`|

---

## Estructura del Proyecto

```
Design_Project/
│
├── .env                          ← Variables de entorno (NO subir a git)
├── .env.example                  ← Plantilla de variables de entorno
├── .htaccess                     ← Reglas de reescritura de Apache
├── autoload.php                  ← Autoloader PSR-4
├── index.php                     ← Front Controller (punto de entrada único)
├── database.sql                  ← Script SQL de referencia
├── package.json                  ← Dependencias de Tailwind CSS
│
├── Config/                       ← Capa de configuración
│   ├── Environment.php           ← Lector de variables de entorno (.env)
│   └── database.php              ← Conexión PDO Singleton + migraciones
│
├── Core/                         ← Núcleo del framework
│   ├── Router.php                ← Motor de enrutamiento con middleware pipeline
│   ├── MiddlewareInterface.php   ← Contrato para middlewares
│   └── Middleware/               ← Middlewares concretos
│       ├── AuthMiddleware.php    ← Verificación de sesión
│       └── RoleMiddleware.php    ← Control de acceso por rol (con herencia)
│
├── App/                          ← Capa de aplicación (MVC + Services)
│   ├── Controllers/              ← Controladores "thin"
│   │   ├── AuthController.php    ← Login y registro
│   │   ├── DashboardController.php ← Routing de dashboards por rol
│   │   ├── WorkerController.php  ← CRUD peinados + reservas
│   │   ├── AdminController.php   ← Gestión trabajadores + hereda worker
│   │   └── ClientController.php  ← Reservas + pagos
│   │
│   ├── Services/                 ← Capa de servicios (lógica de negocio)
│   │   ├── AuthService.php       ← Lógica de autenticación
│   │   ├── UserService.php       ← Gestión de usuarios/trabajadores
│   │   ├── HairstyleService.php  ← Lógica del catálogo de peinados
│   │   ├── ReservationService.php← Lógica de reservas
│   │   ├── PaymentService.php    ← Procesamiento de pagos (Stripe)
│   │   └── ExchangeRateService.php ← Consumo de API de tipo de cambio
│   │
│   ├── Models/                   ← Capa de acceso a datos
│   │   ├── UserModel.php
│   │   ├── HairstyleModel.php
│   │   ├── ReservationModel.php
│   │   └── PaymentModel.php
│   │
│   └── Views/                    ← Plantillas HTML (puramente presentacionales)
│       ├── login.php
│       ├── register.php
│       ├── dashboard_admin.php
│       ├── dashboard_worker.php
│       ├── dashboard_user.php
│       └── partials/
│           └── js_modal_utils.php
│
├── routes/                       ← Definición centralizada de rutas
│   └── web.php
│
├── assets/                       ← JavaScript del cliente
│   └── js/
│       ├── login.js
│       ├── register.js
│       ├── dashboard_admin.js
│       ├── dashboard_worker.js
│       ├── dashboard_user.js
│       └── js_modal_utils.js
│
└── src/                          ← Assets estáticos
    ├── input.css
    ├── output.css
    └── img/
```

---

## Arquitectura del Sistema

### Flujo de una Petición

```
Navegador
   │
   ▼
.htaccess ─── ¿Archivo físico? ── Sí ──▶ Sirve CSS/JS/img directamente
   │
  No
   │
   ▼
index.php (Front Controller)
   │
   ├─ Environment::load()            ← Carga .env
   ├─ session_start()
   ├─ require autoload.php           ← Registra autoloader PSR-4
   ├─ Database::getInstance()        ← Conexión PDO (lee DB_* de .env)
   ├─ new Router($db)                ← Instancia el enrutador
   ├─ require routes/web.php         ← Registra rutas con middlewares
   └─ $router->dispatch()            ← Despacha la petición
          │
          ▼
     Router::dispatch()
          │
          ├─ Resuelve URI + método HTTP
          ├─ Busca ruta coincidente
          │     │
          │  No encontrada → 404 o redirect a login
          │     │
          │  Encontrada
          │     │
          ▼     ▼
     buildPipeline([middlewares], controller)
          │
          ├─ AuthMiddleware → ¿Sesión válida?
          │     │
          │     ├─ No → Redirect login / JSON error
          │     └─ Sí ↓
          │
          ├─ RoleMiddleware → ¿Rol autorizado?
          │     │
          │     ├─ No → Redirect dashboard / JSON error
          │     └─ Sí ↓
          │
          ▼
     Controller → Service → Model → BD
          │
          ▼
     Vista → HTML al navegador
```

### Capa de Enrutamiento y Middlewares

El `Core\Router` soporta **middlewares** que se ejecutan como una cadena (patrón *Chain of Responsibility*) antes de llegar al controlador:

```php
// Registrar una ruta con middlewares
$router->post('admin/workers/store', AdminController::class, 'storeWorker', [
    AuthMiddleware::class,        // 1. Verificar sesión
    RoleMiddleware::class . ':admin', // 2. Verificar rol admin
]);
```

**Middlewares disponibles:**

| Middleware          | Función                                                   |
|--------------------|-----------------------------------------------------------|
| `AuthMiddleware`   | Verifica sesión activa, validez en BD, timeout 30 min     |
| `RoleMiddleware`   | Verifica que el usuario tenga uno de los roles permitidos, soporta herencia |

### Gestión de Roles y Permisos (RBAC)

El sistema implementa **tres roles** con jerarquía de herencia:

```
┌─────────────────────────────────────────────────┐
│                    admin                         │
│  Hereda: worker + client                         │
│  Exclusivo: CRUD de trabajadores                  │
├─────────────────────────────────────────────────┤
│                   worker                         │
│  Hereda: client                                   │
│  Operativo: CRUD catálogo + gestionar reservas   │
├─────────────────────────────────────────────────┤
│                   client                         │
│  Visualización: catálogo + reservas + pagos       │
└─────────────────────────────────────────────────┘
```

**Herencia de roles (implementada en `RoleMiddleware`):**

```php
private const ROLE_HIERARCHY = [
    'admin'  => ['admin', 'worker', 'client'],
    'worker' => ['worker', 'client'],
    'client' => ['client'],
];
```

Esto significa que un `admin` puede acceder a rutas de `worker` y `client`, mientras que un `client` solo puede acceder a rutas de `client`.

### Capa de Servicios

Todos los controladores son **"thin controllers"** que delegan la lógica de negocio a servicios:

| Servicio               | Responsabilidad                                            |
|------------------------|-----------------------------------------------------------|
| `AuthService`          | Login, registro, gestión de sesiones                       |
| `UserService`          | CRUD de trabajadores, validación de negocio                 |
| `HairstyleService`     | CRUD del catálogo de peinados                               |
| `ReservationService`   | Creación, consulta y actualización de reservas              |
| `PaymentService`       | Procesamiento de pagos vía Stripe + cálculo de divisas     |
| `ExchangeRateService`  | Consumo de API externa para tipo de cambio (ExchangeRate-API)|

### Inyección de Dependencias

Todas las dependencias se inyectan a través de constructores (SOLID - DIP):

```php
// Ejemplo: AdminController recibe PDO y crea servicios
class AdminController {
    private UserService $userService;
    private HairstyleService $hairstyleService;

    public function __construct(\PDO $dbConnection) {
        $userModel = new UserModel($dbConnection);
        $this->userService = new UserService($userModel);
        // ...
    }
}
```

---

## Pasarela de Pagos y API de Tipo de Cambio

### Flujo de Pago Completo

```
1. Cliente selecciona peinado
         │
2. Crea reserva (status: pending)
         │
3. Hace clic en "Pagar Ahora"
         │
4. Sistema llama a ExchangeRate-API
   → Obtiene tasa MXN → USD
         │
5. Muestra precio convertido en modal
         │
6. Cliente confirma el pago
         │
7. Sistema llama a Stripe API
   → Crea PaymentIntent
   → Confirma con método de pago
         │
8. Registra pago en BD (tabla payments)
         │
9. Actualiza reserva a "confirmed"
         │
10. Muestra confirmación al cliente
```

### API de Tipo de Cambio (ExchangeRate-API)

El `ExchangeRateService` consume la API de [ExchangeRate-API](https://www.exchangerate-api.com/) para obtener tasas de cambio en tiempo real:

```php
// Ejemplo de uso
$exchangeService = new ExchangeRateService();
$result = $exchangeService->convert(1500, 'MXN', 'USD');
// Resultado: ['success' => true, 'converted_amount' => 87.00, 'rate' => 0.058]
```

**Características:**
- Caché en sesión (5 minutos) para evitar llamadas excesivas
- Tasas de respaldo hardcodeadas cuando la API no está disponible
- Configuración completa desde `.env`

### Integración con Stripe

El `PaymentService` integra [Stripe](https://stripe.com/) para procesar pagos:

- **Modo desarrollo:** Simula pagos cuando la API key no está configurada
- **Modo producción:** Llamadas reales a la API de Stripe vía cURL
- Los pagos se registran en la tabla `payments` con tasa de cambio y monto en USD

---

## Base de Datos

### Esquema Actualizado (v2.0)

**Base de datos:** `auth_system_db` | **Charset:** `utf8mb4_unicode_ci`

#### Tabla `users` (RBAC)

| Columna      | Tipo                                                   | Descripción              |
|-------------|--------------------------------------------------------|--------------------------|
| `id`        | `INT AUTO_INCREMENT PRIMARY KEY`                       | ID único                 |
| `username`  | `VARCHAR(50) NOT NULL UNIQUE`                          | Nombre de usuario        |
| `email`     | `VARCHAR(100) NOT NULL UNIQUE`                         | Correo electrónico       |
| `password`  | `VARCHAR(255) NOT NULL`                                | Hash bcrypt              |
| `role`      | `ENUM('admin', 'worker', 'client') DEFAULT 'client'`  | Rol del usuario          |
| `created_at`| `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`                  | Fecha de creación        |

#### Tabla `hairstyles`

| Columna      | Tipo                        | Descripción              |
|-------------|-----------------------------|--------------------------|
| `id`        | `INT AUTO_INCREMENT PRIMARY KEY` | ID único             |
| `name`      | `VARCHAR(100) NOT NULL`    | Nombre del peinado       |
| `description`| `TEXT NOT NULL`            | Descripción detallada    |
| `price`     | `DECIMAL(10,2) NOT NULL`   | Precio en moneda base    |
| `image_url` | `VARCHAR(255) NOT NULL`    | URL de imagen            |
| `status`    | `ENUM('active', 'inactive')`| Estado                  |

#### Tabla `reservations`

| Columna       | Tipo                        | Descripción              |
|--------------|-----------------------------|--------------------------|
| `id`         | `INT AUTO_INCREMENT PRIMARY KEY` | ID único             |
| `user_id`    | `INT NOT NULL (FK)`         | Cliente que reserva       |
| `hairstyle_id`| `INT NOT NULL (FK)`        | Peinado reservado        |
| `status`     | `ENUM('pending', 'confirmed', 'cancelled')` | Estado |
| `reserved_at`| `TIMESTAMP`                 | Fecha de reserva         |

#### Tabla `payments` (Nueva)

| Columna          | Tipo                        | Descripción              |
|-----------------|-----------------------------|--------------------------|
| `id`            | `INT AUTO_INCREMENT PRIMARY KEY` | ID único             |
| `reservation_id`| `INT NOT NULL (FK)`         | Reserva asociada         |
| `user_id`       | `INT NOT NULL (FK)`         | Usuario que pagó         |
| `amount`        | `DECIMAL(10,2)`             | Monto en moneda base     |
| `currency`      | `VARCHAR(3)`                | Código ISO de moneda     |
| `exchange_rate` | `DECIMAL(10,6)`             | Tasa de cambio aplicada  |
| `amount_usd`    | `DECIMAL(10,2)`             | Monto equivalente en USD |
| `payment_method`| `VARCHAR(50)`               | Método de pago           |
| `transaction_id`| `VARCHAR(255)`              | ID de transacción        |
| `status`        | `ENUM(...)`                 | Estado del pago          |

---

## Seguridad

| Medida                         | Implementación                                           | Ubicación              |
|-------------------------------|---------------------------------------------------------|------------------------|
| **Anti-SQL Injection**        | Prepared statements con `bindParam()` tipado            | Todos los Models       |
| **Anti-XSS**                  | `htmlspecialchars()` en toda salida dinámica             | Controladores + Vistas |
| **Hash de contraseñas**       | `password_hash()` con `PASSWORD_DEFAULT` (bcrypt)       | AuthService            |
| **Verificación de password**  | `password_verify()` (comparación segura contra timing)  | AuthService            |
| **Anti-Session Fixation**     | `session_regenerate_id(true)` al autenticarse           | AuthService            |
| **Timeout de sesión**         | Expiración automática después de 30 minutos             | AuthMiddleware         |
| **Protección de rutas**       | Middlewares de autenticación y autorización por rol      | Router + Middlewares    |
| **Variables de entorno**      | API keys y credenciales en `.env`, nunca en código       | Config\Environment     |
| **Bloqueo de .env**           | Apache deniega acceso directo a `.env`                   | .htaccess              |
| **Sanitización de URI**       | `FILTER_SANITIZE_URL`                                    | Router                 |
| **Error genérico en login**   | Mensaje único para credenciales inválidas                | AuthService            |

---

## Cumplimiento de Principios de Diseño

### SOLID

| Principio | Implementación |
|-----------|---------------|
| **S** - Single Responsibility | Cada clase tiene una única responsabilidad: Controllers manejan HTTP, Services manejan negocio, Models manejan datos, Middlewares manejan cross-cutting concerns. |
| **O** - Open/Closed | El sistema es extensible (nuevos middlewares, servicios, controladores) sin modificar código existente. `RoleMiddleware` soporta nuevos roles sin cambios. |
| **L** - Liskov Substitution | Los middlewares implementan `MiddlewareInterface` y son intercambiables en la cadena de pipeline. |
| **I** - Interface Segregation | `MiddlewareInterface` es una interfaz pequeña y enfocada con un solo método `handle()`. |
| **D** - Dependency Inversion | Todos los componentes dependen de abstractions (interfaces, modelos) inyectadas por constructor, no de implementaciones concretas. |

### DRY (Don't Repeat Yourself)

- Lógica de validación centralizada en Services.
- Middlewares reutilizables para auth y roles.
- Configuración centralizada en `.env` (eliminación de hardcoding).
- Helper methods compartidos (`sanitize()`, `render()`) en controladores.

### Clean Code

- Nombres descriptivos y consistentes.
- Métodos cortos y enfocados.
- Comentarios PHPDoc en todo el código.
- Separación clara de responsabilidades por capa.

---

## Cómo Agregar un Nuevo Módulo

### Paso 1 — Crear el Modelo (si accede a datos)

```php
// App/Models/ProductModel.php
<?php
namespace App\Models;

class ProductModel {
    private \PDO $db;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Métodos de acceso a datos...
}
```

### Paso 2 — Crear el Servicio

```php
// App/Services/ProductService.php
<?php
namespace App\Services;

class ProductService {
    private ProductModel $productModel;

    public function __construct(ProductModel $productModel) {
        $this->productModel = $productModel;
    }

    // Lógica de negocio...
}
```

### Paso 3 — Crear el Controlador (thin)

```php
// App/Controllers/ProductController.php
<?php
namespace App\Controllers;

class ProductController {
    private ProductService $productService;

    public function __construct(\PDO $dbConnection) {
        $this->productService = new ProductService(new ProductModel($dbConnection));
    }

    public function index(): void {
        $products = $this->productService->getAll();
        // Renderizar vista...
    }
}
```

### Paso 4 — Registrar Rutas con Middlewares

```php
// routes/web.php
use Core\Middleware\AuthMiddleware;
use Core\Middleware\RoleMiddleware;

$router->get('products', 'App\\Controllers\\ProductController', 'index', [
    AuthMiddleware::class,
]);
$router->post('products/store', 'App\\Controllers\\ProductController', 'store', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':worker', // Solo worker y admin
]);
```

> **No es necesario** editar `index.php` ni `autoload.php`. El autoloader PSR-4 carga las clases automáticamente.

---

## Tecnologías Utilizadas

| Tecnología      | Uso                                         |
|----------------|---------------------------------------------|
| PHP 8.1+       | Backend, lógica de servidor                  |
| MySQL/MariaDB  | Base de datos relacional                     |
| PDO            | Capa de acceso a datos (prepared statements) |
| Tailwind CSS 4 | Framework de utilidades CSS                  |
| Stripe API     | Pasarela de pagos                            |
| ExchangeRate-API| API de tipo de cambio en tiempo real        |
| Apache         | Servidor web con `mod_rewrite`               |

---

## Licencia

Proyecto académico desarrollado para la plataforma **MiMundoTrenzas**.
