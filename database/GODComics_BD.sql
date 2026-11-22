/* =============================================================
   PROYECTO : GOD Comics - Venta, importación a pedido y
              compra de cómics de colección
   ALUMNO   : Patrick Rivera
   CURSO    : 3657 - Programación Web Básica
   MOTOR    : SQL Server
   VERSIÓN  : PA4 - API REST MVC (script de la PA3 actualizado)

   Cambio para la API: los procedimientos almacenados ahora
   relanzan el error con THROW además de mostrarlo con PRINT,
   así cualquier aplicación que los llame recibe el error.

   TABLAS (7):
     Editorial, Comic, Cliente, Usuario, Pedido,
     DetallePedido, SolicitudColeccion

   RELACIONES:
     Editorial  1 --- N  Comic
     Cliente    1 --- N  Pedido
     Usuario    1 --- N  Pedido              (vendedor que atiende)
     Pedido     1 --- N  DetallePedido
     Comic      1 --- N  DetallePedido
     Cliente    1 --- N  SolicitudColeccion
     Usuario    1 --- N  SolicitudColeccion  (tasador que evalúa)
   ============================================================= */

-- =============================================================
-- 1. CREACIÓN DE LA BASE DE DATOS
-- =============================================================
USE master;
GO

IF DB_ID('GODComicsDB') IS NOT NULL
BEGIN
    ALTER DATABASE GODComicsDB SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
    DROP DATABASE GODComicsDB;
END
GO

CREATE DATABASE GODComicsDB;
GO

USE GODComicsDB;
GO

-- =============================================================
-- 2. CREACIÓN DE TABLAS
-- =============================================================

-- -------------------------------------------------------------
-- Tabla 1: Editorial (DC, Marvel)
-- -------------------------------------------------------------
CREATE TABLE Editorial (
    IdEditorial  INT IDENTITY(1,1) NOT NULL,
    Nombre       VARCHAR(50)  NOT NULL,
    Pais         VARCHAR(50)  NOT NULL,
    Estado       BIT          NOT NULL DEFAULT 1,          -- 1 = activo, 0 = inactivo
    CONSTRAINT PK_Editorial PRIMARY KEY (IdEditorial),
    CONSTRAINT UQ_Editorial_Nombre UNIQUE (Nombre)
);
GO

-- -------------------------------------------------------------
-- Tabla 2: Comic (catálogo de productos)
-- Precio NULL = "Cotizar" (solo para ediciones de colección)
-- -------------------------------------------------------------
CREATE TABLE Comic (
    IdComic         INT IDENTITY(1,1) NOT NULL,
    IdEditorial     INT           NOT NULL,
    Codigo          VARCHAR(10)   NOT NULL,                -- código interno, ej. DC-001
    Titulo          VARCHAR(100)  NOT NULL,
    Formato         VARCHAR(15)   NOT NULL,
    Anio            INT           NOT NULL,
    Precio          DECIMAL(10,2) NULL,
    Stock           INT           NOT NULL DEFAULT 0,
    Disponibilidad  VARCHAR(15)   NOT NULL,
    CONSTRAINT PK_Comic PRIMARY KEY (IdComic),
    CONSTRAINT UQ_Comic_Codigo UNIQUE (Codigo),
    CONSTRAINT FK_Comic_Editorial FOREIGN KEY (IdEditorial) REFERENCES Editorial(IdEditorial),
    CONSTRAINT CK_Comic_Formato CHECK (Formato IN ('GRAPA', 'TPB', 'HARDCOVER')),
    CONSTRAINT CK_Comic_Anio CHECK (Anio BETWEEN 1930 AND 2030),
    CONSTRAINT CK_Comic_Precio CHECK (Precio > 0 OR (Precio IS NULL AND Disponibilidad = 'COLECCION')),
    CONSTRAINT CK_Comic_Stock CHECK (Stock >= 0),
    CONSTRAINT CK_Comic_Disponibilidad CHECK (Disponibilidad IN ('EN STOCK', 'IMPORTACION', 'COLECCION'))
);
GO

