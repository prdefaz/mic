<?php
/**
 * Configuración de la base de datos para el Sistema Bancario
 * Archivo: config.php
 * Descripción: Establece la conexión con la base de datos MySQL usando mysqli
 */

// Configuración de la base de datos
$servername = "localhost";
$username = "root"; // Usuario por defecto de XAMPP
$password = "";     // Contraseña por defecto de XAMPP
$dbname = "BancoDB";

// Crear conexión usando mysqli
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Configurar charset para evitar problemas con caracteres especiales
$conn->set_charset("utf8");

// Función para limpiar datos de entrada
function limpiarDatos($datos) {
    global $conn;
    return $conn->real_escape_string(trim($datos));
}

// Función para verificar si el usuario tiene permisos
function tienePermiso($rol, $accion, $tabla) {
    $matrizPermisos = [
        'Administrador' => [
            'Sucursal' => ['C', 'R', 'U', 'D'],
            'Empleado' => ['C', 'R', 'U', 'D'],
            'Cliente' => ['C', 'R', 'U', 'D'],
            'Cuenta' => ['C', 'R', 'U', 'D'],
            'Transaccion' => ['C', 'R', 'U', 'D'],
            'Prestamo' => ['C', 'R', 'U', 'D'],
            'TarjetaCredito' => ['C', 'R', 'U', 'D'],
            'Usuario' => ['C', 'R', 'U', 'D']
        ],
        'Desarrollador' => [
            'Cuenta' => ['C', 'R', 'U', 'D'],
            'Transaccion' => ['C', 'R', 'U', 'D'],
            'TarjetaCredito' => ['C', 'R', 'U', 'D'],
            'Cliente' => ['R'],
            'Empleado' => ['R']
        ],
        'Supervisor' => [
            'Cuenta' => ['R'],
            'Transaccion' => ['R'],
            'Prestamo' => ['R'],
            'TarjetaCredito' => ['R'],
            'Cliente' => ['R']
        ]
    ];
    
    return isset($matrizPermisos[$rol][$tabla]) && in_array($accion, $matrizPermisos[$rol][$tabla]);
}

// Función para obtener el nombre del rol en español
function obtenerNombreRol($rol) {
    $roles = [
        'Administrador' => 'Administrador',
        'Desarrollador' => 'Desarrollador',
        'Supervisor' => 'Supervisor'
    ];
    
    return isset($roles[$rol]) ? $roles[$rol] : $rol;
}
?> 