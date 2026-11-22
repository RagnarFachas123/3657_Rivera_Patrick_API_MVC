<?php

namespace App\Models;

use PDO;

class DetallePedido
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
                d.IdDetalle,
                d.IdPedido,
                d.IdComic,
                c.Titulo,
                d.Cantidad,
                d.PrecioUnitario,
                d.Cantidad * d.PrecioUnitario AS Subtotal
            FROM DetallePedido d
            INNER JOIN Comic c ON d.IdComic = c.IdComic
            ORDER BY d.IdPedido, d.IdDetalle
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT
                d.IdDetalle,
                d.IdPedido,
                d.IdComic,
                c.Titulo,
                d.Cantidad,
                d.PrecioUnitario,
                d.Cantidad * d.PrecioUnitario AS Subtotal
            FROM DetallePedido d
            INNER JOIN Comic c ON d.IdComic = c.IdComic
            WHERE d.IdDetalle = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function obtenerPorPedido(int $idPedido): array
    {
        $sql = "
            SELECT
                d.IdDetalle,
                d.IdComic,
                c.Titulo,
                d.Cantidad,
                d.PrecioUnitario,
                d.Cantidad * d.PrecioUnitario AS Subtotal
            FROM DetallePedido d
            INNER JOIN Comic c ON d.IdComic = c.IdComic
            WHERE d.IdPedido = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $idPedido]);

        return $stmt->fetchAll();
    }

    // un comic no se puede repetir en el mismo pedido
    public function existeComicEnPedido(int $idPedido, int $idComic): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM DetallePedido
            WHERE IdPedido = :idPedido AND IdComic = :idComic
        ");

        $stmt->execute([':idPedido' => $idPedido, ':idComic' => $idComic]);

        return $stmt->fetchColumn() > 0;
    }

    public function crear(int $idPedido, int $idComic, int $cantidad, $precio): int
    {
        $sql = "
            INSERT INTO DetallePedido (IdPedido, IdComic, Cantidad, PrecioUnitario)
            OUTPUT INSERTED.IdDetalle
            VALUES (:idPedido, :idComic, :cantidad, :precio)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':idPedido' => $idPedido,
            ':idComic' => $idComic,
            ':cantidad' => $cantidad,
            ':precio' => $precio
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizarCantidad(int $id, int $cantidad): bool
    {
        $stmt = $this->db->prepare("UPDATE DetallePedido SET Cantidad = :cantidad WHERE IdDetalle = :id");

        return $stmt->execute([':cantidad' => $cantidad, ':id' => $id]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM DetallePedido WHERE IdDetalle = :id");

        return $stmt->execute([':id' => $id]);
    }
}
