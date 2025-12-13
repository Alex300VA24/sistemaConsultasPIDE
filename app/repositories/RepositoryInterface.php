<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Interface base para todos los repositorios
 * Define operaciones CRUD estándar
 */
interface RepositoryInterface
{
    /**
     * Encuentra un registro por su ID
     *
     * @param int $id ID del registro
     * @return array|null Datos del registro o null si no existe
     */
    public function find(int $id): ?array;

    /**
     * Obtiene todos los registros
     *
     * @return array Lista de registros
     */
    public function findAll(): array;

    /**
     * Crea un nuevo registro
     *
     * @param array $data Datos del registro
     * @return int ID del registro creado
     */
    public function create(array $data): int;

    /**
     * Actualiza un registro existente
     *
     * @param int $id ID del registro
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function update(int $id, array $data): bool;

    /**
     * Elimina un registro
     *
     * @param int $id ID del registro
     * @return bool True si se eliminó correctamente
     */
    public function delete(int $id): bool;
}
