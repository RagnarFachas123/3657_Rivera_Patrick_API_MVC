<?php

namespace App\Controllers;

use App\Models\Cliente;
use Core\Request;
use Core\Response;
use PDO;

class ClienteController
{
    private Cliente $cliente;

    public function __construct(PDO $db)
    {
        $this->cliente = new Cliente($db);
    }

    // GET /api/clientes
    public function index(): void
    {
        Response::success(
            $this->cliente->obtenerTodos(),
            'Clientes obtenidos correctamente'
        );
    }

    // GET /api/clientes/{id}
    public function show(int $id): void
    {
        $cliente = $this->cliente->obtenerPorId($id);

        if (!$cliente) {
            Response::error('Cliente no encontrado', 404);
        }

        Response::success($cliente, 'Cliente obtenido correctamente');
    }

    // POST /api/clientes
    public function store(): void
    {
        $data = Request::body();

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->cliente->existeDni($data['DNI'])) {
            Response::error('Ya existe un cliente con ese DNI', 409);
        }

        if ($this->cliente->existeEmail($data['Email'])) {
            Response::error('Ya existe un cliente con ese email', 409);
        }

        $id = $this->cliente->crear($data);

        Response::success(
            $this->cliente->obtenerPorId($id),
            'Cliente registrado correctamente',
            201
        );
    }

    // PUT /api/clientes/{id}
    public function update(int $id): void
    {
        if (!$this->cliente->obtenerPorId($id)) {
            Response::error('Cliente no encontrado', 404);
        }

        $data = Request::body();

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->cliente->existeDni($data['DNI'], $id)) {
            Response::error('Ya existe otro cliente con ese DNI', 409);
        }

        if ($this->cliente->existeEmail($data['Email'], $id)) {
            Response::error('Ya existe otro cliente con ese email', 409);
        }

        $this->cliente->actualizar($id, $data);

        Response::success(
            $this->cliente->obtenerPorId($id),
            'Cliente actualizado correctamente'
        );
    }

    // DELETE /api/clientes/{id}
    public function destroy(int $id): void
    {
        if (!$this->cliente->obtenerPorId($id)) {
            Response::error('Cliente no encontrado', 404);
        }

        // no se borra si tiene pedidos o solicitudes
        if ($this->cliente->contarRelaciones($id) > 0) {
            Response::error('No se puede eliminar: el cliente tiene pedidos o solicitudes registradas', 409);
        }

        $this->cliente->eliminar($id);

        Response::success(null, 'Cliente eliminado correctamente');
    }

    private function validar(array $data): array
    {
        $errores = [];

        if (empty($data['Nombres'])) {
            $errores['Nombres'] = 'Los nombres son obligatorios';
        } elseif (strlen($data['Nombres']) > 50) {
            $errores['Nombres'] = 'Los nombres deben tener máximo 50 caracteres';
        }

        if (empty($data['Apellidos'])) {
            $errores['Apellidos'] = 'Los apellidos son obligatorios';
        } elseif (strlen($data['Apellidos']) > 50) {
            $errores['Apellidos'] = 'Los apellidos deben tener máximo 50 caracteres';
        }

        // DNI: 8 numeros
        if (empty($data['DNI'])) {
            $errores['DNI'] = 'El DNI es obligatorio';
        } elseif (!preg_match('/^[0-9]{8}$/', $data['DNI'])) {
            $errores['DNI'] = 'El DNI debe tener exactamente 8 números';
        }

        if (empty($data['Email'])) {
            $errores['Email'] = 'El email es obligatorio';
        } elseif (!filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
            $errores['Email'] = 'El email no tiene un formato válido';
        }

        // celular: 9 numeros y empieza en 9 (es opcional)
        if (!empty($data['Telefono']) && !preg_match('/^9[0-9]{8}$/', $data['Telefono'])) {
            $errores['Telefono'] = 'El teléfono debe tener 9 números y empezar con 9';
        }

        if (!empty($data['Distrito']) && strlen($data['Distrito']) > 50) {
            $errores['Distrito'] = 'El distrito debe tener máximo 50 caracteres';
        }

        return $errores;
    }
}
