<?php

namespace App\Models;

class HairstyleModel
{
    private \PDO $db;

    private const COLUMNS = 'id, name, description, price, duration_minutes, image_url, status, created_at';

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function getAllActiveHairstyles(): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::COLUMNS . " FROM hairstyles WHERE status = 'active' ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllHairstyles(): array
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::COLUMNS . " FROM hairstyles ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getHairstyleById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT " . self::COLUMNS . " FROM hairstyles WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function createHairstyle(string $name, string $description, float $price, string $imageUrl, string $status = 'active', int $durationMinutes = 60): bool
    {
        $stmt = $this->db->prepare(
            "INSERT INTO hairstyles (name, description, price, duration_minutes, image_url, status)
             VALUES (:name, :description, :price, :duration_minutes, :image_url, :status)"
        );
        return $stmt->execute([
            ':name'             => $name,
            ':description'      => $description,
            ':price'            => $price,
            ':duration_minutes' => $durationMinutes,
            ':image_url'        => $imageUrl,
            ':status'           => $status,
        ]);
    }

    public function updateHairstyle(int $id, string $name, string $description, float $price, string $imageUrl, string $status, int $durationMinutes = 60): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE hairstyles SET name = :name, description = :description, price = :price,
             duration_minutes = :duration_minutes, image_url = :image_url, status = :status
             WHERE id = :id"
        );
        return $stmt->execute([
            ':id'               => $id,
            ':name'             => $name,
            ':description'      => $description,
            ':price'            => $price,
            ':duration_minutes' => $durationMinutes,
            ':image_url'        => $imageUrl,
            ':status'           => $status,
        ]);
    }

    public function deleteHairstyle(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM hairstyles WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
