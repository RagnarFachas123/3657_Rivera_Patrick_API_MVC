<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\Comic;
use App\Models\DetallePedido;
use App\Models\Pedido;
use App\Models\Usuario;
use Exception;
use PDO;
use Throwable;

// Reglas de negocio de los pedidos.
// Los errores se lanzan con el codigo HTTP: new Exception('mensaje', 409)

class PedidoService
{
    private PDO $db;
    private Pedido $pedido;
    private DetallePedido $detalle;
    private Comic $comic;
    private Cliente $cliente;
    private Usuario $usuario;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->pedido = new Pedido($db);
        $this->detalle = new DetallePedido($db);
        $this->comic = new Comic($db);
        $this->cliente = new Cliente($db);
        $this->usuario = new Usuario($db);
    }

    public function crear(array $data): array
    {
        $this->validarClienteYVendedor($data['IdCliente'], $data['IdUsuario']);

        $id = $this->pedido->crear($data);

        return $this->pedido->obtenerPorId($id);
    }

    public function actualizar(array $actual, array $nuevo): array
    {
        $id = $actual['IdPedido'];

        // un pedido ENTREGADO o CANCELADO ya no se modifica
        if (in_array($actual['Estado'], ['ENTREGADO', 'CANCELADO'])) {
            throw new Exception('El pedido ya está cerrado (' . $actual['Estado'] . ') y no se puede modificar', 409);
        }

        // si ya tiene comics no se puede cambiar de VENTA a IMPORTACION (afecta el stock)
        $detalles = $this->detalle->obtenerPorPedido($id);

        if ($nuevo['TipoPedido'] !== $actual['TipoPedido'] && count($detalles) > 0) {
            throw new Exception('No se puede cambiar el tipo de pedido porque ya tiene cómics agregados', 409);
        }

        $this->validarClienteYVendedor($nuevo['IdCliente'], $nuevo['IdUsuario']);

        if ($nuevo['Adelanto'] > $actual['Total']) {
            throw new Exception('El adelanto no puede ser mayor al total del pedido (S/ ' . $actual['Total'] . ')', 400);
        }

        $this->db->beginTransaction();

        try {
            // si se cancela una venta, el stock regresa al catalogo
            if ($nuevo['Estado'] === 'CANCELADO' && $actual['TipoPedido'] === 'VENTA') {
                foreach ($detalles as $d) {
                    $this->comic->devolverStock($d['IdComic'], $d['Cantidad']);
                }
            }

            $this->pedido->actualizar($id, $nuevo);

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->pedido->obtenerPorId($id);
    }

    public function eliminar(array $pedido): void
    {
        $id = $pedido['IdPedido'];

        if (!in_array($pedido['Estado'], ['PENDIENTE', 'CANCELADO'])) {
            throw new Exception('Solo se pueden eliminar pedidos PENDIENTE o CANCELADO', 409);
        }

        $this->db->beginTransaction();

        try {
            // si es una venta pendiente se devuelve el stock
            // (si esta CANCELADO el stock ya se devolvio antes)
            if ($pedido['Estado'] === 'PENDIENTE' && $pedido['TipoPedido'] === 'VENTA') {
                foreach ($this->detalle->obtenerPorPedido($id) as $d) {
                    $this->comic->devolverStock($d['IdComic'], $d['Cantidad']);
                }
            }

            $this->pedido->eliminar($id);

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function validarClienteYVendedor($idCliente, $idUsuario): void
    {
        if (!$this->cliente->obtenerPorId($idCliente)) {
            throw new Exception('El cliente indicado no existe', 400);
        }

        $vendedor = $this->usuario->obtenerPorId($idUsuario);

        if (!$vendedor) {
            throw new Exception('El usuario (vendedor) indicado no existe', 400);
        }

        if (!in_array($vendedor['Rol'], ['VENDEDOR', 'ADMIN'])) {
            throw new Exception('El usuario debe tener rol VENDEDOR o ADMIN para atender pedidos', 400);
        }

        if ($vendedor['Estado'] == 0) {
            throw new Exception('El usuario indicado está inactivo', 400);
        }
    }
}
