<?php

namespace App\Models;

use PDO;

class Editorial
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
                e.IdEditorial,
                e.Nombre,
                e.Pais,
                e.Estado,
                (SELECT COUNT(*) FROM Comic c WHERE c.IdEditorial = e.IdEditorial) AS TotalComics
            FROM Editorial e
            ORDER BY e.Nombre
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT IdEditorial, Nombre, Pais, Estado
            FROM Editorial
            WHERE IdEditorial = :id
        ");

        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function existeNombre(string $nombre, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM Editorial
            WHERE Nombre = :nombre AND IdEditorial <> :id
        ");

        $stmt->execute([':nombre' => $nombre, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function contarComics(int $id): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Comic WHERE IdEditorial = :id");
        $stmt->execute([':id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function crear(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO Editorial (Nombre, Pais, Estado)
            OUTPUT INSERTED.IdEditorial
            VALUES (:nombre, :pais, :estado)
        ");

        $stmt->execute([
            ':nombre' => $data['Nombre'],
            ':pais'   => $data['Pais'],
            ':estado' => (int) ($data['Estado'] ?? 1),
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE Editorial
            SET Nombre = :nombre,
                Pais   = :pais,
                Estado = :estado
            WHERE IdEditorial = :id
        ");

        return $stmt->execute([
            ':nombre' => $data['Nombre'],
            ':pais'   => $data['Pais'],
            ':estado' => (int) ($data['Estado'] ?? 1),
            ':id'     => $id,
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Editorial WHERE IdEditorial = :id");

        return $stmt->execute([':id' => $id]);
    }
}
