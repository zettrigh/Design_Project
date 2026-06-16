<?php

namespace App\Services;

use App\Models\HairstyleModel;

/**
 * App\Services\HairstyleService
 *
 * Capa de servicio para la gestión del catálogo de peinados.
 * Centraliza la lógica de negocio, validación y reglas de acceso.
 *
 * Principios aplicados:
 *   - SRP: Solo maneja lógica de catálogo de peinados.
 *   - DIP: Depende de HairstyleModel (abstracción).
 *   - DRY: Centraliza validaciones reutilizables.
 *
 * @package App\Services
 */
class HairstyleService
{
    /**
     * @var HairstyleModel Modelo de acceso a datos de peinados.
     */
    private HairstyleModel $hairstyleModel;

    /**
     * Constructor con inyección de dependencias.
     *
     * @param HairstyleModel $hairstyleModel Instancia del modelo de peinados.
     */
    public function __construct(HairstyleModel $hairstyleModel)
    {
        $this->hairstyleModel = $hairstyleModel;
    }

    /**
     * Obtiene todos los peinados activos (para clientes).
     *
     * @return array<int, array{id: int, name: string, description: string, price: float, image_url: string, status: string, created_at: string}>
     */
    public function getActiveHairstyles(): array
    {
        return $this->hairstyleModel->getAllActiveHairstyles();
    }

    /**
     * Obtiene todos los peinados (para admin/trabajador).
     *
     * @return array<int, array{id: int, name: string, description: string, price: float, image_url: string, status: string, created_at: string}>
     */
    public function getAllHairstyles(): array
    {
        return $this->hairstyleModel->getAllHairstyles();
    }

    /**
     * Obtiene un peinado por su ID.
     *
     * @param int $id ID del peinado.
     * @return array|false Datos del peinado o false si no existe.
     */
    public function getHairstyleById(int $id): array|false
    {
        return $this->hairstyleModel->getHairstyleById($id);
    }

    /**
     * Crea un nuevo peinado en el catálogo.
     *
     * @param string $name        Nombre del peinado.
     * @param string $description Descripción detallada.
     * @param float  $price       Precio en la moneda base.
     * @param string $imageUrl    URL de la imagen del peinado.
     * @param string $status      Estado ('active' o 'inactive').
     * @return array{success: bool, message: string}
     */
    public function createHairstyle(
        string $name,
        string $description,
        float $price,
        string $imageUrl,
        string $status = 'active'
    ): array {
        $name        = $this->sanitize($name);
        $description = $this->sanitize($description);
        $imageUrl    = $this->sanitize($imageUrl);

        // URL de imagen por defecto si no se proporciona
        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');
        if (empty($imageUrl)) {
            $imageUrl = $baseUrl . '/src/img/braid_box.png';
        }

        if (empty($name) || empty($description) || $price <= 0) {
            return ['success' => false, 'message' => 'El nombre, la descripción y un precio válido son obligatorios.'];
        }

        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }

        if ($this->hairstyleModel->createHairstyle($name, $description, $price, $imageUrl, $status)) {
            return ['success' => true, 'message' => 'Peinado agregado con éxito al catálogo.'];
        }

        return ['success' => false, 'message' => 'Error al guardar el peinado en la base de datos.'];
    }

    /**
     * Actualiza los datos de un peinado existente.
     *
     * @param int    $id          ID del peinado.
     * @param string $name        Nuevo nombre.
     * @param string $description Nueva descripción.
     * @param float  $price       Nuevo precio.
     * @param string $imageUrl    Nueva URL de imagen.
     * @param string $status      Nuevo estado.
     * @return array{success: bool, message: string}
     */
    public function updateHairstyle(
        int $id,
        string $name,
        string $description,
        float $price,
        string $imageUrl,
        string $status
    ): array {
        $name        = $this->sanitize($name);
        $description = $this->sanitize($description);
        $imageUrl    = $this->sanitize($imageUrl);

        $baseUrl = \Config\Environment::get('BASE_URL', '/HomeWorks/Design_Project');
        if (empty($imageUrl)) {
            $imageUrl = $baseUrl . '/src/img/braid_box.png';
        }

        if ($id <= 0 || empty($name) || empty($description) || $price <= 0) {
            return ['success' => false, 'message' => 'Faltan campos obligatorios para actualizar el peinado.'];
        }

        if (!in_array($status, ['active', 'inactive'])) {
            $status = 'active';
        }

        if ($this->hairstyleModel->updateHairstyle($id, $name, $description, $price, $imageUrl, $status)) {
            return ['success' => true, 'message' => 'Peinado actualizado correctamente.'];
        }

        return ['success' => false, 'message' => 'No se realizaron cambios o el peinado no existe.'];
    }

    /**
     * Elimina un peinado del catálogo.
     *
     * @param int $id ID del peinado a eliminar.
     * @return array{success: bool, message: string}
     */
    public function deleteHairstyle(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'ID de peinado inválido.'];
        }

        if ($this->hairstyleModel->deleteHairstyle($id)) {
            return ['success' => true, 'message' => 'Peinado eliminado del sistema de manera exitosa.'];
        }

        return ['success' => false, 'message' => 'No se pudo eliminar el peinado. Podría estar vinculado a una reserva activa.'];
    }

    /**
     * Sanitiza un string de entrada HTTP.
     *
     * @param string $data Datos crudos.
     * @return string Datos sanitizados.
     */
    private function sanitize(string $data): string
    {
        return htmlspecialchars(stripslashes(trim($data)), ENT_QUOTES, 'UTF-8');
    }
}
