-- =====================================================
-- SCRIPT DE BASE DE DATOS - SISTEMA BANCARIO
-- Archivo: script_base_datos.sql
-- Descripción: Script completo para crear la base de datos BancoDB
-- =====================================================

-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS BancoDB;
USE BancoDB;

-- =====================================================
-- CREACIÓN DE TABLAS
-- =====================================================

-- Tabla de Sucursales
CREATE TABLE Sucursal (
    sucursal_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    direccion VARCHAR(200) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    fecha_apertura DATE NOT NULL
);

-- Tabla de Empleados
CREATE TABLE Empleado (
    empleado_id INT AUTO_INCREMENT PRIMARY KEY,
    sucursal_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    puesto VARCHAR(50) NOT NULL,
    telefono VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    fecha_contratacion DATE NOT NULL,
    FOREIGN KEY (sucursal_id) REFERENCES Sucursal(sucursal_id) ON DELETE CASCADE
);

-- Tabla de Clientes
CREATE TABLE Cliente (
    cliente_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    direccion VARCHAR(200),
    telefono VARCHAR(15),
    email VARCHAR(100) UNIQUE,
    fecha_registro DATE NOT NULL DEFAULT CURRENT_DATE
);

-- Tabla de Cuentas Bancarias
CREATE TABLE Cuenta (
    cuenta_id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    sucursal_id INT NOT NULL,
    empleado_id INT NOT NULL,
    tipo ENUM('Ahorros', 'Corriente', 'Plazo Fijo') NOT NULL,
    saldo DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    fecha_apertura DATE NOT NULL DEFAULT CURRENT_DATE,
    estado ENUM('Activa', 'Inactiva', 'Bloqueada') NOT NULL DEFAULT 'Activa',
    FOREIGN KEY (cliente_id) REFERENCES Cliente(cliente_id) ON DELETE CASCADE,
    FOREIGN KEY (sucursal_id) REFERENCES Sucursal(sucursal_id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES Empleado(empleado_id) ON DELETE CASCADE
);

-- Tabla de Transacciones
CREATE TABLE Transaccion (
    transaccion_id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_id INT NOT NULL,
    empleado_id INT,
    tipo ENUM('Depósito', 'Retiro', 'Transferencia') NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    fecha_hora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    descripcion VARCHAR(200),
    FOREIGN KEY (cuenta_id) REFERENCES Cuenta(cuenta_id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES Empleado(empleado_id) ON DELETE SET NULL
);

-- Tabla de Préstamos
CREATE TABLE Prestamo (
    prestamo_id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT NOT NULL,
    empleado_id INT NOT NULL,
    sucursal_id INT NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    interes DECIMAL(5,2) NOT NULL,
    plazo_meses INT NOT NULL,
    fecha_aprobacion DATE NOT NULL,
    estado ENUM('Aprobado', 'Pagado', 'Moroso', 'Cancelado') NOT NULL DEFAULT 'Aprobado',
    FOREIGN KEY (cliente_id) REFERENCES Cliente(cliente_id) ON DELETE CASCADE,
    FOREIGN KEY (empleado_id) REFERENCES Empleado(empleado_id) ON DELETE CASCADE,
    FOREIGN KEY (sucursal_id) REFERENCES Sucursal(sucursal_id) ON DELETE CASCADE
);

-- Tabla de Tarjetas de Crédito
CREATE TABLE TarjetaCredito (
    tarjeta_id INT AUTO_INCREMENT PRIMARY KEY,
    cuenta_id INT NOT NULL,
    cliente_id INT NOT NULL,
    tipo ENUM('Clásica', 'Oro', 'Platino', 'Negra') NOT NULL,
    numero VARCHAR(16) UNIQUE NOT NULL,
    limite_credito DECIMAL(15,2) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE NOT NULL,
    estado ENUM('Activa', 'Bloqueada', 'Cancelada') NOT NULL DEFAULT 'Activa',
    FOREIGN KEY (cuenta_id) REFERENCES Cuenta(cuenta_id) ON DELETE CASCADE,
    FOREIGN KEY (cliente_id) REFERENCES Cliente(cliente_id) ON DELETE CASCADE
);

-- Tabla de Usuarios para autenticación
CREATE TABLE Usuario (
    usuario_id INT AUTO_INCREMENT PRIMARY KEY,
    empleado_id INT UNIQUE,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('Administrador', 'Desarrollador', 'Supervisor') NOT NULL,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME,
    FOREIGN KEY (empleado_id) REFERENCES Empleado(empleado_id) ON DELETE CASCADE
);

-- =====================================================
-- INSERTAR DATOS DE EJEMPLO
-- =====================================================

-- Insertar Sucursales
INSERT INTO Sucursal (nombre, direccion, telefono, fecha_apertura) VALUES
('Sucursal Central', 'Av. Principal 123, Ciudad', '022222222', '2010-05-15'),
('Sucursal Norte', 'Calle Norte 456, Ciudad', '023333333', '2015-08-20'),
('Sucursal Sur', 'Av. Sur 789, Ciudad', '024444444', '2018-03-10'),
('Sucursal Este', 'Calle Este 321, Ciudad', '025555555', '2019-11-05'),
('Sucursal Oeste', 'Av. Oeste 654, Ciudad', '026666666', '2020-07-12');

-- Insertar Empleados
INSERT INTO Empleado (sucursal_id, nombre, apellido, puesto, telefono, email, fecha_contratacion) VALUES
(1, 'Juan', 'Pérez', 'Gerente', '0912345678', 'juan.perez@bancodb.com', '2015-06-10'),
(1, 'María', 'Gómez', 'Cajero', '0987654321', 'maria.gomez@bancodb.com', '2018-03-15'),
(1, 'Carlos', 'López', 'Asesor Financiero', '0976543210', 'carlos.lopez@bancodb.com', '2019-11-20'),
(2, 'Ana', 'Rodríguez', 'Gerente', '0965432109', 'ana.rodriguez@bancodb.com', '2017-09-05'),
(2, 'Luis', 'Martínez', 'Cajero', '0954321098', 'luis.martinez@bancodb.com', '2020-01-15'),
(3, 'Elena', 'Fernández', 'Gerente', '0943210987', 'elena.fernandez@bancodb.com', '2018-12-01'),
(3, 'Roberto', 'García', 'Asesor Financiero', '0932109876', 'roberto.garcia@bancodb.com', '2019-05-20'),
(4, 'Carmen', 'López', 'Cajero', '0921098765', 'carmen.lopez@bancodb.com', '2021-02-10'),
(5, 'Miguel', 'Hernández', 'Gerente', '0910987654', 'miguel.hernandez@bancodb.com', '2020-08-25');

-- Insertar Clientes
INSERT INTO Cliente (nombre, apellido, direccion, telefono, email, fecha_registro) VALUES
('Pedro', 'Martínez', 'Calle 123, Ciudad', '0991122334', 'pedro.martinez@email.com', '2020-01-15'),
('Lucía', 'Fernández', 'Av. 456, Ciudad', '0982233445', 'lucia.fernandez@email.com', '2019-05-20'),
('Roberto', 'García', 'Calle 789, Ciudad', '0973344556', 'roberto.garcia@email.com', '2021-03-10'),
('María', 'López', 'Av. 321, Ciudad', '0964455667', 'maria.lopez@email.com', '2020-07-22'),
('Carlos', 'Hernández', 'Calle 654, Ciudad', '0955566778', 'carlos.hernandez@email.com', '2021-09-05'),
('Ana', 'González', 'Av. 987, Ciudad', '0946677889', 'ana.gonzalez@email.com', '2019-11-30'),
('Luis', 'Pérez', 'Calle 147, Ciudad', '0937788990', 'luis.perez@email.com', '2020-04-12'),
('Elena', 'Rodríguez', 'Av. 258, Ciudad', '0928899001', 'elena.rodriguez@email.com', '2021-06-18');

-- Insertar Cuentas
INSERT INTO Cuenta (cliente_id, sucursal_id, empleado_id, tipo, saldo, fecha_apertura, estado) VALUES
(1, 1, 1, 'Ahorros', 5000.00, '2020-01-20', 'Activa'),
(1, 1, 1, 'Corriente', 2500.00, '2020-02-15', 'Activa'),
(2, 2, 3, 'Ahorros', 12000.00, '2019-06-10', 'Activa'),
(3, 3, 4, 'Plazo Fijo', 30000.00, '2021-04-05', 'Activa'),
(4, 1, 2, 'Ahorros', 8000.00, '2020-08-12', 'Activa'),
(5, 2, 5, 'Corriente', 3500.00, '2021-09-20', 'Activa'),
(6, 3, 6, 'Ahorros', 15000.00, '2019-12-05', 'Activa'),
(7, 4, 8, 'Plazo Fijo', 25000.00, '2020-05-15', 'Activa'),
(8, 5, 9, 'Ahorros', 7000.00, '2021-07-08', 'Activa');

-- Insertar Transacciones
INSERT INTO Transaccion (cuenta_id, empleado_id, tipo, monto, fecha_hora, descripcion) VALUES
(1, 2, 'Depósito', 1000.00, '2023-01-10 09:15:00', 'Depósito inicial'),
(1, 2, 'Retiro', 500.00, '2023-01-12 14:30:00', 'Retiro de efectivo'),
(2, 2, 'Depósito', 2000.00, '2023-01-15 10:45:00', 'Depósito de nómina'),
(3, 3, 'Transferencia', 1500.00, '2023-01-18 11:20:00', 'Transferencia a tercero'),
(4, 4, 'Depósito', 5000.00, '2023-01-20 16:30:00', 'Depósito de ahorros'),
(5, 2, 'Retiro', 800.00, '2023-01-22 13:45:00', 'Retiro para gastos'),
(6, 5, 'Depósito', 1200.00, '2023-01-25 09:00:00', 'Depósito de salario'),
(7, 6, 'Transferencia', 3000.00, '2023-01-28 15:20:00', 'Transferencia familiar'),
(8, 8, 'Depósito', 8000.00, '2023-02-01 11:10:00', 'Depósito de inversión'),
(9, 9, 'Retiro', 1200.00, '2023-02-03 14:25:00', 'Retiro para compras');

-- Insertar Préstamos
INSERT INTO Prestamo (cliente_id, empleado_id, sucursal_id, monto, interes, plazo_meses, fecha_aprobacion, estado) VALUES
(1, 3, 1, 10000.00, 8.5, 24, '2022-06-15', 'Aprobado'),
(2, 3, 2, 20000.00, 7.5, 36, '2022-09-20', 'Pagado'),
(3, 4, 3, 15000.00, 9.0, 12, '2023-01-05', 'Aprobado'),
(4, 3, 1, 12000.00, 8.0, 18, '2022-11-10', 'Aprobado'),
(5, 5, 2, 18000.00, 7.8, 30, '2023-02-15', 'Aprobado'),
(6, 6, 3, 8000.00, 9.5, 12, '2022-12-20', 'Moroso'),
(7, 8, 4, 25000.00, 7.2, 48, '2023-01-30', 'Aprobado'),
(8, 9, 5, 16000.00, 8.2, 24, '2023-02-10', 'Aprobado');

-- Insertar Tarjetas de Crédito
INSERT INTO TarjetaCredito (cuenta_id, cliente_id, tipo, numero, limite_credito, fecha_emision, fecha_vencimiento, estado) VALUES
(1, 1, 'Clásica', '4111111111111111', 5000.00, '2022-01-01', '2025-01-01', 'Activa'),
(2, 1, 'Oro', '4222222222222222', 10000.00, '2022-03-15', '2025-03-15', 'Activa'),
(3, 2, 'Platino', '4333333333333333', 15000.00, '2022-06-20', '2025-06-20', 'Activa'),
(4, 3, 'Negra', '4444444444444444', 25000.00, '2022-08-10', '2025-08-10', 'Activa'),
(5, 4, 'Clásica', '4555555555555555', 3000.00, '2022-10-05', '2025-10-05', 'Activa'),
(6, 5, 'Oro', '4666666666666666', 8000.00, '2022-12-01', '2025-12-01', 'Activa'),
(7, 6, 'Platino', '4777777777777777', 12000.00, '2023-01-15', '2026-01-15', 'Activa'),
(8, 7, 'Oro', '4888888888888888', 10000.00, '2023-02-20', '2026-02-20', 'Activa'),
(9, 8, 'Clásica', '4999999999999999', 4000.00, '2023-03-10', '2026-03-10', 'Activa');

-- Insertar Usuarios del Sistema
-- Nota: Las contraseñas están hasheadas con password_hash('password', PASSWORD_DEFAULT)
INSERT INTO Usuario (empleado_id, username, password_hash, rol) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador'),
(2, 'desarrollador', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Desarrollador'),
(3, 'supervisor', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Supervisor'),
(4, 'admin2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador'),
(5, 'dev2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Desarrollador');

-- =====================================================
-- CREAR USUARIOS DE MYSQL Y ASIGNAR PERMISOS
-- =====================================================

-- Crear usuarios de MySQL para la conexión de la aplicación
CREATE USER 'admin_bancodb'@'localhost' IDENTIFIED BY 'AdminSecure123!';
CREATE USER 'dev_bancodb'@'localhost' IDENTIFIED BY 'DevSecure456!';
CREATE USER 'supervisor_bancodb'@'localhost' IDENTIFIED BY 'SupervisorSecure789!';

-- Permisos para el Administrador
GRANT ALL PRIVILEGES ON BancoDB.* TO 'admin_bancodb'@'localhost' WITH GRANT OPTION;

-- Permisos para el Desarrollador
GRANT SELECT, INSERT, UPDATE, DELETE ON BancoDB.Cuenta TO 'dev_bancodb'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON BancoDB.Transaccion TO 'dev_bancodb'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON BancoDB.TarjetaCredito TO 'dev_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Cliente TO 'dev_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Empleado TO 'dev_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Sucursal TO 'dev_bancodb'@'localhost';

-- Permisos para el Supervisor
GRANT SELECT ON BancoDB.Cuenta TO 'supervisor_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Transaccion TO 'supervisor_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Prestamo TO 'supervisor_bancodb'@'localhost';
GRANT SELECT ON BancoDB.TarjetaCredito TO 'supervisor_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Cliente TO 'supervisor_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Empleado TO 'supervisor_bancodb'@'localhost';
GRANT SELECT ON BancoDB.Sucursal TO 'supervisor_bancodb'@'localhost';

-- Aplicar los cambios de privilegios
FLUSH PRIVILEGES;

-- =====================================================
-- MENSAJE DE CONFIRMACIÓN
-- =====================================================

SELECT 'Base de datos BancoDB creada exitosamente!' AS Mensaje;
SELECT 'Datos de ejemplo insertados correctamente.' AS Mensaje;
SELECT 'Usuarios de MySQL creados y permisos asignados.' AS Mensaje;
SELECT 'Credenciales de prueba: admin/password, desarrollador/password, supervisor/password' AS Mensaje; 