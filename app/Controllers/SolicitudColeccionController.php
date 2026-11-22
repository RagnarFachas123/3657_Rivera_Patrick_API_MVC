<?php

namespace App\Controllers;

use App\Models\Cliente;
use App\Models\SolicitudColeccion;
use App\Models\Usuario;
use Core\Request;
use Core\Response;
use PDO;

class SolicitudColeccionController
{
    private SolicitudColeccion $solicitud;

    private Cliente $cliente;

    private Usuario $usuario;

    public function __construct(PDO $db)
    {
        $this->solicitud = new SolicitudColeccion($db);
        $this->cliente = new Cliente($db);
        $this->usuario = new Usuario($db);
    }

    // GET /api/solicitudes
    public function index(): void
    {
        Response::success(
            $this->solicitud->obtenerTodos(),
            'Solicitudes obtenidas correctamente'
        );
    }

    // GET /api/solicitudes/{id}
    public function show(int $id): void
    {
        $solicitud = $this->solicitud->obtenerPorId($id);

        if (!$solicitud) {
            Response::error('Solicitud no encontrada', 404);
        }

        Response::success($solicitud, 'Solicitud obtenida correctamente');
    }

    // POST /api/solicitudes
    public function store(): void
    {
        $data = $this->prepararDatos(Request::body());

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        $id = $this->solicitud->crear($data);

        Response::success(
            $this->solicitud->obtenerPorId($id),
            'Solicitud registrada correctamente',
            201
        );
    }

    // PUT /api/solicitudes/{id}
    public function update(int $id): void
    {
        if (!$this->solicitud->obtenerPorId($id)) {
            Response::error('Solicitud no encontrada', 404);
        }

        $data = $this->prepararDatos(Request::body());

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        $this->solicitud->actualizar($id, $data);

        Response::success(
            $this->solicitud->obtenerPorId($id),
            'Solicitud actualizada correctamente'
        );
    }

    // DELETE /api/solicitudes/{id}
    public function destroy(int $id): void
    {
        if (!$this->solicitud->obtenerPorId($id)) {
            Response::error('Solicitud no encontrada', 404);
        }

        $this->solicitud->eliminar($id);

        Response::success(null, 'Solicitud eliminada correctamente');
    }

    private function prepararDatos(array $data): array
    {
        $data['Estado'] = strtoupper($data['Estado'] ?? 'RECIBIDA');

        // vacios se guardan como NULL
        if (empty($data['IdUsuario'])) {
            $data['IdUsuario'] = null;
        }

        if (!isset($data['MontoOfertado']) || $data['MontoOfertado'] === '') {
            $data['MontoOfertado'] = null;
        }

        return $data;
    }

    private function validar(array $data): array
    {
        $errores = [];

        $estados = ['RECIBIDA', 'OFERTADA', 'ACEPTADA', 'RECHAZADA'];

        if (empty($data['IdCliente']) || filter_var($data['IdCliente'], FILTER_VALIDATE_INT) === false) {
            $errores['IdCliente'] = 'El cliente es obligatorio y debe ser un número entero';
        } elseif (!$this->cliente->obtenerPorId($data['IdCliente'])) {
            $errores['IdCliente'] = 'El cliente indicado no existe';
        }

        // el tasador es opcional, pero si se envia debe tener rol TASADOR
        if ($data['IdUsuario'] !== null) {
            if (filter_var($data['IdUsuario'], FILTER_VALIDATE_INT) === false) {
                $errores['IdUsuario'] = 'El tasador debe ser un número entero';
            } else {
                $tasador = $this->usuario->obtenerPorId($data['IdUsuario']);

                if (!$tasador) {
                    $errores['IdUsuario'] = 'El tasador indicado no existe';
                } elseif ($tasador['Rol'] !== 'TASADOR') {
                    $errores['IdUsuario'] = 'El usuario indicado no tiene rol TASADOR';
                }
            }
        }

        if (empty($data['Descripcion'])) {
            $errores['Descripcion'] = 'La descripción es obligatoria';
        } elseif (strlen($data['Descripcion']) > 200) {
            $errores['Descripcion'] = 'La descripción debe tener máximo 200 caracteres';
        }

        if (!isset($data['CantidadComics']) || filter_var($data['CantidadComics'], FILTER_VALIDATE_INT) === false || $data['CantidadComics'] <= 0) {
            $errores['CantidadComics'] = 'La cantidad de cómics debe ser un número entero mayor a 0';
        }

        if ($data['MontoOfertado'] !== null && (!is_numeric($data['MontoOfertado']) || $data['MontoOfertado'] <= 0)) {
            $errores['MontoOfertado'] = 'El monto ofertado debe ser un número mayor a 0';
        }

        if (!in_array($data['Estado'], $estados)) {
            $errores['Estado'] = 'El estado debe ser RECIBIDA, OFERTADA, ACEPTADA o RECHAZADA';
        }

        // para ofertar o aceptar ya debe haber monto y tasador
        if (in_array($data['Estado'], ['OFERTADA', 'ACEPTADA'])) {
            if ($data['MontoOfertado'] === null) {
                $errores['MontoOfertado'] = 'Para el estado ' . $data['Estado'] . ' el monto ofertado es obligatorio';
            }
            if ($data['IdUsuario'] === null) {
                $errores['IdUsuario'] = 'Para el estado ' . $data['Estado'] . ' se debe asignar un tasador';
            }
        }

        return $errores;
    }
}
