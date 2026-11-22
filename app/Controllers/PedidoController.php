<?php

namespace App\Controllers;

use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Services\PedidoService;
use Core\Request;
use Core\Response;
use Exception;
use PDO;
use PDOException;

class PedidoController
{
    private Pedido $pedido;

    private DetallePedido $detalle;

    private PedidoService $service;

    public function __construct(PDO $db)
    {
        $this->pedido = new Pedido($db);
        $this->detalle = new DetallePedido($db);
        $this->service = new PedidoService($db);
    }

    // GET /api/pedidos
    public function index(): void
    {
        Response::success(
            $this->pedido->obtenerTodos(),
            'Pedidos obtenidos correctamente'
        );
    }

    // GET /api/pedidos/{id}   (incluye los comics del pedido)
    public function show(int $id): void
    {
        $pedido = $this->pedido->obtenerPorId($id);

        if (!$pedido) {
            Response::error('Pedido no encontrado', 404);
        }

        $pedido['Detalles'] = $this->detalle->obtenerPorPedido($id);

        Response::success($pedido, 'Pedido obtenido correctamente');
    }

    // POST /api/pedidos
    public function store(): void
    {
        $data = Request::body();

        $data['TipoPedido'] = strtoupper($data['TipoPedido'] ?? '');

        $errores = [];

        if (empty($data['IdCliente']) || filter_var($data['IdCliente'], FILTER_VALIDATE_INT) === false) {
            $errores['IdCliente'] = 'El cliente es obligatorio y debe ser un número entero';
        }

        if (empty($data['IdUsuario']) || filter_var($data['IdUsuario'], FILTER_VALIDATE_INT) === false) {
            $errores['IdUsuario'] = 'El vendedor es obligatorio y debe ser un número entero';
        }

        if (!in_array($data['TipoPedido'], ['VENTA', 'IMPORTACION'])) {
            $errores['TipoPedido'] = 'El tipo de pedido debe ser VENTA o IMPORTACION';
        }

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        try {
            $pedido = $this->service->crear($data);

            Response::success($pedido, 'Pedido registrado correctamente', 201);

        } catch (PDOException $e) {
            throw $e;
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }
    }

    // PUT /api/pedidos/{id}
    // se pueden enviar solo los campos que se quieren cambiar
    public function update(int $id): void
    {
        $actual = $this->pedido->obtenerPorId($id);

        if (!$actual) {
            Response::error('Pedido no encontrado', 404);
        }

        $data = Request::body();

        // lo que no se envia se mantiene igual
        $nuevo = [
            'IdCliente' => $data['IdCliente'] ?? $actual['IdCliente'],
            'IdUsuario' => $data['IdUsuario'] ?? $actual['IdUsuario'],
            'TipoPedido' => strtoupper($data['TipoPedido'] ?? $actual['TipoPedido']),
            'Estado' => strtoupper($data['Estado'] ?? $actual['Estado']),
            'Adelanto' => $data['Adelanto'] ?? $actual['Adelanto']
        ];

        $errores = [];

        $estados = ['PENDIENTE', 'PAGADO', 'EN CAMINO', 'ENTREGADO', 'CANCELADO'];

        if (filter_var($nuevo['IdCliente'], FILTER_VALIDATE_INT) === false) {
            $errores['IdCliente'] = 'El cliente debe ser un número entero';
        }

        if (filter_var($nuevo['IdUsuario'], FILTER_VALIDATE_INT) === false) {
            $errores['IdUsuario'] = 'El vendedor debe ser un número entero';
        }

        if (!in_array($nuevo['TipoPedido'], ['VENTA', 'IMPORTACION'])) {
            $errores['TipoPedido'] = 'El tipo de pedido debe ser VENTA o IMPORTACION';
        }

        if (!in_array($nuevo['Estado'], $estados)) {
            $errores['Estado'] = 'El estado debe ser PENDIENTE, PAGADO, EN CAMINO, ENTREGADO o CANCELADO';
        }

        if (!is_numeric($nuevo['Adelanto']) || $nuevo['Adelanto'] < 0) {
            $errores['Adelanto'] = 'El adelanto debe ser un número mayor o igual a 0';
        }

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        try {
            $pedido = $this->service->actualizar($actual, $nuevo);

            Response::success($pedido, 'Pedido actualizado correctamente');

        } catch (PDOException $e) {
            throw $e;
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }
    }

    // DELETE /api/pedidos/{id}
    public function destroy(int $id): void
    {
        $pedido = $this->pedido->obtenerPorId($id);

        if (!$pedido) {
            Response::error('Pedido no encontrado', 404);
        }

        try {
            $this->service->eliminar($pedido);

            Response::success(null, 'Pedido eliminado correctamente');

        } catch (PDOException $e) {
            throw $e;
        } catch (Exception $e) {
            Response::error($e->getMessage(), $e->getCode());
        }
    }
}
