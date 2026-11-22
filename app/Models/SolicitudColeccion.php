<?php

namespace App\Models;

use PDO;

class SolicitudColeccion
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerTodos(): array
    {
        // LEFT JOIN porque el tasador puede ser NULL (aun no asignado)
        $sql = "
            SELECT
                s.IdSolicitud,
                s.IdCliente,
                c.Nombres AS NombreCliente,
                c.Apellidos AS ApellidoCliente,
                s.IdUsuario,
                u.NombreCompleto AS Tasador,
                s.FechaSolicitud,
                s.Descripcion,
                s.CantidadComics,
                s.MontoOfertado,
                s.Estado
            FROM SolicitudColeccion s
            INNER JOIN Cliente c ON s.IdCliente = c.IdCliente
            LEFT JOIN Usuario u ON s.IdUsuario = u.IdUsuario
            ORDER BY s.IdSolicitud DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $sql = "
            SELECT
                s.IdSolicitud,
                s.IdCliente,
                c.Nombres AS NombreCliente,
                c.Apellidos AS ApellidoCliente,
                s.IdUsuario,
                u.NombreCompleto AS Tasador,
                s.FechaSolicitud,
                s.Descripcion,
                s.CantidadComics,
                s.MontoOfertado,
                s.Estado
            FROM SolicitudColeccion s
            INNER JOIN Cliente c ON s.IdCliente = c.IdCliente
            LEFT JOIN Usuario u ON s.IdUsuario = u.IdUsuario
            WHERE s.IdSolicitud = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO SolicitudColeccion (IdCliente, IdUsuario, Descripcion, CantidadComics, MontoOfertado, Estado)
            OUTPUT INSERTED.IdSolicitud
            VALUES (:idCliente, :idUsuario, :descripcion, :cantidad, :monto, :estado)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':idCliente' => $data['IdCliente'],
            ':idUsuario' => $data['IdUsuario'] ?? null,
            ':descripcion' => $data['Descripcion'],
            ':cantidad' => $data['CantidadComics'],
            ':monto' => $data['MontoOfertado'] ?? null,
            ':estado' => $data['Estado'] ?? 'RECIBIDA'
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE SolicitudColeccion
            SET IdCliente = :idCliente,
                IdUsuario = :idUsuario,
                Descripcion = :descripcion,
                CantidadComics = :cantidad,
                MontoOfertado = :monto,
                Estado = :estado
            WHERE IdSolicitud = :id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':idCliente' => $data['IdCliente'],
            ':idUsuario' => $data['IdUsuario'] ?? null,
            ':descripcion' => $data['Descripcion'],
            ':cantidad' => $data['CantidadComics'],
            ':monto' => $data['MontoOfertado'] ?? null,
            ':estado' => $data['Estado'] ?? 'RECIBIDA',
            ':id' => $id
        ]);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM SolicitudColeccion WHERE IdSolicitud = :id");

        return $stmt->execute([':id' => $id]);
    }
}