-- -------------------------------------------------------------
-- Tabla 3: Cliente
-- -------------------------------------------------------------
CREATE TABLE Cliente (
    IdCliente      INT IDENTITY(1,1) NOT NULL,
    Nombres        VARCHAR(50)  NOT NULL,
    Apellidos      VARCHAR(50)  NOT NULL,
    DNI            CHAR(8)      NOT NULL,
    Email          VARCHAR(100) NOT NULL,
    Telefono       CHAR(9)      NULL,
    Distrito       VARCHAR(50)  NULL,
    FechaRegistro  DATE         NOT NULL DEFAULT GETDATE(),
    CONSTRAINT PK_Cliente PRIMARY KEY (IdCliente),
    CONSTRAINT UQ_Cliente_DNI UNIQUE (DNI),
    CONSTRAINT UQ_Cliente_Email UNIQUE (Email),
    CONSTRAINT CK_Cliente_DNI CHECK (DNI NOT LIKE '%[^0-9]%'),           -- solo números
    CONSTRAINT CK_Cliente_Email CHECK (Email LIKE '%_@_%._%'),           -- formato de correo
    CONSTRAINT CK_Cliente_Telefono CHECK (Telefono LIKE '9%')            -- celular empieza en 9
);
GO

-- -------------------------------------------------------------
-- Tabla 4: Usuario (personal administrativo del sistema)
-- -------------------------------------------------------------
CREATE TABLE Usuario (
    IdUsuario      INT IDENTITY(1,1) NOT NULL,
    NombreCompleto VARCHAR(100) NOT NULL,
    Usuario        VARCHAR(30)  NOT NULL,
    Clave          VARCHAR(64)  NOT NULL,                  -- clave encriptada (hash)
    Rol            VARCHAR(15)  NOT NULL,
    Estado         BIT          NOT NULL DEFAULT 1,
    CONSTRAINT PK_Usuario PRIMARY KEY (IdUsuario),
    CONSTRAINT UQ_Usuario_Usuario UNIQUE (Usuario),
    CONSTRAINT CK_Usuario_Rol CHECK (Rol IN ('ADMIN', 'VENDEDOR', 'TASADOR'))
);
GO

-- -------------------------------------------------------------
-- Tabla 5: Pedido (ventas e importaciones a pedido)
-- -------------------------------------------------------------
CREATE TABLE Pedido (
    IdPedido     INT IDENTITY(1,1) NOT NULL,
    IdCliente    INT           NOT NULL,
    IdUsuario    INT           NOT NULL,                   -- vendedor que atiende
    FechaPedido  DATE          NOT NULL DEFAULT GETDATE(),
    TipoPedido   VARCHAR(15)   NOT NULL,
    Estado       VARCHAR(15)   NOT NULL DEFAULT 'PENDIENTE',
    Total        DECIMAL(10,2) NOT NULL DEFAULT 0,
    Adelanto     DECIMAL(10,2) NOT NULL DEFAULT 0,
    CONSTRAINT PK_Pedido PRIMARY KEY (IdPedido),
    CONSTRAINT FK_Pedido_Cliente FOREIGN KEY (IdCliente) REFERENCES Cliente(IdCliente),
    CONSTRAINT FK_Pedido_Usuario FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario),
    CONSTRAINT CK_Pedido_Tipo CHECK (TipoPedido IN ('VENTA', 'IMPORTACION')),
    CONSTRAINT CK_Pedido_Estado CHECK (Estado IN ('PENDIENTE', 'PAGADO', 'EN CAMINO', 'ENTREGADO', 'CANCELADO')),
    CONSTRAINT CK_Pedido_Total CHECK (Total >= 0),
    CONSTRAINT CK_Pedido_Adelanto CHECK (Adelanto >= 0 AND Adelanto <= Total)
);
GO

-- -------------------------------------------------------------
-- Tabla 6: DetallePedido (cómics incluidos en cada pedido)
-- Subtotal es una columna calculada automáticamente
-- -------------------------------------------------------------
CREATE TABLE DetallePedido (
    IdDetalle       INT IDENTITY(1,1) NOT NULL,
    IdPedido        INT           NOT NULL,
    IdComic         INT           NOT NULL,
    Cantidad        INT           NOT NULL,
    PrecioUnitario  DECIMAL(10,2) NOT NULL,
    Subtotal        AS (Cantidad * PrecioUnitario),
    CONSTRAINT PK_DetallePedido PRIMARY KEY (IdDetalle),
    CONSTRAINT FK_Detalle_Pedido FOREIGN KEY (IdPedido) REFERENCES Pedido(IdPedido) ON DELETE CASCADE,
    CONSTRAINT FK_Detalle_Comic FOREIGN KEY (IdComic) REFERENCES Comic(IdComic),
    CONSTRAINT UQ_Detalle_PedidoComic UNIQUE (IdPedido, IdComic),   -- no repetir cómic en un pedido
    CONSTRAINT CK_Detalle_Cantidad CHECK (Cantidad > 0),
    CONSTRAINT CK_Detalle_Precio CHECK (PrecioUnitario > 0)
);
GO

