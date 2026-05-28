# MiMundoTrenzas — Sistema de Autenticación MVC

Sistema web de autenticación de usuarios construido con PHP puro siguiendo el patrón **MVC (Modelo-Vista-Controlador)**, enrutamiento centralizado y carga automática de clases mediante un **autoloader PSR-4**.

---

## Tabla de Contenidos

- [Requisitos](#requisitos)
- [Instalación](#instalación)
- [Estructura del Proyecto](#estructura-del-proyecto)
- [Ciclo de Vida de una Petición](#ciclo-de-vida-de-una-petición)
- [Módulos del Sistema](#módulos-del-sistema)
  - [1. Autoloader PSR-4](#1-autoloader-psr-4-autoloadphp)
  - [2. Front Controller](#2-front-controller-indexphp)
  - [3. Configuración de Base de Datos](#3-configuración-de-base-de-datos-configdatabasephp)
  - [4. Router (Enrutador)](#4-router-enrutador-corerouterphp)
  - [5. Rutas](#5-rutas-routeswebphp)
  - [6. Controladores](#6-controladores-appcontrollers)
  - [7. Modelos](#7-modelos-appmodels)
  - [8. Vistas](#8-vistas-appviews)
- [Base de Datos](#base-de-datos)
- [Seguridad](#seguridad)
- [Cómo Agregar un Nuevo Módulo](#cómo-agregar-un-nuevo-módulo)

---

## Requisitos

| Tecnología | Versión mínima |
|------------|---------------|
| PHP        | 8.1+          |
| MySQL      | 5.7+ / MariaDB 10.3+ |
| Apache     | 2.4+ con `mod_rewrite` habilitado |
| XAMPP      | 8.x (incluye todo lo anterior) |

---

## Instalación

1. **Clonar o copiar** el proyecto dentro de la carpeta de XAMPP:

   ```
   C:\xampp\htdocs\HomeWorks\Design_Project\
   ```

2. **Iniciar Apache y MySQL** desde el panel de control de XAMPP.

3. **Acceder al proyecto** en el navegador:

   ```
   http://localhost/HomeWorks/Design_Project/login
   ```

   > La base de datos y la tabla `users` se crean **automáticamente** en la primera conexión. No es necesario importar el archivo SQL manualmente.

4. **(Opcional)** Si deseas recrear la base de datos manualmente, importa `database.sql` desde phpMyAdmin.

---

## Estructura del Proyecto

```
Design_Project/
│
├── autoload.php              ← Autoloader PSR-4
├── index.php                 ← Front Controller (punto de entrada único)
├── .htaccess                 ← Reglas de reescritura de Apache
├── database.sql              ← Script SQL de referencia
├── package.json              ← Dependencias de Tailwind CSS
│
├── Config/                   ← Módulo de configuración
│   └── database.php          ← Conexión PDO Singleton
│
├── Core/                     ← Núcleo del framework
│   └── Router.php            ← Motor de enrutamiento
│
├── App/                      ← Capa de aplicación (MVC)
│   ├── Controllers/          ← Controladores
│   │   └── AuthController.php
│   ├── Models/               ← Modelos de datos
│   │   └── UserModel.php
│   └── views/                ← Plantillas HTML
│       ├── login.php
│       ├── register.php
│       └── dashboard.php
│
├── routes/                   ← Definición de rutas
│   └── web.php
│
└── src/                      ← Assets estáticos
    ├── input.css
    ├── output.css             ← CSS compilado (Tailwind)
    └── img/
        └── logo.jpeg
```

---

## Ciclo de Vida de una Petición

El siguiente diagrama muestra el flujo completo desde que el navegador realiza una petición hasta que recibe la respuesta HTML:

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
     ├─ session_start()
     ├─ require autoload.php          ← Registra autoloader PSR-4
     ├─ Database::getInstance()       ← Conexión PDO (autoload carga Config\Database)
     ├─ new Router($db)               ← Instancia el enrutador (autoload carga Core\Router)
     ├─ require routes/web.php        ← Registra todas las rutas
     └─ $router->dispatch()           ← Despacha la petición
            │
            ▼
       Router::dispatch()
            │
            ├─ Obtiene URI desde $_GET['url']
            ├─ Obtiene método HTTP (GET/POST)
            ├─ Busca ruta coincidente
            │       │
            │   No encontrada ──▶ handleNotFound() ──▶ 404 HTML
            │       │
            │   Encontrada
            │       │
            │       ▼
            └─ callAction(FQCN, método)
                    │
                    ├─ class_exists() ← El autoloader PSR-4 carga el archivo
                    ├─ new Controller($db)
                    └─ $controller->método()
                            │
                            ▼
                    Controlador ejecuta lógica
                            │
                            ├─ Interactúa con Modelo (BD)
                            └─ Renderiza Vista (HTML)
                                    │
                                    ▼
                              Respuesta al navegador
```

---

## Módulos del Sistema

### 1. Autoloader PSR-4 (`autoload.php`)

**Namespace:** —  
**Responsabilidad:** Cargar automáticamente las clases del proyecto a partir de su namespace, sin necesidad de `require_once` manuales.

#### Funcionamiento

El autoloader registra una función anónima mediante `spl_autoload_register()` que intercepta cualquier intento de usar una clase no cargada. Cuando PHP encuentra una clase desconocida (por ejemplo `App\Controllers\AuthController`), el autoloader:

1. Recorre un mapa de **prefijos de namespace → directorios base**.
2. Verifica si el FQCN (Fully Qualified Class Name) comienza con algún prefijo registrado.
3. Convierte el resto del namespace en una ruta de archivo (reemplazando `\` por `/`).
4. Si el archivo existe, lo incluye con `require`.

#### Mapa de Namespaces

| Prefijo de Namespace | Directorio Base |
|---------------------|-----------------|
| `App\`              | `App/`          |
| `Config\`           | `Config/`       |
| `Core\`             | `Core/`         |

#### Ejemplo de Resolución

```
Clase solicitada:  App\Controllers\AuthController
Prefijo encontrado: App\
Clase relativa:    Controllers\AuthController
Ruta resuelta:     App/Controllers/AuthController.php
```

#### Cómo Extenderlo

Para registrar un nuevo namespace raíz, agregar una entrada al array `$prefixes`:

```php
$prefixes = [
    'App\\'       => __DIR__ . '/App/',
    'Config\\'    => __DIR__ . '/Config/',
    'Core\\'      => __DIR__ . '/Core/',
    'Services\\'  => __DIR__ . '/Services/',  // ← nuevo
];
```

---

### 2. Front Controller (`index.php`)

**Namespace:** —  
**Responsabilidad:** Punto de entrada único de la aplicación. Toda petición HTTP pasa por este archivo.

#### Funcionamiento

1. **Inicia la sesión** con `session_start()`.
2. **Carga el autoloader** PSR-4 (`autoload.php`).
3. **Obtiene la conexión** a la base de datos vía `Config\Database` (patrón Singleton).
4. **Instancia el Router** inyectándole la conexión PDO.
5. **Carga las rutas** desde `routes/web.php`.
6. **Despacha la petición** al controlador correspondiente.

#### Configuración de Apache (`.htaccess`)

El archivo `.htaccess` redirige todas las peticiones (excepto archivos físicos como CSS, imágenes, etc.) hacia `index.php`, pasando la URI original como parámetro `url`:

```apache
RewriteEngine On
RewriteBase /HomeWorks/Design_Project/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.+)$ index.php?url=$1 [QSA,L]
```

---

### 3. Configuración de Base de Datos (`Config/database.php`)

**Namespace:** `Config`  
**Clase:** `Database`  
**Patrón de diseño:** Singleton  
**Responsabilidad:** Gestionar una única conexión PDO compartida en toda la aplicación.

#### Características

| Propiedad    | Valor por defecto       |
|-------------|------------------------|
| Host        | `localhost`            |
| Base de datos | `auth_system_db`     |
| Usuario     | `root`                 |
| Contraseña  | `` (vacía)             |
| Charset     | `utf8mb4`              |

#### Inicialización Automática

Al instanciarse por primera vez, la clase realiza automáticamente:

1. **Conexión** al servidor MySQL sin seleccionar base de datos.
2. **Creación de la BD** `auth_system_db` si no existe (con collation `utf8mb4_unicode_ci`).
3. **Selección de la BD** con `USE`.
4. **Creación de la tabla** `users` si no existe.

#### Opciones PDO Configuradas

| Opción                          | Valor                  | Propósito                          |
|---------------------------------|-----------------------|------------------------------------|
| `ATTR_ERRMODE`                  | `ERRMODE_EXCEPTION`   | Lanza excepciones en errores SQL   |
| `ATTR_EMULATE_PREPARES`         | `false`               | Prepared statements reales (anti-SQLi) |
| `ATTR_DEFAULT_FETCH_MODE`       | `FETCH_ASSOC`         | Resultados como arrays asociativos |

#### Métodos Públicos

| Método               | Retorno     | Descripción                                    |
|----------------------|-------------|------------------------------------------------|
| `getInstance()`     | `Database`  | Devuelve la instancia única (Singleton)        |
| `getConnection()`   | `\PDO`      | Devuelve el objeto de conexión PDO             |

---

### 4. Router (Enrutador) (`Core/Router.php`)

**Namespace:** `Core`  
**Clase:** `Router`  
**Responsabilidad:** Registrar rutas HTTP y despachar la petición entrante al controlador y método correspondiente.

#### Métodos Públicos

| Método | Firma | Descripción |
|--------|-------|-------------|
| `get()` | `get(string $uri, string $controller, string $method): void` | Registra una ruta GET |
| `post()` | `post(string $uri, string $controller, string $method): void` | Registra una ruta POST |
| `dispatch()` | `dispatch(): void` | Resuelve la URI actual y ejecuta el controlador |

#### Proceso de Despacho (`dispatch()`)

1. **Extrae la URI** de `$_GET['url']`, la limpia con `trim()` y `FILTER_SANITIZE_URL`.
2. **Obtiene el método HTTP** (`GET` o `POST`) desde `$_SERVER['REQUEST_METHOD']`.
3. **Busca coincidencia** en la tabla de rutas registradas.
4. Si **encuentra** la ruta → instancia el controlador (inyectando `$db`) y ejecuta el método.
5. Si **no encuentra** la ruta:
   - URI vacía → redirige a `login`.
   - Cualquier otra → muestra página **404** con estilo.

#### Instanciación de Controladores

El Router instancia controladores usando su **FQCN** (Fully Qualified Class Name). El autoloader PSR-4 se encarga de localizar y cargar el archivo automáticamente:

```php
// Internamente, el Router hace:
$controller = new $fqcn($this->db);  // Ejemplo: new App\Controllers\AuthController($db)
$controller->$methodName();
```

---

### 5. Rutas (`routes/web.php`)

**Responsabilidad:** Registro centralizado de todas las rutas de la aplicación.

#### Rutas Registradas

##### Módulo de Autenticación

| Método HTTP | URI        | Controlador                        | Acción       | Descripción                   |
|-------------|------------|-----------------------------------|-------------|-------------------------------|
| `GET`       | `login`    | `App\Controllers\AuthController`  | `login()`    | Muestra formulario de login   |
| `POST`      | `login`    | `App\Controllers\AuthController`  | `login()`    | Procesa las credenciales      |
| `GET`       | `register` | `App\Controllers\AuthController`  | `register()` | Muestra formulario de registro|
| `POST`      | `register` | `App\Controllers\AuthController`  | `register()` | Procesa el registro           |
| `GET`       | `dashboard`| `App\Controllers\AuthController`  | `dashboard()`| Panel principal del usuario   |
| `GET`       | `logout`   | `App\Controllers\AuthController`  | `logout()`   | Cierra la sesión              |

#### Formato de Registro

```php
$router->get('uri',  'App\Controllers\NombreController', 'metodo');
$router->post('uri', 'App\Controllers\NombreController', 'metodo');
```

---

### 6. Controladores (`App/Controllers/`)

#### AuthController (`App\Controllers\AuthController`)

**Namespace:** `App\Controllers`  
**Dependencia:** `App\Models\UserModel`  
**Responsabilidad:** Gestionar todo el flujo de autenticación: login, registro, dashboard y logout.

##### Constructor

Recibe la conexión PDO por inyección de dependencias y crea una instancia de `UserModel`:

```php
public function __construct(\PDO $dbConnection) {
    $this->userModel = new UserModel($dbConnection);
}
```

##### Métodos Públicos (Acciones)

| Método         | HTTP      | Descripción                                                |
|---------------|-----------|-----------------------------------------------------------|
| `login()`     | GET/POST  | Muestra el formulario o procesa las credenciales          |
| `register()`  | GET/POST  | Muestra el formulario o crea un nuevo usuario             |
| `dashboard()` | GET       | Muestra el panel del usuario (requiere sesión activa)     |
| `logout()`    | GET       | Destruye la sesión y redirige al login                    |

##### Métodos Privados (Helpers)

| Método              | Descripción                                                          |
|--------------------|----------------------------------------------------------------------|
| `sanitize()`       | Sanitiza entradas HTTP contra XSS (`htmlspecialchars` + `trim`)     |
| `enforceSession()` | Verifica sesión activa. Redirige a login si no existe o si expiró (timeout de 30 min) |
| `render()`         | Renderiza una vista PHP pasándole variables al scope con `extract()` |

##### Flujo de Login (`login()`)

```
¿Ya tiene sesión? ── Sí ──▶ Redirige a dashboard
        │
       No
        │
  ¿Es POST? ── No ──▶ Renderiza formulario login.php
        │
       Sí
        │
  Sanitiza email
  Busca usuario por email (UserModel)
        │
  ¿Usuario válido + password_verify()? ── No ──▶ Muestra error genérico
        │
       Sí
        │
  session_regenerate_id() (anti-fixation)
  Guarda datos en $_SESSION
  Redirige a dashboard
```

##### Flujo de Registro (`register()`)

```
¿Ya tiene sesión? ── Sí ──▶ Redirige a dashboard
        │
       No
        │
  ¿Es POST? ── No ──▶ Renderiza formulario register.php
        │
       Sí
        │
  Validaciones:
    ├─ Campos vacíos
    ├─ Formato de email (FILTER_VALIDATE_EMAIL)
    ├─ Longitud de contraseña (≥ 8 caracteres)
    ├─ Contraseñas coinciden
    ├─ Email no duplicado (UserModel)
    └─ Username no duplicado (UserModel)
        │
  Todo válido → password_hash() + createUser()
  Muestra mensaje de éxito con enlace al login
```

##### Flujo de Logout (`logout()`)

1. Limpia `$_SESSION`.
2. Elimina la cookie de sesión.
3. Destruye la sesión con `session_destroy()`.
4. Redirige a `login` (con parámetro `?timeout=1` si fue por inactividad).

---

### 7. Modelos (`App/Models/`)

#### UserModel (`App\Models\UserModel`)

**Namespace:** `App\Models`  
**Responsabilidad:** Capa de acceso a datos para la entidad `User`. Ejecuta queries preparadas contra la tabla `users`.

##### Constructor

```php
public function __construct(\PDO $dbConnection) {
    $this->db = $dbConnection;
}
```

##### Métodos Públicos

| Método              | Firma                                                      | Retorno        | Descripción                                    |
|---------------------|-----------------------------------------------------------|----------------|------------------------------------------------|
| `isEmailTaken()`    | `isEmailTaken(string $email): bool`                       | `bool`         | Verifica si un email ya está registrado        |
| `isUsernameTaken()` | `isUsernameTaken(string $username): bool`                  | `bool`         | Verifica si un nombre de usuario ya existe     |
| `createUser()`      | `createUser(string $username, string $email, string $hashedPassword): bool` | `bool` | Inserta un nuevo usuario en la BD |
| `getUserByEmail()`  | `getUserByEmail(string $email): array\|false`              | `array\|false` | Obtiene id, username y password hash por email |

##### Seguridad en Queries

Todos los métodos usan **prepared statements** con `bindParam()` y tipado explícito (`PDO::PARAM_STR`), eliminando el riesgo de inyección SQL:

```php
$stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
$stmt->bindParam(':email', $email, \PDO::PARAM_STR);
$stmt->execute();
```

---

### 8. Vistas (`App/views/`)

Las vistas son plantillas PHP que generan HTML. Reciben variables del controlador mediante `extract()` y utilizan **Tailwind CSS v4** para el estilizado.

#### Vistas Disponibles

| Vista           | Variables Recibidas       | Descripción                                        |
|-----------------|--------------------------|---------------------------------------------------|
| `login.php`     | `$error`                 | Formulario de inicio de sesión                    |
| `register.php`  | `$error`, `$success`     | Formulario de registro con validación visual      |
| `dashboard.php` | `$username`              | Panel principal con bienvenida al usuario         |

#### Características Comunes

- **Estilizado:** Tailwind CSS v4 (vía `src/output.css`).
- **Responsive:** Diseño adaptativo con clases de Tailwind.
- **Prevención XSS:** Todas las salidas dinámicas usan `htmlspecialchars()`.
- **Logo:** Se muestra `src/img/logo.jpeg` en cada vista.

#### login.php

- Formulario con campos de email y contraseña.
- Muestra mensaje de error si las credenciales son inválidas.
- Muestra alerta de timeout si la sesión expiró por inactividad.
- Enlace de navegación al formulario de registro.

#### register.php

- Formulario con campos: nombre de usuario, email, contraseña, confirmar contraseña.
- Conserva los valores ingresados en caso de error (excepto contraseñas).
- Al registrarse exitosamente, muestra mensaje de éxito con botón al login.
- Oculta el formulario después del registro exitoso.

#### dashboard.php

- Barra de navegación superior con logo, nombre de la plataforma y botón de cerrar sesión.
- Contenido principal con saludo personalizado al usuario.
- Grid de 4 tarjetas para mostrar contenido (fotos/descripciones).

---

## Base de Datos

### Esquema

**Base de datos:** `auth_system_db`  
**Charset:** `utf8mb4`  
**Collation:** `utf8mb4_unicode_ci`

#### Tabla `users`

| Columna      | Tipo            | Restricciones                    | Descripción                    |
|-------------|-----------------|----------------------------------|--------------------------------|
| `id`        | `INT`           | `AUTO_INCREMENT`, `PRIMARY KEY`  | Identificador único            |
| `username`  | `VARCHAR(50)`   | `NOT NULL`, `UNIQUE`             | Nombre de usuario              |
| `email`     | `VARCHAR(100)`  | `NOT NULL`, `UNIQUE`             | Correo electrónico             |
| `password`  | `VARCHAR(255)`  | `NOT NULL`                       | Hash bcrypt de la contraseña   |
| `created_at`| `TIMESTAMP`     | `DEFAULT CURRENT_TIMESTAMP`      | Fecha de creación              |

---

## Seguridad

El sistema implementa las siguientes medidas de seguridad:

| Medida                        | Implementación                                             | Ubicación              |
|------------------------------|-----------------------------------------------------------|------------------------|
| **Anti-SQL Injection**       | Prepared statements con `bindParam()` tipado              | `UserModel`            |
| **Anti-XSS**                 | `htmlspecialchars()` en toda salida dinámica               | Controladores + Vistas |
| **Hash de contraseñas**      | `password_hash()` con `PASSWORD_DEFAULT` (bcrypt)         | `AuthController`       |
| **Verificación de password** | `password_verify()` (comparación segura contra timing)    | `AuthController`       |
| **Anti-Session Fixation**    | `session_regenerate_id(true)` al autenticarse             | `AuthController`       |
| **Timeout de sesión**        | Expiración automática después de 30 minutos de inactividad| `AuthController`       |
| **Destrucción de sesión**    | Limpieza de `$_SESSION`, eliminación de cookie, `session_destroy()` | `AuthController` |
| **Sanitización de entrada**  | `htmlspecialchars()` + `stripslashes()` + `trim()`        | `AuthController`       |
| **Sanitización de URI**      | `FILTER_SANITIZE_URL`                                     | `Router`               |
| **Error genérico en login**  | Mensaje único para email/password incorrectos (anti-enumeración) | `AuthController` |

---

## Cómo Agregar un Nuevo Módulo

Gracias al autoloader PSR-4, agregar un nuevo módulo solo requiere **2 pasos**:

### Paso 1 — Crear las clases

**Modelo** (si accede a datos):

```php
// App/Models/BookModel.php
<?php
namespace App\Models;

class BookModel {
    private \PDO $db;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // ... métodos de acceso a datos
}
```

**Controlador:**

```php
// App/Controllers/BookController.php
<?php
namespace App\Controllers;

use App\Models\BookModel;

class BookController {
    private BookModel $bookModel;

    public function __construct(\PDO $dbConnection) {
        $this->bookModel = new BookModel($dbConnection);
    }

    public function index(): void {
        // Lógica + renderizar vista
    }
}
```

### Paso 2 — Registrar rutas

```php
// routes/web.php
$router->get('books',  'App\Controllers\BookController', 'index');
$router->post('books', 'App\Controllers\BookController', 'store');
```

> **No es necesario** editar `index.php` ni agregar `require_once`. El autoloader PSR-4 localiza y carga las clases automáticamente.

---

## Tecnologías Utilizadas

| Tecnología     | Uso                                    |
|---------------|----------------------------------------|
| PHP 8.1+      | Backend, lógica de servidor            |
| MySQL/MariaDB | Base de datos relacional               |
| PDO           | Capa de acceso a datos                 |
| Tailwind CSS 4| Framework de utilidades CSS            |
| Apache        | Servidor web con `mod_rewrite`         |

---

## Licencia

Proyecto académico desarrollado para la plataforma **MiMundoTrenzas**.
