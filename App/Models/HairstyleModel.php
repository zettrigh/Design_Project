<?php

namespace App\Models;

// ─────────────────────────────────────────────────────────────
// App\Models\HairstyleModel
// Capa de acceso a datos para la entidad Hairstyle (Peinados).
// ─────────────────────────────────────────────────────────────

class HairstyleModel {

    private \PDO $db;

    public function __construct(\PDO $dbConnection) {
        $this->db = $dbConnection;
    }

    // Obtiene todos los peinados activos en venta
    public function getAllActiveHairstyles(): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, description, price, image_url, status, created_at 
                 FROM hairstyles 
                 WHERE status = 'active' 
                 ORDER BY id DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error en getAllActiveHairstyles: ' . $e->getMessage());
            return [];
        }
    }

    // Obtiene todos los peinados para la vista de administrador
    public function getAllHairstyles(): array {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, description, price, image_url, status, created_at 
                 FROM hairstyles 
                 ORDER BY id DESC"
            );
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('Error en getAllHairstyles: ' . $e->getMessage());
            return [];
        }
    }

    // Obtiene un peinado por ID
    public function getHairstyleById(int $id): array|false {
        try {
            $stmt = $this->db->prepare(
                "SELECT id, name, description, price, image_url, status, created_at 
                 FROM hairstyles 
                 WHERE id = :id LIMIT 1"
            );
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->rowCount() === 1 ? $stmt->fetch() : false;
        } catch (\PDOException $e) {
            error_log('Error en getHairstyleById: ' . $e->getMessage());
            return false;
        }
    }

    // Crea un nuevo peinado
    public function createHairstyle(string $name, string $description, float $price, string $imageUrl, string $status = 'active'): bool {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO hairstyles (name, description, price, image_url, status)
                 VALUES (:name, :description, :price, :image_url, :status)"
            );
            $stmt->bindParam(':name',        $name,        \PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, \PDO::PARAM_STR);
            $stmt->bindParam(':price',       $price);
            $stmt->bindParam(':image_url',   $imageUrl,    \PDO::PARAM_STR);
            $stmt->bindParam(':status',      $status,      \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error creando peinado: ' . $e->getMessage());
            return false;
        }
    }

    // Actualiza los detalles de un peinado
    public function updateHairstyle(int $id, string $name, string $description, float $price, string $imageUrl, string $status): bool {
        try {
            $stmt = $this->db->prepare(
                "UPDATE hairstyles 
                 SET name = :name, description = :description, price = :price, image_url = :image_url, status = :status 
                 WHERE id = :id"
            );
            $stmt->bindParam(':id',          $id,          \PDO::PARAM_INT);
            $stmt->bindParam(':name',        $name,        \PDO::PARAM_STR);
            $stmt->bindParam(':description', $description, \PDO::PARAM_STR);
            $stmt->bindParam(':price',       $price);
            $stmt->bindParam(':image_url',   $imageUrl,    \PDO::PARAM_STR);
            $stmt->bindParam(':status',      $status,      \PDO::PARAM_STR);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error actualizando peinado: ' . $e->getMessage());
            return false;
        }
    }

    // Elimina un peinado
    public function deleteHairstyle(int $id): bool {
        try {
            $stmt = $this->db->prepare(
                "DELETE FROM hairstyles WHERE id = :id"
            );
            $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log('Error eliminando peinado: ' . $e->getMessage());
            return false;
        }
    }
}
