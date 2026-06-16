-- ─────────────────────────────────────────────────────────────
-- MiMundoTrenzas — Esquema de Base de Datos
-- Versión: 2.0 (RBAC + Pagos)
-- ─────────────────────────────────────────────────────────────

CREATE DATABASE IF NOT EXISTS auth_system_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE auth_system_db;

-- ── Tabla: users ────────────────────────────────────────────
-- Almacena todos los usuarios del sistema con soporte RBAC.
-- Roles: admin, worker, client
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('admin', 'worker', 'client') NOT NULL DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla: hairstyles ───────────────────────────────────────
-- Catálogo de peinados disponibles.
-- Los precios se almacenan directamente en USD.
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS hairstyles (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    price       DECIMAL(10,2) NOT NULL,
    image_url   VARCHAR(255) NOT NULL,
    status      ENUM('active', 'inactive') DEFAULT 'active',
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla: reservations ─────────────────────────────────────
-- Registra las reservas (apartados) de los clientes.
-- Estados: pending, confirmed, cancelled
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS reservations (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    hairstyle_id INT NOT NULL,
    status       ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
    reserved_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (hairstyle_id) REFERENCES hairstyles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Tabla: payments ─────────────────────────────────────────
-- Registra los pagos procesados a través de la pasarela.
-- Todos los montos se manejan en USD.
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS payments (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id  INT NOT NULL,
    user_id         INT NOT NULL,
    amount          DECIMAL(10,2) NOT NULL,
    currency        VARCHAR(3) NOT NULL DEFAULT 'USD',
    exchange_rate   DECIMAL(10,6) NOT NULL DEFAULT 1.000000,
    amount_usd      DECIMAL(10,2) NOT NULL,
    payment_method  VARCHAR(50) NOT NULL DEFAULT 'stripe',
    transaction_id  VARCHAR(255) NOT NULL DEFAULT '',
    status          ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
