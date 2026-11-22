# GOD Comics - API REST MVC

**Alumno:** Patrick Rivera
**Curso:** 3657 - Programación Web Básica
**Motor:** SQL Server
**Lenguaje:** PHP 8 (sin frameworks, basado en el proyecto del docente)

API para la tienda GOD Comics (venta, importación a pedido y compra de cómics de colección).
Tiene un módulo CRUD por cada tabla de la base de datos de la PA3 (7 tablas = 7 módulos).

---

## Estructura del proyecto

```
3657_Rivera_Patrick_API_MVC/
├── app/
│   ├── Controllers/     → reciben la petición, validan y responden
│   ├── Models/          → ejecutan las consultas SQL
│   ├── Services/        → reglas de negocio de pedidos (stock y totales)
│   └── Middleware/      → CORS
├── config/
│   └── Database.php     → conexión a SQL Server (lee el .env)
├── core/
│   ├── Env.php          → lee las variables del archivo .env
│   ├── Request.php      → lee el JSON que llega
│   ├── Response.php     → todas las respuestas con el mismo formato
│   └── Router.php       → busca la ruta y llama al controller
├── routes/
│   └── api.php          → todos los endpoints
├── public/
│   ├── index.php        → archivo de inicio (todas las peticiones entran aquí)
│   └── .htaccess
├── database/
│   └── GODComics_BD.sql → script de la base de datos con datos de prueba
├── postman/
│   └── GODComics_API.postman_collection.json
├── evidencias/          → capturas de las pruebas
├── .env.example         → plantilla de la conexión
└── .gitignore
```

## Endpoints

Cada módulo tiene las 5 operaciones:

| Método | Ruta | Acción |
|---|---|---|
| GET | `/api/{modulo}` | Listar |
| GET | `/api/{modulo}/{id}` | Obtener por ID |
| POST | `/api/{modulo}` | Crear |
| PUT | `/api/{modulo}/{id}` | Actualizar |
| DELETE | `/api/{modulo}/{id}` | Eliminar |

| Módulo | Tabla |
|---|---|
| `editoriales` | Editorial |
| `comics` | Comic |
| `clientes` | Cliente |
| `usuarios` | Usuario |
| `pedidos` | Pedido |
| `detalles` | DetallePedido |
| `solicitudes` | SolicitudColeccion |

## Códigos HTTP que usa la API

| Código | Cuándo |
|---|---|
| 200 | Operación correcta (listar, obtener, actualizar, eliminar) |
| 201 | Registro creado |
| 400 | Datos inválidos o JSON mal escrito (devuelve qué campo falló) |
| 404 | El registro o la ruta no existe |
| 409 | Conflicto: dato duplicado, registro relacionado (FK), stock insuficiente, pedido cerrado |
| 500 | Error interno (sin mostrar datos sensibles) |

Formato de las respuestas:

```json
{ "success": true, "message": "Cómic registrado correctamente", "data": { ... } }
{ "success": false, "message": "Datos inválidos", "errores": { "DNI": "El DNI debe tener exactamente 8 números" } }
```

## Reglas importantes

- **Usuario:** la clave se guarda encriptada con SHA-256 (igual que `HASHBYTES` del script) y nunca se devuelve.
- **Comic:** el precio solo puede ir vacío si la disponibilidad es `COLECCION`.
- **DetallePedido:** hace lo mismo que el procedimiento `sp_AgregarComicPedido`: el pedido debe estar `PENDIENTE`, si es `VENTA` descuenta stock, no se repite un cómic en el mismo pedido y recalcula el total. Usa transacciones.
- **Pedido:** si se cancela una venta el stock regresa al catálogo (como `sp_CambiarEstadoPedido`). Un pedido `ENTREGADO` o `CANCELADO` ya no se modifica.
- **Eliminar:** no se puede eliminar un registro que tenga datos relacionados (por ejemplo una editorial con cómics), responde 409.

---

## Cómo ejecutarlo

### 1. Base de datos
Abrir `database/GODComics_BD.sql` en SQL Server Management Studio y ejecutarlo completo.

### 2. PHP con el driver de SQL Server
Se necesita PHP 8.1 o superior (por ejemplo el de XAMPP) con la extensión `pdo_sqlsrv`:

1. Descargar **Microsoft Drivers for PHP for SQL Server** y copiar a `C:\xampp\php\ext` el archivo que corresponde a tu versión de PHP
   (ej. PHP 8.2 de XAMPP → `php_pdo_sqlsrv_82_ts_x64.dll`).
2. En `C:\xampp\php\php.ini` agregar: `extension=php_pdo_sqlsrv_82_ts_x64.dll`
3. Instalar **Microsoft ODBC Driver 18 for SQL Server**.
4. Comprobar con `php -m` que aparece `pdo_sqlsrv`.

### 3. Configurar la conexión
Copiar `.env.example` como `.env` y poner el nombre del servidor:

```
DB_HOST=localhost\SQLEXPRESS
DB_NAME=GODComicsDB
DB_USER=
DB_PASSWORD=
```

(si `DB_USER` está vacío se usa Autenticación de Windows)

### 4. Levantar el servidor
```
cd public
php -S localhost:8000
```

Abrir `http://localhost:8000` → debe responder *"API funcionando correctamente"*.

### 5. Pruebas en Postman
1. Postman → **Import** → `postman/GODComics_API.postman_collection.json`
2. Ejecutar la colección completa con **Run collection** (en orden).
3. Cada request ya tiene sus tests (código HTTP esperado), incluye casos correctos y casos de error (400, 404, 409).
