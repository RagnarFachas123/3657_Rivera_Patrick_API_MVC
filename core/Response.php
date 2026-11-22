<?php

// Todas las APIs responden de la misma manera

namespace Core;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        exit;
    }

    public static function success(mixed $data = null, string $message = 'Operación exitosa', int $status = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    // $errores es opcional: lista de campos con error
    public static function error(string $message, int $status = 400, array $errores = []): void
    {
        $respuesta = [
            'success' => false,
            'message' => $message
        ];

        if (count($errores) > 0) {
            $respuesta['errores'] = $errores;
        }

        self::json($respuesta, $status);
    }
}
