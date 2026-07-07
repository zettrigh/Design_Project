# MiMundoTrenzas — Sistema MVC con RBAC, Pagos y Clean Architecture

Sistema web completo para gestión de peinados de trenzas, construido con **PHP 8.1+ puro** siguiendo el patrón **MVC**, arquitectura limpia (**Clean Code**, **SOLID**, **DRY**, **Result Pattern**) y soporte completo de roles y permisos (**RBAC**).

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
  - [Result Pattern](#result-pattern)
  - [Capa de Servicios](#capa-de-servicios)
  - [Inyección de Dependencias](#inyección-de-dependencias)
- [Pasarela de Pagos y Tipo de Cambio](#pasarela-de-pagos-y-tipo-de-cambio)
- [Base de Datos](#base-de-datos)
- [Seguridad](#seguridad)
- [Cumplimiento de Principios de Diseño](#cumplimiento-de-principios-de-diseño)
- [Cómo Agregar un Nuevo Módulo](#cómo-agregar-un-nuevo-módulo)
- [Tecnologías Utilizadas](#tecnologías-utilizadas)

---

## Descripción General

**MiMundoTrenzas** es una plataforma web para la gestión integral de un negocio de peinados de trenzas. Permite a los clientes explorar un catálogo, realizar reservas y pagar de forma segura, mientras que los trabajadores y administradores gestionan el catálogo, las reservas y los usuarios desde paneles dedicados.

### Características Principales

- **RBAC (Role-Based Access Control):** Tres roles — `admin`, `worker`, `client` — con jerarquía de permisos.
- **Pasarela de Pagos:** Integración con Stripe y cálculo dinámico de precios en USD + VES.
- **Clean Architecture:** Separación en capas (Router → Middleware → Controller → Service → Model → View).
- **Result Pattern:** `Core\Result` con `success()`/`failure()` factories elimina try-catch en modelos y estandariza respuestas JSON.
- **Inyección de Dependencias:** Todos los componentes reciben sus dependencias por constructor.
- **Configuración por .env:** Variables de entorno centralizadas (BD, API keys, URLs).
- **Middlewares:** Protección de rutas por autenticación y rol.
- **Vistas Presentacionales:** Lógica condicional eliminada de las vistas; toda la información se prepara en los controladores/servicios.
- **Gestión de Horarios:** Sistema completo de agenda con horarios de atención configurables por el administrador, disponibilidad individual por trabajador, generación dinámica de slots y detección de conflictos de horario.
- **Tipo de Cambio Manual:** Administrador puede fijar tasa USD→VES desde el panel; la tasa se usa en los catálogos de usuario.

---

## Requisitos

| Tecnología   | Versión mínima           |
|--------------|--------------------------|
| PHP          | 8.1+                     |
| MySQL        | 5.7+ / MariaDB 10.3+    |
| Apache       | 2.4+ con `mod_rewrite`   |
| XAMPP        | 8.x (incluye todo lo anterior) |
| Node.js      | 18+ (para Tailwind CSS)  |

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
```

### 3. Compilar Tailwind CSS

```bash
npx tailwindcss -i ./src/input.css -o ./src/output.css
```

### 4. Iniciar servicios

Iniciar **Apache** y **MySQL** desde el panel de control de XAMPP.

### 5. Acceder al sistema

```
http://localhost/HomeWorks/Design_Project/login
```

> La base de datos, tablas y datos iniciales se crean **automáticamente** al acceder al sistema por primera vez.

### 6. Credenciales por defecto

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
| `STRIPE_SECRET_KEY`       | Clave secreta de Stripe                      | `sk_test_...`                    |
| `STRIPE_PUBLISHABLE_KEY`  | Clave pública de Stripe                      | `pk_test_...`                    |

---

## Estructura del Proyecto

```
Design_Project/
│
├── .env                          ← Variables de entorno (NO subir a git)
├── .env.example                  ← Plantilla de variables de entorno
├── .htaccess                     ← Reglas de reescritura de Apache
├── bootstrap.php                 ← Carga explícita de todas las clases (reemplaza autoloader)
├── index.php                     ← Front Controller (punto de entrada único)
├── package.json                  ← Dependencias de Tailwind CSS
│
├── Config/                       ← Capa de configuración
│   ├── Environment.php           ← Lector de variables de entorno (.env)
│   └── database.php              ← Conexión PDO Singleton + migraciones automáticas
│
├── Core/                         ← Núcleo del framework
│   ├── Result.php                ← Result Pattern (success/failure, toArray)
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
│   │   ├── WorkerController.php  ← CRUD peinados + reservas + disponibilidad
│   │   ├── AdminController.php   ← Gestión trabajadores + horarios + tipo de cambio + hereda worker
│   │   └── ClientController.php  ← Reservas + pagos + selección de cita
│   │
│   ├── Services/                 ← Capa de servicios (lógica de negocio, retorna Result)
│   │   ├── AuthService.php       ← Lógica de autenticación
│   │   ├── UserService.php       ← Gestión de usuarios/trabajadores
│   │   ├── HairstyleService.php  ← CRUD del catálogo de peinados
│   │   ├── ReservationService.php← Lógica de reservas
│   │   ├── PaymentService.php    ← Procesamiento de pagos (Stripe)
│   │   ├── ExchangeRateService.php ← API de tipo de cambio + tasa manual
│   │   └── ScheduleService.php   ← Lógica de horarios, slots y disponibilidad
│   │
│   ├── Models/                   ← Capa de acceso a datos (PDO, sin try-catch)
│   │   ├── UserModel.php
│   │   ├── HairstyleModel.php
│   │   ├── ReservationModel.php
│   │   ├── PaymentModel.php
│   │   ├── BusinessHoursModel.php
│   │   ├── WorkerScheduleModel.php
│   │   └── ExchangeRateModel.php
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
   ├─ require bootstrap.php          ← Carga explícita de todas las clases
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
     Controller → Service (retorna Result) → Model → BD
          │
          ▼
     Vista → HTML al navegador
```

### Capa de Enrutamiento y Middlewares

El `Core\Router` soporta **middlewares** que se ejecutan como una cadena (patrón *Chain of Responsibility*) antes de llegar al controlador:

```php
$router->post('admin/workers/store', AdminController::class, 'storeWorker', [
    AuthMiddleware::class,
    RoleMiddleware::class . ':admin',
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
┌─────────────────────────────────────────────────────────┐
│                        admin                             │
│  Hereda: worker + client                                 │
│  Exclusivo: CRUD trabajadores, horarios, tipo de cambio  │
│  Agenda: vista general de citas del día                  │
├─────────────────────────────────────────────────────────┤
│                       worker                             │
│  Hereda: client                                          │
│  Operativo: CRUD catálogo, gestionar reservas            │
│  Agenda: gestionar su propia disponibilidad semanal      │
├─────────────────────────────────────────────────────────┤
│                       client                             │
│  Visualización: catálogo + reservas + pagos              │
│  Agenda: seleccionar fecha/hora al apartar peinado      │
└─────────────────────────────────────────────────────────┘
```

**Herencia de roles (implementada en `RoleMiddleware`):**

```php
private const ROLE_HIERARCHY = [
    'admin'  => ['admin', 'worker', 'client'],
    'worker' => ['worker', 'client'],
    'client' => ['client'],
];
```

### Result Pattern

Todas las operaciones de mutación en los **Services** retornan un objeto `Core\Result` en lugar de arrays genéricos:

```php
$result = $this->authService->login($email, $password);

if ($result->isSuccess()) {
    $userId = $result->getValue(); // mixed
    // respuesta exitosa
} else {
    $error = $result->getMessage(); // string
    // manejar error
}

// Para respuestas JSON:
echo json_encode($result->toArray());
// { success: true/false, data: mixed, message: string }
```

**Ventajas:**
- Elimina la necesidad de try-catch en los Models (las excepciones PDO burbujean hasta los Services, donde se envuelven en `Result::failure()`).
- Estandariza el formato de respuesta JSON (`{success, data, message}`).
- Los controladores siempre usan `toArray()` para serializar.

### Capa de Servicios

Todos los controladores son **"thin controllers"** que delegan la lógica de negocio a servicios. Los métodos de mutación retornan `Result`; los de consulta retornan `array|false` directamente.

| Servicio               | Responsabilidad                                            |
|------------------------|------------------------------------------------------------|
| `AuthService`          | Login, registro, gestión de sesiones                       |
| `UserService`          | CRUD de trabajadores, promoción/democión de roles          |
| `HairstyleService`     | CRUD del catálogo de peinados                              |
| `ReservationService`   | Creación, consulta y actualización de reservas             |
| `PaymentService`       | Procesamiento de pagos vía Stripe                          |
| `ExchangeRateService`  | API externa + tasa manual fijada por admin (USD→VES)       |
| `ScheduleService`      | Gestión de horarios, disponibilidad de trabajadores, slots, conflictos |

### Inyección de Dependencias

Todas las dependencias se inyectan a través de constructores (SOLID - DIP):

```php
class AdminController {
    public function __construct(\PDO $dbConnection) {
        $userModel = new UserModel($dbConnection);
        $this->userService = new UserService($userModel);
    }
}
```

---

## Pasarela de Pagos y Tipo de Cambio

### Flujo de Pago

```
1. Cliente selecciona peinado
2. Crea reserva (status: pending)
3. Hace clic en "Pagar Ahora"
4. Sistema muestra precio en USD + VES
5. Cliente confirma
6. Sistema procesa pago y registra en tabla payments
7. Actualiza reserva a "confirmed"
```

### Tipo de Cambio (USD → VES)

El `ExchangeRateService` combina tres fuentes en orden de prioridad:

1. **Tasa manual** (tabla `exchange_rates`) — fijada por el admin desde el panel "Tipo de Cambio"
2. **Caché en sesión** (1 hora) — evita llamadas excesivas a la API
3. **API externa** (ExchangeRate-API) — consulta en tiempo real con fallback hardcodeado

La tasa se muestra en el dashboard del usuario: etiqueta "1 USD = X.XX VES" y precios convertidos en el catálogo.

### Integración con Stripe

El `PaymentService` integra [Stripe](https://stripe.com/) para procesar pagos. En modo desarrollo simula pagos cuando la API key no está configurada.

---

## Gestión de Horarios (Agenda)

El sistema incluye un módulo completo de **gestión de horarios** con citas programadas, disponibilidad de trabajadores y detección automática de conflictos.

### Arquitectura del Módulo

```
┌─────────────────────────────────────────────────────────────┐
│                    ScheduleService                           │
│  - Gestión de horarios de atención (business hours)         │
│  - Gestión de disponibilidad de trabajadores                │
│  - Generación de slots disponibles (cada 30 min)            │
│  - Detección de conflictos de horario                       │
│  - Reserva de citas con fecha/hora/trabajador               │
│  - Vista general de agenda por fecha                        │
└─────────────────────────────────────────────────────────────┘
         │                              │
         ▼                              ▼
┌──────────────────┐      ┌──────────────────────────┐
│ BusinessHoursModel│      │ WorkerScheduleModel       │
│ - CRUD horarios   │      │ - CRUD disponibilidad     │
│ - Horas por día   │      │ - Búsqueda trabajadores   │
│  (0=Domingo..6=Sa)│      │   disponibles en fecha    │
└──────────────────┘      └──────────────────────────┘
```

### Roles y Permisos

| Rol      | Puede gestionar                            |
|----------|--------------------------------------------|
| `admin`  | Horarios de atención del negocio + ver agenda general + ver disponibilidad de cualquier trabajador |
| `worker` | Su propia disponibilidad semanal            |
| `client` | Seleccionar fecha/hora al apartar un peinado |

---

## Base de Datos

**Base de datos:** `auth_system_db` | **Charset:** `utf8mb4_unicode_ci`

Las migraciones se ejecutan automáticamente en `config/database.php` al iniciar la aplicación. El constructor detecta errores de tablespace corrupto (MySQL 1813/1932) y recrea las tablas automáticamente.

### Tabla `users` (RBAC)

| Columna      | Tipo                                                   | Descripción              |
|-------------|--------------------------------------------------------|--------------------------|
| `id`        | `INT AUTO_INCREMENT PRIMARY KEY`                       | ID único                 |
| `username`  | `VARCHAR(50) NOT NULL UNIQUE`                          | Nombre de usuario        |
| `email`     | `VARCHAR(100) NOT NULL UNIQUE`                         | Correo electrónico       |
| `password`  | `VARCHAR(255) NOT NULL`                                | Hash bcrypt              |
| `role`      | `ENUM('admin', 'worker', 'client') DEFAULT 'client'`  | Rol del usuario          |
| `created_at`| `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`                  | Fecha de creación        |

### Tabla `hairstyles`

| Columna           | Tipo                                  | Descripción                    |
|------------------|---------------------------------------|--------------------------------|
| `id`             | `INT AUTO_INCREMENT PRIMARY KEY`      | ID único                       |
| `name`           | `VARCHAR(100) NOT NULL`               | Nombre del peinado             |
| `description`    | `TEXT NOT NULL`                       | Descripción detallada          |
| `price`          | `DECIMAL(10,2) NOT NULL`              | Precio en USD                  |
| `duration_minutes`| `INT NOT NULL DEFAULT 60`            | Duración estimada del servicio |
| `image_url`      | `VARCHAR(255) NOT NULL`               | URL de imagen                  |
| `status`         | `ENUM('active', 'inactive')`          | Estado                         |
| `created_at`     | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP` | Fecha de creación              |

### Tabla `reservations`

| Columna           | Tipo                                              | Descripción                    |
|------------------|---------------------------------------------------|--------------------------------|
| `id`             | `INT AUTO_INCREMENT PRIMARY KEY`                  | ID único                       |
| `user_id`        | `INT NOT NULL (FK -> users.id)`                   | Cliente que reserva            |
| `hairstyle_id`   | `INT NOT NULL (FK -> hairstyles.id)`              | Peinado reservado              |
| `worker_id`      | `INT DEFAULT NULL (FK -> users.id)`               | Trabajador asignado            |
| `appointment_date`| `DATE DEFAULT NULL`                              | Fecha de la cita               |
| `appointment_time`| `TIME DEFAULT NULL`                             | Hora de inicio de la cita      |
| `end_time`       | `TIME DEFAULT NULL`                               | Hora de fin estimada           |
| `status`         | `ENUM('pending', 'confirmed', 'cancelled')`       | Estado                         |
| `reserved_at`    | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`             | Fecha de reserva               |

### Tabla `payments`

| Columna          | Tipo                                    | Descripción                   |
|-----------------|-----------------------------------------|-------------------------------|
| `id`            | `INT AUTO_INCREMENT PRIMARY KEY`        | ID único                      |
| `reservation_id`| `INT NOT NULL (FK -> reservations.id)`  | Reserva asociada              |
| `user_id`       | `INT NOT NULL (FK -> users.id)`         | Usuario que pagó              |
| `amount`        | `DECIMAL(10,2)`                         | Monto en moneda base          |
| `currency`      | `VARCHAR(3)`                            | Código ISO de moneda          |
| `exchange_rate` | `DECIMAL(10,6)`                         | Tasa de cambio aplicada       |
| `amount_usd`    | `DECIMAL(10,2)`                         | Monto equivalente en USD      |
| `payment_method`| `VARCHAR(50)`                           | Método de pago                |
| `transaction_id`| `VARCHAR(255)`                          | ID de transacción             |
| `status`        | `ENUM('pending','completed','failed','refunded')` | Estado del pago    |
| `created_at`    | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP`   | Fecha de creación             |

### Tabla `business_hours`

| Columna       | Tipo                                  | Descripción                              |
|--------------|---------------------------------------|------------------------------------------|
| `id`         | `INT AUTO_INCREMENT PRIMARY KEY`      | ID único                                 |
| `day_of_week`| `TINYINT NOT NULL`                    | Día de la semana (0=Domingo, ..., 6=Sábado) |
| `open_time`  | `TIME NOT NULL`                       | Hora de apertura                         |
| `close_time` | `TIME NOT NULL`                       | Hora de cierre                           |
| `is_active`  | `TINYINT(1) DEFAULT 1`               | 1=Abierto, 0=Cerrado                     |
| `UNIQUE KEY` | `uq_day (day_of_week)`               | Un solo registro por día                 |

### Tabla `worker_schedules`

| Columna       | Tipo                                      | Descripción                              |
|--------------|-------------------------------------------|------------------------------------------|
| `id`         | `INT AUTO_INCREMENT PRIMARY KEY`          | ID único                                 |
| `worker_id`  | `INT NOT NULL (FK -> users.id)`           | ID del trabajador                        |
| `day_of_week`| `TINYINT NOT NULL`                        | Día de la semana (0=Domingo, ..., 6=Sábado) |
| `start_time` | `TIME NOT NULL`                           | Hora de inicio de disponibilidad         |
| `end_time`   | `TIME NOT NULL`                           | Hora de fin de disponibilidad            |
| `is_active`  | `TINYINT(1) DEFAULT 1`                   | 1=Disponible, 0=No disponible            |
| `FOREIGN KEY`| `(worker_id) REFERENCES users(id) ON DELETE CASCADE` |
| `UNIQUE KEY` | `uq_worker_day (worker_id, day_of_week)` | Un solo registro por trabajador y día    |

### Tabla `exchange_rates`

| Columna       | Tipo                                    | Descripción                              |
|--------------|-----------------------------------------|------------------------------------------|
| `id`         | `INT AUTO_INCREMENT PRIMARY KEY`        | ID único                                 |
| `from_currency`| `VARCHAR(3) NOT NULL`                 | Moneda origen (ej. USD)                  |
| `to_currency`| `VARCHAR(3) NOT NULL`                   | Moneda destino (ej. VES)                 |
| `rate`       | `DECIMAL(14,6) NOT NULL`                | Tasa de cambio                           |
| `updated_by` | `INT DEFAULT NULL (FK -> users.id)`     | Admin que fijó la tasa                   |
| `updated_at` | `TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` |
| `UNIQUE KEY` | `uq_pair (from_currency, to_currency)` | Un solo registro por par de monedas      |

---

## Seguridad

### Protecciones Implementadas (OWASP Top 10)

| Medida                         | Implementación                                           | Ubicación              |
|-------------------------------|---------------------------------------------------------|------------------------|
| **Anti-CSRF**                 | Token de 32 bytes (random_bytes) en sesión; validación obligatoria en todos los POST; header o body field `_csrf_token` | `bootstrap.php` (helpers), `index.php` (validación), todas las vistas + JS |
| **Anti-SQL Injection**        | Prepared statements con bindParam() tipado en todos los queries | Todos los Models       |
| **Anti-XSS**                  | `htmlspecialchars()` (helper global `h()`) en toda salida dinámica | Vistas                 |
| **Content Security Policy**   | CSP restrictivo: scripts/style solo desde CDNs confiables, `'unsafe-inline'` para Tailwind, `form-action 'self'`, `frame-src 'none'` | `SecurityHeadersMiddleware` |
| **Anti-Clickjacking**         | `X-Frame-Options: DENY`                                  | `SecurityHeadersMiddleware` |
| **MIME sniffing prevention**  | `X-Content-Type-Options: nosniff`                        | `SecurityHeadersMiddleware` |
| **Referrer Policy**           | `strict-origin-when-cross-origin`                        | `SecurityHeadersMiddleware` |
| **Permissions Policy**        | Cámara, micrófono y geolocalización bloqueados           | `SecurityHeadersMiddleware` |
| **Strict Transport Security** | `max-age=31536000; includeSubDomains` (solo si HTTPS activo) | `SecurityHeadersMiddleware` |
| **Hash de contraseñas**       | `password_hash()` con `PASSWORD_DEFAULT` (bcrypt)        | AuthService, UserService |
| **Verificación de password**  | `password_verify()` (comparación segura contra timing)   | AuthService            |
| **Política de contraseñas**   | Mínimo 8 caracteres (antes 6)                            | AuthService, UserService |
| **Anti-Session Fixation**     | `session_regenerate_id(true)` al autenticarse            | AuthService            |
| **Cookies seguras**           | `httponly=true`, `samesite=Lax`, `secure` dinámico según HTTPS | `index.php` (session_set_cookie_params) |
| **Timeout de sesión**         | Expiración automática después de 30 minutos              | AuthMiddleware         |
| **Protección de rutas**       | Middlewares de autenticación y autorización por rol (RBAC) | Router + Middlewares   |
| **Variables de entorno**      | API keys y credenciales en `.env`, nunca en código       | Config\Environment     |
| **Bloqueo de .env**           | Apache deniega acceso directo a `.env`                   | .htaccess              |
| **Error genérico en login**   | Mensaje único para credenciales inválidas                | AuthService            |
| **Información oculta**        | Mensajes de error internos no revelan nombres de clase/método | `Router::callAction` |
| **SSL verification**          | `verify_peer` y `verify_peer_name` activos en llamadas API externas | ExchangeRateService |
| **Transacciones atómicas**    | Pago + actualización de reserva en `BEGIN/COMMIT/ROLLBACK` | PaymentService |
| **Autorización por propietario** | Verificación de que `user_id` coincide con la reserva antes del pago | PaymentService |
| **URL validation**            | Helper `validate_url()` con filtro de esquemas permitidos | `bootstrap.php` |
| **CSRF en JS**                | Tokens incluidos en todos los fetch POST vía `window.CSRF_TOKEN` | dashboard_*.js |

---

## Mejoras de Clean Code Aplicadas

### Refactorización de Controladores
- **`AdminController`** ahora extiende a **`WorkerController`**, eliminando ~120 líneas de código duplicado (métodos `storeHairstyle`, `updateHairstyle`, `deleteHairstyle`, `updateReservation`). Las rutas de admin heredan funcionalidad vía herencia limpia.
- Servicios compartidos declarados como `protected` en `WorkerController` para acceso desde `AdminController`.

### Eliminación de Código Muerto

| Archivo | Métodos eliminados | Razón |
|---------|--------------------|-------|
| `UserModel` | `updateRole()` | Nunca llamado |
| `UserModel` | rename `getAllAdmins()` → `getWorkersAndAdmins()` | Nombre engañoso (retornaba admin+worker) |
| `ReservationModel` | `assignWorker()` | Nunca llamado |
| `ReservationModel` | `getReservationsByWorkerAndDate()` | Nunca llamado |
| `BusinessHoursModel` | `getAllHours()`, `upsertHours()` | Wrappers inline eliminados |
| `WorkerScheduleModel` | `getScheduleByWorker()`, `upsertSchedule()` | Wrappers inline eliminados |
| `UserService` | `getAllUsers()`, `countByRole()`, `promoteToWorker()`, `demoteToClient()`, `updateProfile()`, `getUsersByRole()`, `deleteUser()` | Nunca llamados |
| `PaymentService` | `createPayment()`, `getPaymentById()`, `updatePaymentStatus()` | Nunca llamados |
| `HairstyleService` | `getPriceUSD()` | Nunca llamado |
| `ScheduleService` | `getDayName()`, `getAllDayNames()`, `updateReservationSchedule()` | Nunca llamados |
| `ExchangeRateService` | `getAllRates()` | Nunca llamado |

### Helpers Globales (`bootstrap.php`)
- **`h()`** — alias de `htmlspecialchars()` con `ENT_QUOTES | ENT_SUBSTITUTE`, UTF-8.
- **`csrf_token()`** — genera/retorna token de 32 bytes almacenado en sesión.
- **`verify_csrf_token()`** — comparación segura con `hash_equals()`.
- **`validate_url()`** — valida URL con esquemas http/https permitidos.

### Seguridad en Sesiones
- `session_set_cookie_params()` configurado **antes** de `session_start()` en `index.php`.
- Cookies con `httponly`, `samesite=Lax`, `secure` dinámico.

---

## Cumplimiento de Principios de Diseño

### SOLID

| Principio | Implementación |
|-----------|---------------|
| **S** - Single Responsibility | Cada clase tiene una única responsabilidad: Controllers manejan HTTP, Services manejan negocio, Models manejan datos, Middlewares manejan cross-cutting concerns. |
| **O** - Open/Closed | El sistema es extensible (nuevos middlewares, servicios, controladores) sin modificar código existente. |
| **L** - Liskov Substitution | Los middlewares implementan `MiddlewareInterface` y son intercambiables en la cadena de pipeline. |
| **I** - Interface Segregation | `MiddlewareInterface` es una interfaz pequeña con un solo método `handle()`. |
| **D** - Dependency Inversion | Todos los componentes dependen de abstracciones inyectadas por constructor, no de implementaciones concretas. |

### Result Pattern

- `Core\Result` con `success()`/`failure()` factories reemplaza los arrays genéricos `['success' => true/false]`.
- Los Models ya no tienen try-catch; las excepciones PDO burbujean hasta el Service, donde se envuelven en `Result::failure()`.
- Los Controladores serializan con `toArray()` para respuestas JSON consistentes.

### Clean Code

- Nombres descriptivos y consistentes.
- Métodos cortos y enfocados.
- Sin docblocks redundantes (el tipado PHP 8.1+ es auto-documentado).
- Separación clara de responsabilidades por capa.
- `bootstrap.php` con carga explícita en lugar de autoloader dinámico.

---

## Cómo Agregar un Nuevo Módulo

### Paso 1 — Crear el Modelo

```php
// App/Models/ProductModel.php
<?php
namespace App\Models;

class ProductModel {
    private \PDO $db;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Métodos de acceso a datos con prepared statements...
}
```

### Paso 2 — Crear el Servicio (con Result Pattern)

```php
// App/Services/ProductService.php
<?php
namespace App\Services;

use App\Models\ProductModel;
use Core\Result;

class ProductService {
    private ProductModel $productModel;

    public function __construct(ProductModel $productModel) {
        $this->productModel = $productModel;
    }

    public function createProduct(string $name, float $price): Result {
        if (empty(trim($name))) {
            return Result::failure('El nombre es obligatorio.');
        }
        // ... lógica de negocio ...
        return Result::success(['id' => $newId], 'Producto creado.');
    }

    public function getAllProducts(): array {
        return $this->productModel->getAll();
    }
}
```

### Paso 3 — Crear el Controlador

```php
// App/Controllers/ProductController.php
<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Services\ProductService;

class ProductController {
    private ProductService $productService;

    public function __construct(\PDO $dbConnection) {
        $this->productService = new ProductService(new ProductModel($dbConnection));
    }

    public function store(): void {
        header('Content-Type: application/json');
        $result = $this->productService->createProduct(
            $_POST['name'] ?? '',
            floatval($_POST['price'] ?? 0)
        );
        echo json_encode($result->toArray());
        exit;
    }
}
```

### Paso 4 — Registrar en `bootstrap.php`

```php
// bootstrap.php — agregar después de los modelos existentes
require_once __DIR__ . '/App/Models/ProductModel.php';
require_once __DIR__ . '/App/Services/ProductService.php';
require_once __DIR__ . '/App/Controllers/ProductController.php';
```

### Paso 5 — Registrar Rutas con Middlewares

```php
// routes/web.php
$router->get('products', 'App\\Controllers\\ProductController', 'index', [
    Core\Middleware\AuthMiddleware::class,
]);
$router->post('products/store', 'App\\Controllers\\ProductController', 'store', [
    Core\Middleware\AuthMiddleware::class,
    Core\Middleware\RoleMiddleware::class . ':worker',
]);
```

---

## Tecnologías Utilizadas

| Tecnología      | Uso                                         |
|----------------|---------------------------------------------|
| PHP 8.1+       | Backend, lógica de servidor                  |
| MySQL/MariaDB  | Base de datos relacional                     |
| PDO            | Capa de acceso a datos (prepared statements) |
| Tailwind CSS 4 | Framework de utilidades CSS                  |
| Stripe API     | Pasarela de pagos                            |
| Apache         | Servidor web con `mod_rewrite`               |

---

---

## Changelog

### 2026-07-06 — Refactorización OWASP + Clean Code

#### Seguridad (OWASP Top 10)
- **CSRF Protection:** Token de 32 bytes en todas las formas y peticiones AJAX. Validación global en `index.php` para todo POST.
- **Content Security Policy:** Cabecera CSP restrictiva agregada vía `SecurityHeadersMiddleware`.
- **Anti-Clickjacking + MIME sniffing:** `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`.
- **Cookies seguras:** `session_set_cookie_params()` con `httponly`, `samesite=Lax`, `secure` dinámico.
- **SSL verification:** `verify_peer` y `verify_peer_name` habilitados en llamadas a API externa.
- **Transacciones atómicas:** `PaymentService::processPayment` ahora usa `BEGIN/COMMIT/ROLLBACK`.
- **Autorización por propietario:** Verificación de que la reserva pertenece al usuario antes del pago.
- **Política de contraseñas:** Mínimo 8 caracteres (antes 6).
- **Información oculta:** Mensajes de error internos no revelan nombres de clase/método en `Router`.

#### Clean Code
- **`AdminController`** ahora extiende a `WorkerController`, eliminando ~120 líneas duplicadas.
- **Código muerto eliminado:** 22 métodos no utilizados removidos de Models y Services.
- **Helpers globales:** `h()`, `csrf_token()`, `verify_csrf_token()`, `validate_url()` en `bootstrap.php`.
- **Nombres corregidos:** `UserModel::getAllAdmins()` renombrado a `getWorkersAndAdmins()`.

---

## Licencia

Proyecto académico desarrollado para la plataforma **MiMundoTrenzas**.
