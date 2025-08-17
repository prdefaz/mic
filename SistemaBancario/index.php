<?php
/**
 * Página Principal del Sistema Bancario
 * Archivo: index.php
 * Descripción: Dashboard principal con menú dinámico según rol
 */

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir configuración
require_once 'config.php';

$username = $_SESSION['username'];
$rol = $_SESSION['rol'];
$nombreRol = obtenerNombreRol($rol);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema Bancario</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background 0.3s ease;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            text-align: center;
        }
        
        .welcome-section h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 24px;
        }
        
        .welcome-section p {
            color: #666;
            font-size: 16px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .menu-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            text-decoration: none;
            color: inherit;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        
        .menu-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
            display: flex;
            align-items: center;
        }
        
        .menu-card p {
            color: #666;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .icon {
            margin-right: 10px;
            font-size: 24px;
        }
        
        .stats-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .stats-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card h4 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        .stat-card p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .disabled::after {
            content: " (No disponible)";
            color: #999;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏦 Sistema Bancario</h1>
            <div class="user-info">
                <p><strong>Usuario:</strong> <?php echo htmlspecialchars($username); ?></p>
                <p><strong>Rol:</strong> <?php echo htmlspecialchars($nombreRol); ?></p>
                <a href="logout.php" class="logout-btn">Cerrar Sesión</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-section">
            <h2>Bienvenido al Sistema Bancario</h2>
            <p>Seleccione una opción del menú para gestionar el sistema</p>
        </div>
        
        <div class="menu-grid">
            <?php if (tienePermiso($rol, 'R', 'Sucursal')): ?>
            <a href="crud_sucursales.php" class="menu-card">
                <h3><span class="icon">🏢</span>Gestionar Sucursales</h3>
                <p>Administrar sucursales bancarias, incluyendo información de ubicación y contacto.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'Empleado')): ?>
            <a href="crud_empleados.php" class="menu-card">
                <h3><span class="icon">👥</span>Gestionar Empleados</h3>
                <p>Administrar información de empleados, puestos y asignaciones a sucursales.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'Cliente')): ?>
            <a href="crud_clientes.php" class="menu-card">
                <h3><span class="icon">👤</span>Gestionar Clientes</h3>
                <p>Administrar información de clientes del banco y sus datos personales.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'Cuenta')): ?>
            <a href="crud_cuentas.php" class="menu-card">
                <h3><span class="icon">💳</span>Gestionar Cuentas</h3>
                <p>Administrar cuentas bancarias, saldos y estados de las cuentas de clientes.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'Transaccion')): ?>
            <a href="crud_transacciones.php" class="menu-card">
                <h3><span class="icon">💰</span>Gestionar Transacciones</h3>
                <p>Administrar depósitos, retiros y transferencias entre cuentas bancarias.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'Prestamo')): ?>
            <a href="crud_prestamos.php" class="menu-card">
                <h3><span class="icon">🏦</span>Gestionar Préstamos</h3>
                <p>Administrar préstamos bancarios, montos, intereses y estados de pago.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'TarjetaCredito')): ?>
            <a href="crud_tarjetas_credito.php" class="menu-card">
                <h3><span class="icon">💳</span>Gestionar Tarjetas de Crédito</h3>
                <p>Administrar tarjetas de crédito, límites y estados de las tarjetas.</p>
            </a>
            <?php endif; ?>
            
            <?php if (tienePermiso($rol, 'R', 'Usuario')): ?>
            <a href="crud_usuarios.php" class="menu-card">
                <h3><span class="icon">🔐</span>Gestionar Usuarios</h3>
                <p>Administrar usuarios del sistema, roles y permisos de acceso.</p>
            </a>
            <?php endif; ?>
        </div>
        
        <div class="stats-section">
            <h3>Estadísticas del Sistema</h3>
            <div class="stats-grid">
                <?php
                // Obtener estadísticas básicas
                $stats = [];
                
                // Contar clientes
                $result = $conn->query("SELECT COUNT(*) as total FROM Cliente");
                $stats['clientes'] = $result->fetch_assoc()['total'];
                
                // Contar cuentas activas
                $result = $conn->query("SELECT COUNT(*) as total FROM Cuenta WHERE estado = 'Activa'");
                $stats['cuentas'] = $result->fetch_assoc()['total'];
                
                // Contar empleados
                $result = $conn->query("SELECT COUNT(*) as total FROM Empleado");
                $stats['empleados'] = $result->fetch_assoc()['total'];
                
                // Contar sucursales
                $result = $conn->query("SELECT COUNT(*) as total FROM Sucursal");
                $stats['sucursales'] = $result->fetch_assoc()['total'];
                ?>
                
                <div class="stat-card">
                    <h4><?php echo $stats['clientes']; ?></h4>
                    <p>Clientes Registrados</p>
                </div>
                
                <div class="stat-card">
                    <h4><?php echo $stats['cuentas']; ?></h4>
                    <p>Cuentas Activas</p>
                </div>
                
                <div class="stat-card">
                    <h4><?php echo $stats['empleados']; ?></h4>
                    <p>Empleados</p>
                </div>
                
                <div class="stat-card">
                    <h4><?php echo $stats['sucursales']; ?></h4>
                    <p>Sucursales</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 