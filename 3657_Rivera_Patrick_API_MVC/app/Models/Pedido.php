<?php

namespace App\Models;

use PDO;

class Pedido
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerTodos(): array
    {
        $sql = "
            SELECT
                p.IdPedido,
                p.IdCliente,
                c.Nombres AS NombreCliente,
                c.Apellidos AS ApellidoCliente,
                p.IdUsuario,
                u.NombreCompleto AS Vendedor,
                p.FechaPedido,
                p.TipoPedido,
                p.Estado,
                p.Total,
                p.Adelanto,
                p.Total - p.Adelanto AS Saldo
            FROM Pedido p
            INNER JOIN Cliente c ON p.IdCliente = c.IdCliente
            INNER JOIN Usuario u ON p.IdUsuario = u.IdUsuario
            ORDER BY p.IdPedido DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT
                p.IdPedido,
                p.IdCliente,
                c.Nombres AS NombreCliente,
                c.Apellidos AS ApellidoCliente,
                p.IdUsuario,
                u.NombreCompleto AS Vendedor,
                p.FechaPedido,
                p.TipoPedido,
                p.Estado,
                p.Total,
                p.Adelanto,
                p.Total - p.Adelanto AS Saldo
            FROM Pedido p
            INNER JOIN Cliente c ON p.IdCliente = c.IdCliente
            INNER JOIN Usuario u ON p.IdUsuario = u.IdUsuario
            WHERE p.IdPedido = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        // el pedido empieza PENDIENTE con total 0,
        // el total se calcula cuando se agregan los detalles
        $sql = "
            INSERT INTO Pedido (IdCliente, IdUsuario, TipoPedido)
            OUTPUT INSERTED.IdPedido
            VALUES (:idCliente, :idUsuario, :tipo)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':idCliente' => $data['IdCliente'],
            ':idUsuario' => $data['IdUsuario'],
            ':tipo' => $data['TipoPedido']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE Pedido
            SET IdCliente = :idCliente,
                IdUsuario = :idUsuario,
                TipoPedido = :tipo,
                Estado = :estado,
                Adelanto = :adelanto
            WHERE IdPedido = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':idCliente' => $data['IdCliente'],
            ':idUsuario' => $data['IdUsuario'],
            ':tipo' => $data['TipoPedido'],
            ':estado' => $data['Estado'],
            ':adelanto' => $data['Adelanto'],
            ':id' => $id
        ]);
    }

    // suma los subtotales de los detalles y lo guarda como total
    public function recalcularTotal(int $id): void
    {
        $sql = "
            UPDATE Pedido
            SET Total = COALESCE((SELECT SUM(Cantidad * PrecioUnitario)
                                  FROM DetallePedido
                                  WHERE IdPedido = :id1), 0)
            WHERE IdPedido = :id2
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id1' => $id, ':id2' => $id]);
    }

    public function eliminar(int $id): bool
    {
        // primero los detalles y luego el pedido
        $stmt = $this->db->prepare("DELETE FROM DetallePedido WHERE IdPedido = :id");
        $stmt->execute([':id' => $id]);

        $stmt = $this->db->prepare("DELETE FROM Pedido WHERE IdPedido = :id");

        return $stmt->execute([':id' => $id]);
    }
}
