<?php

namespace App\Models;

class UserModel
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function findByEmail(string $email): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, role, created_at
             FROM users WHERE email = :email LIMIT 1"
        );
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password, role, created_at
             FROM users WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function createUser(string $username, string $email, string $password, string $role = 'client'): int|false
    {
        $stmt = $this->db->prepare(
            "INSERT INTO users (username, email, password, role)
             VALUES (:username, :email, :password, :role)"
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $password,
            ':role'     => $role,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function usernameExists(string $username): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        return $stmt->fetch() !== false;
    }

    public function emailExists(string $email): bool
    {
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() !== false;
    }

    public function getAllUsers(): array
    {
        $stmt = $this->db->query(
            "SELECT id, username, email, role, created_at FROM users ORDER BY id DESC"
        );
        return $stmt->fetchAll();
    }

    public function getAllWorkers(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, role, created_at
             FROM users WHERE role = 'worker' ORDER BY id DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAllAdmins(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, role, created_at
             FROM users WHERE role IN ('admin', 'worker') ORDER BY role, id"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUsersByRole(string $role): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, role, created_at FROM users WHERE role = :role ORDER BY id DESC"
        );
        $stmt->execute([':role' => $role]);
        return $stmt->fetchAll();
    }

    public function updateUser(int $id, string $username, string $email): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE users SET username = :username, email = :email WHERE id = :id"
        );
        return $stmt->execute([
            ':id'       => $id,
            ':username' => $username,
            ':email'    => $email,
        ]);
    }

    public function updatePassword(int $id, string $newPasswordHash): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET password = :password WHERE id = :id");
        return $stmt->execute([
            ':password' => $newPasswordHash,
            ':id'       => $id,
        ]);
    }

    public function updateRole(int $id, string $role): bool
    {
        $stmt = $this->db->prepare("UPDATE users SET role = :role WHERE id = :id");
        return $stmt->execute([':role' => $role, ':id' => $id]);
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function countByRole(string $role): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as total FROM users WHERE role = :role");
        $stmt->execute([':role' => $role]);
        return (int) $stmt->fetch()['total'];
    }
}
