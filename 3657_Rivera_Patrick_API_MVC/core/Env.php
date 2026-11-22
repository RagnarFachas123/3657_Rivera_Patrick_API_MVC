<?php

namespace Core;

// Lee el archivo .env (CLAVE=valor) para no escribir
// la contraseña de la base de datos dentro del código

class Env
{
    public static function cargar(string $ruta): void
    {
        if (!file_exists($ruta)) {
            return;
        }

        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {

            $linea = trim($linea);

            // saltar comentarios
            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            $partes = explode('=', $linea, 2);

            if (count($partes) == 2) {
                $_ENV[trim($partes[0])] = trim($partes[1]);
            }
        }
    }

    public static function get(string $clave, string $porDefecto = ''): string
    {
        return $_ENV[$clave] ?? $porDefecto;
    }
}
