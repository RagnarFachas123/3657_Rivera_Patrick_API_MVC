<?php

namespace App\Controllers;

use App\Models\Comic;
use App\Models\Editorial;
use Core\Request;
use Core\Response;
use PDO;

class ComicController
{
    private Comic $comic;

    private Editorial $editorial;

    public function __construct(PDO $db)
    {
        $this->comic = new Comic($db);
        $this->editorial = new Editorial($db);
    }

    // GET /api/comics
    public function index(): void
    {
        Response::success(
            $this->comic->obtenerTodos(),
            'Cómics obtenidos correctamente'
        );
    }

    // GET /api/comics/{id}
    public function show(int $id): void
    {
        $comic = $this->comic->obtenerPorId($id);

        if (!$comic) {
            Response::error('Cómic no encontrado', 404);
        }

        Response::success($comic, 'Cómic obtenido correctamente');
    }

    // POST /api/comics
    public function store(): void
    {
        $data = $this->prepararDatos(Request::body());

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->comic->existeCodigo($data['Codigo'])) {
            Response::error('Ya existe un cómic con ese código', 409);
        }

        $id = $this->comic->crear($data);

        Response::success(
            $this->comic->obtenerPorId($id),
            'Cómic registrado correctamente',
            201
        );
    }

    // PUT /api/comics/{id}
    public function update(int $id): void
    {
        if (!$this->comic->obtenerPorId($id)) {
            Response::error('Cómic no encontrado', 404);
        }

        $data = $this->prepararDatos(Request::body());

        $errores = $this->validar($data);

        if (count($errores) > 0) {
            Response::error('Datos inválidos', 400, $errores);
        }

        if ($this->comic->existeCodigo($data['Codigo'], $id)) {
            Response::error('Ya existe otro cómic con ese código', 409);
        }

        $this->comic->actualizar($id, $data);

        Response::success(
            $this->comic->obtenerPorId($id),
            'Cómic actualizado correctamente'
        );
    }

    // DELETE /api/comics/{id}
    public function destroy(int $id): void
    {
        if (!$this->comic->obtenerPorId($id)) {
            Response::error('Cómic no encontrado', 404);
        }

        // no se borra si ya esta en algun pedido (FK_Detalle_Comic)
        if ($this->comic->contarDetalles($id) > 0) {
            Response::error('No se puede eliminar: el cómic ya forma parte de uno o más pedidos', 409);
        }

        $this->comic->eliminar($id);

        Response::success(null, 'Cómic eliminado correctamente');
    }

    // pasa a mayusculas los campos que tienen valores fijos
    private function prepararDatos(array $data): array
    {
        if (isset($data['Formato'])) {
            $data['Formato'] = strtoupper($data['Formato']);
        }

        if (isset($data['Disponibilidad'])) {
            $data['Disponibilidad'] = strtoupper($data['Disponibilidad']);
        }

        if (isset($data['Precio']) && $data['Precio'] === '') {
            $data['Precio'] = null;
        }

        return $data;
    }

    private function validar(array $data): array
    {
        $errores = [];

        $formatos = ['GRAPA', 'TPB', 'HARDCOVER'];
        $disponibilidades = ['EN STOCK', 'IMPORTACION', 'COLECCION'];

        if (empty($data['IdEditorial']) || filter_var($data['IdEditorial'], FILTER_VALIDATE_INT) === false) {
            $errores['IdEditorial'] = 'La editorial es obligatoria y debe ser un número entero';
        } elseif (!$this->editorial->obtenerPorId($data['IdEditorial'])) {
            $errores['IdEditorial'] = 'La editorial indicada no existe';
        }

        if (empty($data['Codigo'])) {
            $errores['Codigo'] = 'El código es obligatorio';
        } elseif (strlen($data['Codigo']) > 10) {
            $errores['Codigo'] = 'El código debe tener máximo 10 caracteres';
        }

        if (empty($data['Titulo'])) {
            $errores['Titulo'] = 'El título es obligatorio';
        } elseif (strlen($data['Titulo']) > 100) {
            $errores['Titulo'] = 'El título debe tener máximo 100 caracteres';
        }

        if (empty($data['Formato']) || !in_array($data['Formato'], $formatos)) {
            $errores['Formato'] = 'El formato debe ser GRAPA, TPB o HARDCOVER';
        }

        if (!isset($data['Anio']) || filter_var($data['Anio'], FILTER_VALIDATE_INT) === false) {
            $errores['Anio'] = 'El año es obligatorio y debe ser un número entero';
        } elseif ($data['Anio'] < 1930 || $data['Anio'] > 2030) {
            $errores['Anio'] = 'El año debe estar entre 1930 y 2030';
        }

        if (empty($data['Disponibilidad']) || !in_array($data['Disponibilidad'], $disponibilidades)) {
            $errores['Disponibilidad'] = 'La disponibilidad debe ser EN STOCK, IMPORTACION o COLECCION';
        }

        // el precio puede ser NULL solo si es de COLECCION ("Cotizar")
        if (isset($data['Precio'])) {
            if (!is_numeric($data['Precio']) || $data['Precio'] <= 0) {
                $errores['Precio'] = 'El precio debe ser un número mayor a 0';
            }
        } elseif (($data['Disponibilidad'] ?? '') !== 'COLECCION') {
            $errores['Precio'] = 'El precio es obligatorio (solo puede ir vacío si es de COLECCION)';
        }

        if (isset($data['Stock'])) {
            if (filter_var($data['Stock'], FILTER_VALIDATE_INT) === false || $data['Stock'] < 0) {
                $errores['Stock'] = 'El stock debe ser un número entero mayor o igual a 0';
            }
        }

        return $errores;
    }
}