-- -------------------------------------------------------------
-- Tabla 7: SolicitudColeccion (módulo "Vende tu colección")
-- -------------------------------------------------------------
CREATE TABLE SolicitudColeccion (
    IdSolicitud     INT IDENTITY(1,1) NOT NULL,
    IdCliente       INT           NOT NULL,
    IdUsuario       INT           NULL,                    -- tasador asignado
    FechaSolicitud  DATE          NOT NULL DEFAULT GETDATE(),
    Descripcion     VARCHAR(200)  NOT NULL,
    CantidadComics  INT           NOT NULL,
    MontoOfertado   DECIMAL(10,2) NULL,
    Estado          VARCHAR(15)   NOT NULL DEFAULT 'RECIBIDA',
    CONSTRAINT PK_SolicitudColeccion PRIMARY KEY (IdSolicitud),
    CONSTRAINT FK_Solicitud_Cliente FOREIGN KEY (IdCliente) REFERENCES Cliente(IdCliente),
    CONSTRAINT FK_Solicitud_Usuario FOREIGN KEY (IdUsuario) REFERENCES Usuario(IdUsuario),
    CONSTRAINT CK_Solicitud_Cantidad CHECK (CantidadComics > 0),
    CONSTRAINT CK_Solicitud_Monto CHECK (MontoOfertado IS NULL OR MontoOfertado > 0),
    CONSTRAINT CK_Solicitud_Estado CHECK (Estado IN ('RECIBIDA', 'OFERTADA', 'ACEPTADA', 'RECHAZADA'))
);
GO

-- =============================================================
-- 3. ÍNDICES
-- =============================================================
CREATE INDEX IX_Comic_Titulo         ON Comic(Titulo);              -- buscador del catálogo
CREATE INDEX IX_Comic_Editorial      ON Comic(IdEditorial);         -- filtro por editorial
CREATE INDEX IX_Pedido_Cliente       ON Pedido(IdCliente);          -- historial del cliente
CREATE INDEX IX_Pedido_Estado        ON Pedido(Estado);             -- panel de pedidos por estado
CREATE INDEX IX_Detalle_Comic        ON DetallePedido(IdComic);     -- ventas por cómic
CREATE INDEX IX_Solicitud_Estado     ON SolicitudColeccion(Estado); -- solicitudes pendientes
GO

-- =============================================================
-- 4. VISTA: resumen de pedidos para el panel administrativo
-- =============================================================
CREATE VIEW vw_ResumenPedidos
AS
SELECT
    p.IdPedido,
    p.FechaPedido,
    c.Nombres + ' ' + c.Apellidos AS Cliente,
    u.NombreCompleto              AS Vendedor,
    p.TipoPedido,
    p.Estado,
    p.Total,
    p.Adelanto,
    p.Total - p.Adelanto          AS Saldo
FROM Pedido p
INNER JOIN Cliente c ON p.IdCliente = c.IdCliente
INNER JOIN Usuario u ON p.IdUsuario = u.IdUsuario;
GO

-- =============================================================
-- 5. PROCEDIMIENTOS ALMACENADOS
-- =============================================================

