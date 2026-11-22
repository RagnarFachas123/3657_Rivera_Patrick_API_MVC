<?php

namespace Config;

use Core\Env;
use PDO;
use PDOException;

class Database
{
    public function connect(): PDO
    {
        // datos tomados del archivo .env
        $host     = Env::get('DB_HOST', 'localhost');
        $database = Env::get('DB_NAME', 'GODComicsDB');
        $username = Env::get('DB_USER');
        $password = Env::get('DB_PASSWORD');

        try {

            // conexión a sql server
            $dsn = "sqlsrv:Server={$host};Database={$database};TrustServerCertificate=1";

            // si no hay usuario se usa la autenticación de windows
            if ($username === '') {
                $connection = new PDO($dsn);
            } else {
                $connection = new PDO($dsn, $username, $password);
            }

            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            return $connection;

        } catch (PDOException $e) {

            throw new PDOException('Error al conectar con la base de datos.');
        }
    }
}
