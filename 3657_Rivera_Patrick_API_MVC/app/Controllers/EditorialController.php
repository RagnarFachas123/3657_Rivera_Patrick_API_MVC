<?php

namespace App\Controllers;

use App\Models\Editorial;
use Core\Request;
use Core\Response;
use PDO;

class EditorialController
{
    private Editorial $editorial;

    public function __construct(PDO $db)
    {
        $this->editorial = new Editorial($db);
    }

    // GET /api/editoriales
    public function index(): void
    {
        Response::success(
            $this->editorial->obtenerTodos(),
            'Editoriales obtenidas correctamente'
        );
    }

    // GET /api/editoriales/{id}
    public function show(int $id): void
    {
        $editorial = $this->editorial->obtenerPorId($id);

        if (!$editorial) {
            Response::error('Editorial no encontrada', 404);
        }

        Response::success($editorial, 'Editorial obtenida correctamente');
    }

    // POST /api/editoriales
    public function store(): void
    {
        $data = Request::body();

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->editorial->existeNombre($data['Nombre'])) {
            Response::error('Ya existe una editorial con ese nombre', 409);
        }

        $id = $this->editorial->crear($data);

        Response::success(
            $this->editorial->obtenerPorId($id),
            'Editorial registrada correctamente',
            201
        );
    }

    // PUT /api/editoriales/{id}
    public function update(int $id): void
    {
        if (!$this->editorial->obtenerPorId($id)) {
            Response::error('Editorial no encontrada', 404);
        }

        $data = Request::body();

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->editorial->existeNombre($data['Nombre'], $id)) {
            Response::error('Ya existe otra editorial con ese nombre', 409);
        }

        $this->editorial->actualizar($id, $data);

        Response::success(
            $this->editorial->obtenerPorId($id),
            'Editorial actualizada correctamente'
        );
    }

    // DELETE /api/editoriales/{id}
    public function destroy(int $id): void
    {
        if (!$this->editorial->obtenerPorId($id)) {
            Response::error('Editorial no encontrada', 404);
        }

        // no se borra si tiene comics (FK_Comic_Editorial)
        $comics = $this->editorial->contarComics($id);

        if ($comics > 0) {
            Response::error("No se puede eliminar: la editorial tiene $comics cómic(s) registrados", 409);
        }

        $this->editorial->eliminar($id);

        Response::success(null, 'Editorial eliminada correctamente');
    }

    private function validar(array $data): array
    {
        $errores = [];

        if (empty($data['Nombre'])) {
            $errores['Nombre'] = 'El nombre es obligatorio';
        } elseif (strlen($data['Nombre']) > 50) {
            $errores['Nombre'] = 'El nombre debe tener máximo 50 caracteres';
        }

        if (empty($data['Pais'])) {
            $errores['Pais'] = 'El país es obligatorio';
        } elseif (strlen($data['Pais']) > 50) {
            $errores['Pais'] = 'El país debe tener máximo 50 caracteres';
        }

        if (isset($data['Estado']) && !in_array($data['Estado'], [0, 1, '0', '1'], true)) {
            $errores['Estado'] = 'El estado debe ser 1 (activo) o 0 (inactivo)';
        }

        return $errores;
    }
}