-- -------------------------------------------------------------
-- SP 1: sp_AgregarComicPedido
-- Agrega un cómic a un pedido:
--   - valida que el pedido exista y esté PENDIENTE
--   - valida que el cómic exista y tenga precio
--   - si es VENTA valida stock y lo descuenta
--   - actualiza el total del pedido
-- Usa transacción: si algo falla, no se guarda nada.
-- -------------------------------------------------------------
CREATE PROCEDURE sp_AgregarComicPedido
    @IdPedido INT,
    @IdComic  INT,
    @Cantidad INT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @Tipo   VARCHAR(15);
    DECLARE @Estado VARCHAR(15);
    DECLARE @Precio DECIMAL(10,2);
    DECLARE @Stock  INT;

    BEGIN TRY
        BEGIN TRANSACTION;

        -- Datos del pedido
        SELECT @Tipo = TipoPedido, @Estado = Estado
        FROM Pedido WHERE IdPedido = @IdPedido;

        IF @Tipo IS NULL
            RAISERROR('El pedido no existe.', 16, 1);

        IF @Estado <> 'PENDIENTE'
            RAISERROR('Solo se puede modificar un pedido PENDIENTE.', 16, 1);

        -- Datos del cómic
        SELECT @Precio = Precio, @Stock = Stock
        FROM Comic WHERE IdComic = @IdComic;

        IF @Stock IS NULL
            RAISERROR('El cómic no existe.', 16, 1);

        IF @Precio IS NULL
            RAISERROR('El cómic es de colección: primero debe cotizarse.', 16, 1);

        IF @Cantidad <= 0
            RAISERROR('La cantidad debe ser mayor a cero.', 16, 1);

        IF @Tipo = 'VENTA' AND @Stock < @Cantidad
            RAISERROR('Stock insuficiente para esta venta.', 16, 1);

        -- Registrar el detalle
        INSERT INTO DetallePedido (IdPedido, IdComic, Cantidad, PrecioUnitario)
        VALUES (@IdPedido, @IdComic, @Cantidad, @Precio);

        -- Descontar stock solo en ventas (la importación se compra afuera)
        IF @Tipo = 'VENTA'
            UPDATE Comic SET Stock = Stock - @Cantidad WHERE IdComic = @IdComic;

        -- Recalcular el total del pedido
        UPDATE Pedido
        SET Total = (SELECT SUM(Subtotal) FROM DetallePedido WHERE IdPedido = @IdPedido)
        WHERE IdPedido = @IdPedido;

        COMMIT TRANSACTION;
        PRINT 'Cómic agregado correctamente al pedido ' + CAST(@IdPedido AS VARCHAR(10));
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        PRINT 'Error: ' + ERROR_MESSAGE();
        THROW;
    END CATCH
END
GO

-- -------------------------------------------------------------
-- SP 2: sp_CambiarEstadoPedido
-- Cambia el estado de un pedido. Si se CANCELA una venta,
-- devuelve el stock de los cómics al catálogo.
-- -------------------------------------------------------------
CREATE PROCEDURE sp_CambiarEstadoPedido
    @IdPedido    INT,
    @NuevoEstado VARCHAR(15)
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @Tipo   VARCHAR(15);
    DECLARE @Estado VARCHAR(15);

    BEGIN TRY
        BEGIN TRANSACTION;

        SELECT @Tipo = TipoPedido, @Estado = Estado
        FROM Pedido WHERE IdPedido = @IdPedido;

        IF @Tipo IS NULL
            RAISERROR('El pedido no existe.', 16, 1);

        IF @Estado IN ('ENTREGADO', 'CANCELADO')
            RAISERROR('El pedido ya está cerrado y no se puede modificar.', 16, 1);

        -- Si se cancela una venta, se devuelve el stock
        IF @NuevoEstado = 'CANCELADO' AND @Tipo = 'VENTA'
        BEGIN
            UPDATE c
            SET c.Stock = c.Stock + d.Cantidad
            FROM Comic c
            INNER JOIN DetallePedido d ON c.IdComic = d.IdComic
            WHERE d.IdPedido = @IdPedido;
        END

        UPDATE Pedido SET Estado = @NuevoEstado WHERE IdPedido = @IdPedido;

        COMMIT TRANSACTION;
        PRINT 'Pedido ' + CAST(@IdPedido AS VARCHAR(10)) + ' actualizado a ' + @NuevoEstado;
    END TRY
    BEGIN CATCH
        IF @@TRANCOUNT > 0
            ROLLBACK TRANSACTION;
        PRINT 'Error: ' + ERROR_MESSAGE();
        THROW;
    END CATCH
END
GO

-- =============================================================
-- 6. DATOS DE PRUEBA (máximo 10 registros por tabla)
-- =============================================================

-- Editorial (2)
INSERT INTO Editorial (Nombre, Pais) VALUES
('DC Comics',     'Estados Unidos'),
('Marvel Comics', 'Estados Unidos');
GO

