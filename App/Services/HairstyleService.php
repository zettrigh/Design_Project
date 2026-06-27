<?php

namespace App\Services;

use App\Models\HairstyleModel;
use Core\Result;

class HairstyleService
{
    private HairstyleModel $hairstyleModel;

    public function __construct(HairstyleModel $hairstyleModel)
    {
        $this->hairstyleModel = $hairstyleModel;
    }

    public function getAllHairstyles(): array
    {
        return $this->hairstyleModel->getAllHairstyles();
    }

    public function getActiveHairstyles(): array
    {
        return $this->hairstyleModel->getAllActiveHairstyles();
    }

    public function getHairstyleById(int $id): array|false
    {
        return $this->hairstyleModel->getHairstyleById($id);
    }

    public function getPriceUSD(int $id): ?float
    {
        $hairstyle = $this->hairstyleModel->getHairstyleById($id);
        return $hairstyle ? (float) $hairstyle['price'] : null;
    }

    public function createHairstyle(string $name, string $description, float $price, string $imageUrl, string $status = 'active', int $durationMinutes = 60): Result
    {
        if (empty(trim($name))) {
            return Result::failure('El nombre del peinado es obligatorio.');
        }
        if ($price <= 0) {
            return Result::failure('El precio debe ser mayor a cero.');
        }
        if (empty(trim($imageUrl))) {
            return Result::failure('La URL de la imagen es obligatoria.');
        }

        $durationMinutes = max(15, min(480, $durationMinutes));

        $created = $this->hairstyleModel->createHairstyle(
            trim($name), trim($description), $price, trim($imageUrl), $status, $durationMinutes
        );

        return $created
            ? Result::success(null, 'Peinado creado exitosamente.')
            : Result::failure('Error al crear el peinado.');
    }

    public function updateHairstyle(int $id, string $name, string $description, float $price, string $imageUrl, string $status, int $durationMinutes = 60): Result
    {
        if ($id <= 0) {
            return Result::failure('ID de peinado inválido.');
        }
        if (empty(trim($name))) {
            return Result::failure('El nombre del peinado es obligatorio.');
        }
        if ($price <= 0) {
            return Result::failure('El precio debe ser mayor a cero.');
        }

        $durationMinutes = max(15, min(480, $durationMinutes));

        $updated = $this->hairstyleModel->updateHairstyle(
            $id, trim($name), trim($description), $price, trim($imageUrl), $status, $durationMinutes
        );

        return $updated
            ? Result::success(null, 'Peinado actualizado exitosamente.')
            : Result::failure('Error al actualizar el peinado.');
    }

    public function deleteHairstyle(int $id): Result
    {
        if ($id <= 0) {
            return Result::failure('ID de peinado inválido.');
        }

        $deleted = $this->hairstyleModel->deleteHairstyle($id);

        return $deleted
            ? Result::success(null, 'Peinado eliminado exitosamente.')
            : Result::failure('Error al eliminar el peinado.');
    }
}
