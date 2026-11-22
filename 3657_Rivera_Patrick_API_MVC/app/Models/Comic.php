<?php

namespace App\Models;

use PDO;

class Comic
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
                c.IdComic,
                c.IdEditorial,
                e.Nombre AS Editorial,
                c.Codigo,
                c.Titulo,
                c.Formato,
                c.Anio,
                c.Precio,
                c.Stock,
                c.Disponibilidad
            FROM Comic c
            INNER JOIN Editorial e ON c.IdEditorial = e.IdEditorial
            ORDER BY c.Titulo
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT
                c.IdComic,
                c.IdEditorial,
                e.Nombre AS Editorial,
                c.Codigo,
                c.Titulo,
                c.Formato,
                c.Anio,
                c.Precio,
                c.Stock,
                c.Disponibilidad
            FROM Comic c
            INNER JOIN Editorial e ON c.IdEditorial = e.IdEditorial
            WHERE c.IdComic = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function existeCodigo(string $codigo, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM Comic
            WHERE Codigo = :codigo AND IdComic <> :id
        ");

        $stmt->execute([':codigo' => $codigo, ':id' => $excluirId]);

        return $stmt->fetchColumn() > 0;
    }

    public function contarDetalles(int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM DetallePedido WHERE IdComic = :id");
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO Comic (IdEditorial, Codigo, Titulo, Formato, Anio, Precio, Stock, Disponibilidad)
            OUTPUT INSERTED.IdComic
            VALUES (:idEditorial, :codigo, :titulo, :formato, :anio, :precio, :stock, :disponibilidad)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':idEditorial' => $data['IdEditorial'],
            ':codigo' => $data['Codigo'],
            ':titulo' => $data['Titulo'],
            ':formato' => $data['Formato'],
            ':anio' => $data['Anio'],
            ':precio' => $data['Precio'] ?? null,
            ':stock' => $data['Stock'] ?? 0,
            ':disponibilidad' => $data['Disponibilidad']
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE Comic
            SET IdEditorial = :idEditorial,
                Codigo = :codigo,
                Titulo = :titulo,
                Formato = :formato,
                Anio = :anio,
                Precio = :precio,
                Stock = :stock,
                Disponibilidad = :disponibilidad
            WHERE IdComic = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':idEditorial' => $data['IdEditorial'],
            ':codigo' => $data['Codigo'],
            ':titulo' => $data['Titulo'],
            ':formato' => $data['Formato'],
            ':anio' => $data['Anio'],
            ':precio' => $data['Precio'] ?? null,
            ':stock' => $data['Stock'] ?? 0,
            ':disponibilidad' => $data['Disponibilidad'],
            ':id' => $id
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Comic WHERE IdComic = :id");

        return $stmt->execute([':id' => $id]);
    }

    // ===== STOCK (lo usan los pedidos) =====

    // descuenta solo si hay stock suficiente, si no alcanza devuelve false
    public function descontarStock(int $id, int $cantidad): bool
    {
        $stmt = $this->db->prepare("
            UPDATE Comic
            SET Stock = Stock - :cantidad
            WHERE IdComic = :id AND Stock >= :cantidad2
        ");

        $stmt->execute([':cantidad' => $cantidad, ':id' => $id, ':cantidad2' => $cantidad]);

        return $stmt->rowCount() > 0;
    }

    public function devolverStock(int $id, int $cantidad): void
    {
        $stmt = $this->db->prepare("UPDATE Comic SET Stock = Stock + :cantidad WHERE IdComic = :id");

        $stmt->execute([':cantidad' => $cantidad, ':id' => $id]);
    }
}
