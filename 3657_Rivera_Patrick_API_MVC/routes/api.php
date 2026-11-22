<?php

use App\Controllers\EditorialController;
use App\Controllers\ComicController;
use App\Controllers\ClienteController;
use App\Controllers\UsuarioController;
use App\Controllers\PedidoController;
use App\Controllers\DetallePedidoController;
use App\Controllers\SolicitudColeccionController;
use Core\Response;

// ==========================================================
// INSTANCIAR CONTROLLERS
// ==========================================================

$editorialController = new EditorialController($db);

$comicController = new ComicController($db);

$clienteController = new ClienteController($db);

$usuarioController = new UsuarioController($db);

$pedidoController = new PedidoController($db);

$detalleController = new DetallePedidoController($db);

$solicitudController = new SolicitudColeccionController($db);


// ==========================================================
// INICIO
// ==========================================================

$router->get('/', function () {
    Response::success([
        'api' => 'GOD Comics API',
        'modulos' => ['/api/editoriales', '/api/comics', '/api/clientes', '/api/usuarios', '/api/pedidos', '/api/detalles', '/api/solicitudes']
    ], 'API funcionando correctamente');
});


// ==========================================================
// EDITORIALES
// ==========================================================

$router->get('/api/editoriales', [$editorialController, 'index']);

$router->get('/api/editoriales/{id}', [$editorialController, 'show']);

$router->post('/api/editoriales', [$editorialController, 'store']);

$router->put('/api/editoriales/{id}', [$editorialController, 'update']);

$router->delete('/api/editoriales/{id}', [$editorialController, 'destroy']);


// ==========================================================
// CÓMICS
// ==========================================================

$router->get('/api/comics', [$comicController, 'index']);

$router->get('/api/comics/{id}', [$comicController, 'show']);

$router->post('/api/comics', [$comicController, 'store']);

$router->put('/api/comics/{id}', [$comicController, 'update']);

$router->delete('/api/comics/{id}', [$comicController, 'destroy']);


// ==========================================================
// CLIENTES
// ==========================================================

$router->get('/api/clientes', [$clienteController, 'index']);

$router->get('/api/clientes/{id}', [$clienteController, 'show']);

$router->post('/api/clientes', [$clienteController, 'store']);

$router->put('/api/clientes/{id}', [$clienteController, 'update']);

$router->delete('/api/clientes/{id}', [$clienteController, 'destroy']);


// ==========================================================
// USUARIOS
// ==========================================================

$router->get('/api/usuarios', [$usuarioController, 'index']);

$router->get('/api/usuarios/{id}', [$usuarioController, 'show']);

$router->post('/api/usuarios', [$usuarioController, 'store']);

$router->put('/api/usuarios/{id}', [$usuarioController, 'update']);

$router->delete('/api/usuarios/{id}', [$usuarioController, 'destroy']);


// ==========================================================
// PEDIDOS
// ==========================================================

$router->get('/api/pedidos', [$pedidoController, 'index']);

$router->get('/api/pedidos/{id}', [$pedidoController, 'show']);

$router->post('/api/pedidos', [$pedidoController, 'store']);

$router->put('/api/pedidos/{id}', [$pedidoController, 'update']);

$router->delete('/api/pedidos/{id}', [$pedidoController, 'destroy']);


// ==========================================================
// DETALLE DE PEDIDOS
// ==========================================================

$router->get('/api/detalles', [$detalleController, 'index']);

$router->get('/api/detalles/{id}', [$detalleController, 'show']);

$router->post('/api/detalles', [$detalleController, 'store']);

$router->put('/api/detalles/{id}', [$detalleController, 'update']);

$router->delete('/api/detalles/{id}', [$detalleController, 'destroy']);


// ==========================================================
// SOLICITUDES DE COLECCIÓN (Vende tu colección)
// ==========================================================

$router->get('/api/solicitudes', [$solicitudController, 'index']);

$router->get('/api/solicitudes/{id}', [$solicitudController, 'show']);

$router->post('/api/solicitudes', [$solicitudController, 'store']);

$router->put('/api/solicitudes/{id}', [$solicitudController, 'update']);

$router->delete('/api/solicitudes/{id}', [$solicitudController, 'destroy']);