-- Comic (10): los 8 del catálogo de la landing + 2 adicionales
INSERT INTO Comic (IdEditorial, Codigo, Titulo, Formato, Anio, Precio, Stock, Disponibilidad) VALUES
(1, 'DC-001', 'The Dark Knight Returns',   'HARDCOVER', 1986,  95.00, 6, 'EN STOCK'),
(1, 'DC-002', 'Watchmen',                  'TPB',       1987, 110.00, 4, 'EN STOCK'),
(1, 'DC-003', 'Crisis on Infinite Earths', 'TPB',       1985, 180.00, 0, 'IMPORTACION'),
(1, 'DC-004', 'Batman: Year One',          'TPB',       1987,  85.00, 8, 'EN STOCK'),
(2, 'MV-001', 'Amazing Fantasy #15',       'GRAPA',     1962,   NULL, 1, 'COLECCION'),
(2, 'MV-002', 'X-Men: Dark Phoenix Saga',  'TPB',       1980, 150.00, 0, 'IMPORTACION'),
(2, 'MV-003', 'Civil War',                 'TPB',       2006, 120.00, 5, 'EN STOCK'),
(2, 'MV-004', 'The Infinity Gauntlet',     'TPB',       1991, 160.00, 0, 'IMPORTACION'),
(1, 'DC-005', 'Kingdom Come',              'TPB',       1996, 130.00, 3, 'EN STOCK'),
(2, 'MV-005', 'Daredevil: Born Again',     'TPB',       1986, 140.00, 2, 'EN STOCK');
GO

-- Cliente (8)
INSERT INTO Cliente (Nombres, Apellidos, DNI, Email, Telefono, Distrito) VALUES
('Lucía',   'Fernández Rojas', '45871236', 'lucia.fernandez@ejemplo.com', '987654321', 'Miraflores'),
('Diego',   'Ramírez Torres',  '70214589', 'diego.ramirez@ejemplo.com',   '956321478', 'San Isidro'),
('Andrea',  'Quispe Huamán',   '46325871', 'andrea.quispe@ejemplo.com',   '912457896', 'Surco'),
('Javier',  'Salazar Mendoza', '72589634', 'javier.salazar@ejemplo.com',  '998745632', 'Jesús María'),
('Valeria', 'Chávez Paredes',  '48963215', 'valeria.chavez@ejemplo.com',  '945612378', 'San Borja'),
('Renzo',   'Gutiérrez Vela',  '71458236', 'renzo.gutierrez@ejemplo.com', '963258741', 'Lince'),
('Camila',  'Torres León',     '43698521', 'camila.torres@ejemplo.com',   '974185236', 'Pueblo Libre'),
('Martín',  'Castillo Ríos',   '40125896', 'martin.castillo@ejemplo.com', '981472583', 'Barranco');
GO

