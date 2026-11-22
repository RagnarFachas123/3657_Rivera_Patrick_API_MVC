<?php

namespace App\Services;

use App\Models\Comic;
use App\Models\DetallePedido;
use App\Models\Pedido;
use Exception;
use PDO;
use Throwable;

// Misma logica que el procedimiento sp_AgregarComicPedido del script SQL:
// - el pedido debe existir y estar PENDIENTE
// - el comic debe existir y tener precio
// - si es VENTA se valida y descuenta el stock
// - se recalcula el total del pedido
// Todo dentro de una transaccion: si algo falla no se guarda nada.

class DetallePedidoService
{
    private PDO $db;
    private DetallePedido $detalle;
    private Pedido $pedido;
    private Comic $comic;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->detalle = new DetallePedido($db);
        $this->pedido = new Pedido($db);
        $this->comic = new Comic($db);
    }

    public function crear(int $idPedido, int $idComic, int $cantidad): array
    {
        $pedido = $this->obtenerPedidoPendiente($idPedido);

        $comic = $this->comic->obtenerPorId($idComic);

        if (!$comic) {
            throw new Exception('El cómic indicado no existe', 400);
        }

        if ($comic['Precio'] === null) {
            throw new Exception('El cómic es de colección: primero debe cotizarse', 409);
        }

        if ($this->detalle->existeComicEnPedido($idPedido, $idComic)) {
            throw new Exception('Ese cómic ya está en el pedido, actualice la cantidad del detalle', 409);
        }

        $this->db->beginTransaction();

        try {
            if ($pedido['TipoPedido'] === 'VENTA') {
                if (!$this->comic->descontarStock($idComic, $cantidad)) {
                    throw new Exception('Stock insuficiente. Stock disponible: ' . $comic['Stock'], 409);
                }
            }

            $id = $this->detalle->crear($idPedido, $idComic, $cantidad, $comic['Precio']);

            $this->pedido->recalcularTotal($idPedido);

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->detalle->obtenerPorId($id);
    }

    public function actualizar(array $detalle, int $nuevaCantidad): array
    {
        $pedido = $this->obtenerPedidoPendiente($detalle['IdPedido']);

        $diferencia = $nuevaCantidad - $detalle['Cantidad'];

        // el nuevo total no puede quedar por debajo del adelanto
        $nuevoTotal = $pedido['Total'] + ($diferencia * $detalle['PrecioUnitario']);
        $this->validarAdelanto($pedido, $nuevoTotal);

        $this->db->beginTransaction();

        try {
            if ($pedido['TipoPedido'] === 'VENTA') {
                if ($diferencia > 0 && !$this->comic->descontarStock($detalle['IdComic'], $diferencia)) {
                    throw new Exception('Stock insuficiente para aumentar la cantidad', 409);
                }

                if ($diferencia < 0) {
                    $this->comic->devolverStock($detalle['IdComic'], -$diferencia);
                }
            }

            $this->detalle->actualizarCantidad($detalle['IdDetalle'], $nuevaCantidad);

            $this->pedido->recalcularTotal($detalle['IdPedido']);

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $this->detalle->obtenerPorId($detalle['IdDetalle']);
    }

    public function eliminar(array $detalle): void
    {
        $pedido = $this->obtenerPedidoPendiente($detalle['IdPedido']);

        $nuevoTotal = $pedido['Total'] - $detalle['Subtotal'];
        $this->validarAdelanto($pedido, $nuevoTotal);

        $this->db->beginTransaction();

        try {
            if ($pedido['TipoPedido'] === 'VENTA') {
                $this->comic->devolverStock($detalle['IdComic'], $detalle['Cantidad']);
            }

            $this->detalle->eliminar($detalle['IdDetalle']);

            $this->pedido->recalcularTotal($detalle['IdPedido']);

            $this->db->commit();

        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function obtenerPedidoPendiente(int $idPedido): array
    {
        $pedido = $this->pedido->obtenerPorId($idPedido);

        if (!$pedido) {
            throw new Exception('El pedido indicado no existe', 400);
        }

        if ($pedido['Estado'] !== 'PENDIENTE') {
            throw new Exception('Solo se puede modificar un pedido PENDIENTE (estado actual: ' . $pedido['Estado'] . ')', 409);
        }

        return $pedido;
    }

    private function validarAdelanto(array $pedido, $nuevoTotal): void
    {
        if ($nuevoTotal < $pedido['Adelanto']) {
            throw new Exception('El total del pedido no puede quedar menor al adelanto registrado (S/ ' . $pedido['Adelanto'] . ')', 409);
        }
    }
}
