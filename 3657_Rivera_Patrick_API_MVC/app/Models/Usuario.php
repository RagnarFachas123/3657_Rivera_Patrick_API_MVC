<?php

namespace App\Models;

use PDO;

class Usuario
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // la Clave nunca se devuelve en las consultas

    public function obtenerTodos(): array
    {
        $stmt = $this->db->prepare("
            SELECT IdUsuario, NombreCompleto, Usuario, Rol, Estado
            FROM Usuario
            ORDER BY NombreCompleto
        ");

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT IdUsuario, NombreCompleto, Usuario, Rol, Estado
            FROM Usuario
            WHERE IdUsuario = :id
        ");

        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function existeUsuario(string $usuario, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM Usuario
            WHERE Usuario = :usuario AND IdUsuario <> :id
        ");

        $stmt->execute([':usuario' => $usuario, ':id' => $excluirId]);

        return $stmt->fetchColumn() > 0;
    }

    // pedidos y solicitudes donde participa el usuario
    public function contarRelaciones(int $id): int
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM Pedido WHERE IdUsuario = :id1) +
                (SELECT COUNT(*) FROM SolicitudColeccion WHERE IdUsuario = :id2)
        ");

        $stmt->execute([':id1' => $id, ':id2' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function crear(array $data): int
    {
        $sql = "
            INSERT INTO Usuario (NombreCompleto, Usuario, Clave, Rol, Estado)
            OUTPUT INSERTED.IdUsuario
            VALUES (:nombre, :usuario, :clave, :rol, :estado)
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nombre' => $data['NombreCompleto'],
            ':usuario' => $data['Usuario'],
            ':clave' => $this->encriptar($data['Clave']),
            ':rol' => $data['Rol'],
            ':estado' => $data['Estado'] ?? 1
        ]);

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $id, array $data): bool
    {
        $sql = "
            UPDATE Usuario
            SET NombreCompleto = :nombre,
                Usuario = :usuario,
                Rol = :rol,
                Estado = :estado
            WHERE IdUsuario = :id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':nombre' => $data['NombreCompleto'],
            ':usuario' => $data['Usuario'],
            ':rol' => $data['Rol'],
            ':estado' => $data['Estado'] ?? 1,
            ':id' => $id
        ]);

        // la clave solo se cambia si la envian
        if (!empty($data['Clave'])) {
            $stmt = $this->db->prepare("UPDATE Usuario SET Clave = :clave WHERE IdUsuario = :id");
            $stmt->execute([':clave' => $this->encriptar($data['Clave']), ':id' => $id]);
        }

        return true;
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Usuario WHERE IdUsuario = :id");

        return $stmt->execute([':id' => $id]);
    }

    // mismo formato que HASHBYTES('SHA2_256', ...) del script SQL
    private function encriptar(string $clave): string
    {
        return strtoupper(hash('sha256', $clave));
    }
}
