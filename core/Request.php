<?php

namespace Core;

class Request
{
    public static function body(): array
    {
        $input = file_get_contents('php://input');

        if (trim($input) === '') {
            return [];
        }

        $data = json_decode($input, true);

        // si el json esta mal escrito
        if (!is_array($data)) {
            Response::error('El JSON enviado no es válido', 400);
        }

        return $data;
    }
}