-- Usuario (4): la clave se guarda encriptada con SHA2_256
INSERT INTO Usuario (NombreCompleto, Usuario, Clave, Rol) VALUES
('Carlos Mendoza Pérez', 'cmendoza', CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', 'Admin123'), 2), 'ADMIN'),
('Sofía Vargas Díaz',    'svargas',  CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', 'Venta123'), 2), 'VENDEDOR'),
('Luis Paredes Soto',    'lparedes', CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', 'Venta456'), 2), 'VENDEDOR'),
('Elena Ruiz Campos',    'eruiz',    CONVERT(VARCHAR(64), HASHBYTES('SHA2_256', 'Tasa123'),  2), 'TASADOR');
GO

-- Pedido (7): se crean en PENDIENTE con total 0;
-- el SP agrega los cómics y calcula el total.
INSERT INTO Pedido (IdCliente, IdUsuario, FechaPedido, TipoPedido) VALUES
(1, 2, '2026-09-05', 'VENTA'),
(2, 3, '2026-09-18', 'VENTA'),
(3, 2, '2026-09-25', 'IMPORTACION'),
(4, 3, '2026-10-10', 'IMPORTACION'),
(5, 2, '2026-10-14', 'VENTA'),
(6, 3, '2026-10-20', 'VENTA'),
(7, 2, '2026-10-28', 'VENTA');
GO

-- DetallePedido (10): registrados con el procedimiento almacenado
EXEC sp_AgregarComicPedido 1, 1, 1;   -- The Dark Knight Returns
EXEC sp_AgregarComicPedido 1, 4, 1;   -- Batman: Year One
EXEC sp_AgregarComicPedido 2, 2, 1;   -- Watchmen
EXEC sp_AgregarComicPedido 3, 3, 1;   -- Crisis on Infinite Earths (importación)
EXEC sp_AgregarComicPedido 3, 8, 1;   -- The Infinity Gauntlet (importación)
EXEC sp_AgregarComicPedido 4, 6, 1;   -- X-Men: Dark Phoenix Saga (importación)
EXEC sp_AgregarComicPedido 5, 7, 2;   -- Civil War x2
EXEC sp_AgregarComicPedido 6, 9, 1;   -- Kingdom Come
EXEC sp_AgregarComicPedido 7, 10, 1;  -- Daredevil: Born Again
EXEC sp_AgregarComicPedido 7, 4, 1;   -- Batman: Year One
GO

-- Pagos y adelantos registrados
UPDATE Pedido SET Adelanto = Total WHERE IdPedido IN (1, 2, 5);        -- pagados al 100 %
UPDATE Pedido SET Adelanto = Total * 0.5 WHERE IdPedido = 3;           -- importación con 50 % de adelanto
GO

-- Estados de los pedidos (usando el SP 2)
EXEC sp_CambiarEstadoPedido 1, 'ENTREGADO';
EXEC sp_CambiarEstadoPedido 2, 'PAGADO';
EXEC sp_CambiarEstadoPedido 3, 'EN CAMINO';
EXEC sp_CambiarEstadoPedido 5, 'ENTREGADO';
EXEC sp_CambiarEstadoPedido 6, 'CANCELADO';   -- devuelve el stock de Kingdom Come
-- Pedidos 4 y 7 se quedan PENDIENTES
GO

-- SolicitudColeccion (6)
INSERT INTO SolicitudColeccion (IdCliente, IdUsuario, FechaSolicitud, Descripcion, CantidadComics, MontoOfertado, Estado) VALUES
(8, 4,    '2026-09-02', 'Colección de Spider-Man de los años 90',      45, 1800.00, 'OFERTADA'),
(1, 4,    '2026-09-12', 'Tomos de Batman en español, como nuevos',     12,  600.00, 'ACEPTADA'),
(5, 4,    '2026-10-05', 'Lote Marvel con daños de humedad',            30,    NULL, 'RECHAZADA'),
(6, NULL, '2026-10-22', 'Grapas de X-Men de los años 80',              20,    NULL, 'RECIBIDA'),
(3, NULL, '2026-10-24', 'Colección completa de Sandman',               10,    NULL, 'RECIBIDA'),
(2, NULL, '2026-10-30', 'Tres ediciones Absolute de DC con estuche',    3,    NULL, 'RECIBIDA');
GO

-- =============================================================
-- 7. CONSULTAS DE VERIFICACIÓN
-- =============================================================

-- Contenido de las tablas
SELECT * FROM Editorial;
SELECT * FROM Comic;
SELECT * FROM Cliente;
SELECT * FROM Usuario;
SELECT * FROM Pedido;
SELECT * FROM DetallePedido;
SELECT * FROM SolicitudColeccion;

-- Resumen de pedidos (vista)
SELECT * FROM vw_ResumenPedidos;

-- Detalle de cada pedido con el título del cómic
SELECT d.IdPedido, c.Titulo, d.Cantidad, d.PrecioUnitario, d.Subtotal
FROM DetallePedido d
INNER JOIN Comic c ON d.IdComic = c.IdComic
ORDER BY d.IdPedido;

-- Catálogo por editorial
SELECT e.Nombre AS Editorial, c.Titulo, c.Disponibilidad,
       ISNULL(CAST(c.Precio AS VARCHAR(12)), 'Cotizar') AS Precio, c.Stock
FROM Comic c
INNER JOIN Editorial e ON c.IdEditorial = e.IdEditorial
ORDER BY e.Nombre, c.Titulo;

-- Solicitudes de colección pendientes de evaluar
SELECT s.IdSolicitud, cl.Nombres + ' ' + cl.Apellidos AS Cliente,
       s.Descripcion, s.CantidadComics, s.FechaSolicitud
FROM SolicitudColeccion s
INNER JOIN Cliente cl ON s.IdCliente = cl.IdCliente
WHERE s.Estado = 'RECIBIDA';
GO

-- =============================================================
-- 8. PRUEBAS DE ERROR DE LOS SP (descomentar para probar)
-- =============================================================
-- EXEC sp_AgregarComicPedido 7, 10, 5;       -- stock insuficiente
-- EXEC sp_AgregarComicPedido 7, 5, 1;        -- cómic de colección sin precio
-- EXEC sp_AgregarComicPedido 1, 2, 1;        -- pedido ya entregado
-- EXEC sp_CambiarEstadoPedido 6, 'PAGADO';   -- pedido cancelado no se modifica
