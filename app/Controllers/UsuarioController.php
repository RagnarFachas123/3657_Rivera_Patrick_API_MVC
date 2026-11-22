<?php

namespace App\Controllers;

use App\Models\Usuario;
use Core\Request;
use Core\Response;
use PDO;

class UsuarioController
{
    private Usuario $usuario;

    public function __construct(PDO $db)
    {
        $this->usuario = new Usuario($db);
    }

    // GET /api/usuarios
    public function index(): void
    {
        Response::success(
            $this->usuario->obtenerTodos(),
            'Usuarios obtenidos correctamente'
        );
    }

    // GET /api/usuarios/{id}
    public function show(int $id): void
    {
        $usuario = $this->usuario->obtenerPorId($id);

        if (!$usuario) {
            Response::error('Usuario no encontrado', 404);
        }

        Response::success($usuario, 'Usuario obtenido correctamente');
    }

    // POST /api/usuarios
    public function store(): void
    {
        $data = Request::body();

        if (isset($data['Rol'])) {
            $data['Rol'] = strtoupper($data['Rol']);
        }

        // al crear la clave es obligatoria
        $errores = $this->validar($data, true);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->usuario->existeUsuario($data['Usuario'])) {
            Response::error('Ese nombre de usuario ya está en uso', 409);
        }

        $id = $this->usuario->crear($data);

        Response::success(
            $this->usuario->obtenerPorId($id),
            'Usuario registrado correctamente',
            201
        );
    }

    // PUT /api/usuarios/{id}
    public function update(int $id): void
    {
        if (!$this->usuario->obtenerPorId($id)) {
            Response::error('Usuario no encontrado', 404);
        }

        $data = Request::body();

        if (isset($data['Rol'])) {
            $data['Rol'] = strtoupper($data['Rol']);
        }

        // al actualizar la clave es opcional
        $errores = $this->validar($data, false);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->usuario->existeUsuario($data['Usuario'], $id)) {
            Response::error('Ese nombre de usuario ya está en uso', 409);
        }

        $this->usuario->actualizar($id, $data);

        Response::success(
            $this->usuario->obtenerPorId($id),
            'Usuario actualizado correctamente'
        );
    }

    // DELETE /api/usuarios/{id}
    public function destroy(int $id): void
    {
        if (!$this->usuario->obtenerPorId($id)) {
            Response::error('Usuario no encontrado', 404);
        }

        if ($this->usuario->contarRelaciones($id) > 0) {
            Response::error('No se puede eliminar: el usuario tiene pedidos o solicitudes a su cargo. Puede desactivarlo con Estado = 0', 409);
        }

        $this->usuario->eliminar($id);

        Response::success(null, 'Usuario eliminado correctamente');
    }

    private function validar(array $data, bool $claveObligatoria): array
    {
        $errores = [];

        $roles = ['ADMIN', 'VENDEDOR', 'TASADOR'];

        if (empty($data['NombreCompleto'])) {
            $errores['NombreCompleto'] = 'El nombre completo es obligatorio';
        } elseif (strlen($data['NombreCompleto']) > 100) {
            $errores['NombreCompleto'] = 'El nombre completo debe tener máximo 100 caracteres';
        }

        // solo letras, numeros, punto y guion bajo
        if (empty($data['Usuario'])) {
            $errores['Usuario'] = 'El usuario es obligatorio';
        } elseif (!preg_match('/^[a-zA-Z0-9._]{3,30}$/', $data['Usuario'])) {
            $errores['Usuario'] = 'El usuario debe tener de 3 a 30 caracteres (letras, números, punto o guion bajo)';
        }

        if ($claveObligatoria && empty($data['Clave'])) {
            $errores['Clave'] = 'La clave es obligatoria';
        } elseif (!empty($data['Clave']) && strlen($data['Clave']) < 6) {
            $errores['Clave'] = 'La clave debe tener al menos 6 caracteres';
        }

        if (empty($data['Rol']) || !in_array($data['Rol'], $roles)) {
            $errores['Rol'] = 'El rol debe ser ADMIN, VENDEDOR o TASADOR';
        }

        if (isset($data['Estado']) && !in_array($data['Estado'], [0, 1, '0', '1'], true)) {
            $errores['Estado'] = 'El estado debe ser 1 (activo) o 0 (inactivo)';
        }

        return $errores;
    }
}
