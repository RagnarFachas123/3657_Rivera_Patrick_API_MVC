<?php

namespace App\Controllers;

use App\Models\DetallePedido;
use App\Services\DetallePedidoService;
use Core\Request;
use Core\Response;
use Exception;
use PDO;
use PDOException;

class DetallePedidoController
{
    private DetallePedido $detalle;

    private DetallePedidoService $service;

    public function __construct(PDO $db)
    {
        $this->detalle = new DetallePedido($db);
        $this->service = new DetallePedidoService($db);
    }

    // GET /api/detalles
    public function index(): void
    {
        Response::success(
            $this->detalle->obtenerTodos(),
            'Detalles de pedido obtenidos correctamente'
        );
    }

    // GET /api/detalles/{id}
    public function show(int $id): void
    {
        $detalle = $this->detalle->obtenerPorId($id);

        if (!$detalle) {
            Response::error('Detalle no encontrado', 404);
        }

        Response::success($detalle, 'Detalle obtenido correctamente');
    }

    // POST /api/detalles   (agrega un comic a un pedido)
    public function store(): void
    {
        $data = Request::body();

        $errores = [];

        if (empty($data['IdPedido']) || filter_var($data['IdPedido'], FILTER_VALIDATE_INT) === false) {
            $errores['IdPedido'] = 'El pedido es obligatorio y debe ser un número entero';
        }

        if (empty($data['IdComic']) || filter_var($data['IdComic'], FILTER_VALIDATE_INT) === false) {
            $errores['IdComic'] = 'El cómic es obligatorio y debe ser un número entero';
        }

        if (!$this->cantidadValida($data)) {
            $errores['Cantidad'] = 'La cantidad debe ser un número entero mayor a 0';
        }

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        try {
            $detalle = $this->service->crear($data['IdPedido'], $data['IdComic'], $data['Cantidad']);

            Response::success($detalle, 'Cómic agregado al pedido correctamente', 201);

        } catch (PDOException $e) {
            throw $e;
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }
    }

    // PUT /api/detalles/{id}   (solo se cambia la cantidad)
    public function update(int $id): void
    {
        $detalle = $this->detalle->obtenerPorId($id);

        if (!$detalle) {
            Response::error('Detalle no encontrado', 404);
        }

        $data = Request::body();

        if (!$this->cantidadValida($data)) {
            Response::error('Datos inválidos', 400, [
                'Cantidad' => 'La cantidad debe ser un número entero mayor a 0'
            ]);
        }

        try {
            $detalle = $this->service->actualizar($detalle, $data['Cantidad']);

            Response::success($detalle, 'Detalle actualizado correctamente');

        } catch (PDOException $e) {
            throw $e;
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }
    }

    // DELETE /api/detalles/{id}   (quita el comic del pedido)
    public function destroy(int $id): void
    {
        $detalle = $this->detalle->obtenerPorId($id);

        if (!$detalle) {
            Response::error('Detalle no encontrado', 404);
        }

        try {
            $this->service->eliminar($detalle);

            Response::success(null, 'Detalle eliminado correctamente');

        } catch (PDOException $e) {
            throw $e;
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }
    }

    private function cantidadValida(array $data): bool
    {
        return isset($data['Cantidad'])
            && filter_var($data['Cantidad'], FILTER_VALIDATE_INT) !== false
            && $data['Cantidad'] > 0;
    }
}
