<?php

namespace App\Models;

use PDO;

class Cliente
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function obtenerTodos(): array
    {
        $stmt = $this->db->prepare("
            SELECT IdCliente, Nombres, Apellidos, DNI, Email, Telefono, Distrito, FechaRegistro
            FROM Cliente
            ORDER BY Apellidos, Nombres
        ");

        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function obtenerPorId(int $id): array|false
    {
        $stmt = $this->db->prepare("
            SELECT IdCliente, Nombres, Apellidos, DNI, Email, Telefono, Distrito, FechaRegistro
            FROM Cliente
            WHERE IdCliente = :id
        ");

        $stmt->execute([':id' => $id]);

        return $stmt->fetch();
    }

    public function existeDni(string $dni, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Cliente WHERE DNI = :dni AND IdCliente <> :id");
        $stmt->execute([':dni' => $dni, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function existeEmail(string $email, int $excluirId = 0): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM Cliente WHERE Email = :email AND IdCliente <> :id");
        $stmt->execute([':email' => $email, ':id' => $excluirId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function contarRelaciones(int $id): int
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM Pedido WHERE IdCliente = :id1) +
                (SELECT COUNT(*) FROM SolicitudColeccion WHERE IdCliente = :id2)
        ");

        $stmt->execute([':id1' => $id, ':id2' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function crear(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO Cliente (Nombres, Apellidos, DNI, Email, Telefono, Distrito)
            OUTPUT INSERTED.IdCliente
            VALUES (:nombres, :apellidos, :dni, :email, :telefono, :distrito)
        ");

        $stmt->execute($this->parametros($data));

        return (int) $stmt->fetchColumn();
    }

    public function actualizar(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE Cliente
            SET Nombres   = :nombres,
                Apellidos = :apellidos,
                DNI       = :dni,
                Email     = :email,
                Telefono  = :telefono,
                Distrito  = :distrito
            WHERE IdCliente = :id
        ");

        $params = $this->parametros($data);
        $params[':id'] = $id;

        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM Cliente WHERE IdCliente = :id");

        return $stmt->execute([':id' => $id]);
    }

    private function parametros(array $data): array
    {
        return [
            ':nombres'   => $data['Nombres'],
            ':apellidos' => $data['Apellidos'],
            ':dni'       => (string) $data['DNI'],
            ':email'     => $data['Email'],
            ':telefono'  => isset($data['Telefono']) && $data['Telefono'] !== '' ? (string) $data['Telefono'] : null,
            ':distrito'  => isset($data['Distrito']) && $data['Distrito'] !== '' ? $data['Distrito'] : null,
        ];
    }
}
