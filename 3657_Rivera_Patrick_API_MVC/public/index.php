<?php

// =====================================
// AUTOLOAD
// carga las clases segun su carpeta (App, Config, Core)
// =====================================

spl_autoload_register(function ($clase) {
    $carpetas = [
        'App\\'    => __DIR__ . '/../app/',
        'Config\\' => __DIR__ . '/../config/',
        'Core\\'   => __DIR__ . '/../core/',
    ];

    foreach ($carpetas as $prefijo => $carpeta) {
        if (str_starts_with($clase, $prefijo)) {
            $archivo = $carpeta . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
            if (file_exists($archivo)) {
                require_once $archivo;
            }
        }
    }
});

use App\Middleware\CorsMiddleware;
use Config\Database;
use Core\Env;
use Core\Response;
use Core\Router;

// =====================================
// VARIABLES DE ENTORNO (.env)
// =====================================

Env::cargar(__DIR__ . '/../.env');

// no mostrar errores de php en la respuesta
ini_set('display_errors', '0');

// =====================================
// CORS
// =====================================

CorsMiddleware::handle();

// =====================================
// CONEXIÓN
// =====================================

try {
    $database = new Database();
    $db = $database->connect();
} catch (Throwable $e) {
    Response::error('No se pudo conectar con la base de datos', 500);
}

// =====================================
// ROUTER
// =====================================

$router = new Router();

require_once __DIR__ . '/../routes/api.php';

// =====================================
// MÉTODO HTTP Y URI
// =====================================

$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// quitar la carpeta del proyecto si se usa XAMPP
// ej: /3657_Rivera_Patrick_API_MVC/public/api/comics -> /api/comics
$basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));

if ($basePath !== '/' && str_starts_with($uri, $basePath)) {
    $uri = substr($uri, strlen($basePath));
}

if ($uri === '') {
    $uri = '/';
}

// =====================================
// EJECUTAR ROUTER
// =====================================

try {

    $router->dispatch($method, $uri);

} catch (PDOException $e) {

    // 547 = conflicto con FOREIGN KEY o CHECK
    // 2627 / 2601 = dato duplicado (UNIQUE)
    $codigo = $e->errorInfo[1] ?? 0;

    if ($codigo == 547) {
        Response::error('La operación no respeta las relaciones o restricciones de la base de datos', 409);
    }

    if ($codigo == 2627 || $codigo == 2601) {
        Response::error('Ya existe un registro con ese dato', 409);
    }

    Response::error('Error interno en la base de datos', 500);

} catch (Throwable $e) {

    Response::error('Error interno del servidor', 500);
}
