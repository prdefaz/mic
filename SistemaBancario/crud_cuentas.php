<?php
/**
 * CRUD de Cuentas Bancarias - Sistema Bancario
 * Archivo: crud_cuentas.php
 * Descripción: Gestión completa de cuentas bancarias
 */

session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

// Incluir configuración
require_once 'config.php';

$rol = $_SESSION['rol'];

// Verificar permisos para acceder a esta página
if (!tienePermiso($rol, 'R', 'Cuenta')) {
    header("Location: index.php");
    exit();
}

$mensaje = '';
$error = '';

// Procesar formulario de búsqueda
$where_clause = "1=1";
$params = [];
$param_types = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['buscar'])) {
    $cliente_id = trim($_POST['cliente_id']);
    $tipo = trim($_POST['tipo']);
    $estado = trim($_POST['estado']);
    $sucursal_id = trim($_POST['sucursal_id']);
    
    if (!empty($cliente_id)) {
        $where_clause .= " AND c.cliente_id = ?";
        $params[] = $cliente_id;
        $param_types .= "i";
    }
    
    if (!empty($tipo)) {
        $where_clause .= " AND c.tipo = ?";
        $params[] = $tipo;
        $param_types .= "s";
    }
    
    if (!empty($estado)) {
        $where_clause .= " AND c.estado = ?";
        $params[] = $estado;
        $param_types .= "s";
    }
    
    if (!empty($sucursal_id)) {
        $where_clause .= " AND c.sucursal_id = ?";
        $params[] = $sucursal_id;
        $param_types .= "i";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Cuenta')) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar si la cuenta tiene transacciones asociadas
    $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Transaccion WHERE cuenta_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    
    if ($count > 0) {
        $error = "No se puede eliminar la cuenta porque tiene transacciones asociadas.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM Cuenta WHERE cuenta_id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            $mensaje = "Cuenta eliminada exitosamente.";
        } else {
            $error = "Error al eliminar la cuenta.";
        }
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $cliente_id = (int)$_POST['cliente_id'];
    $sucursal_id = (int)$_POST['sucursal_id'];
    $empleado_id = (int)$_POST['empleado_id'];
    $tipo = $_POST['tipo'];
    $saldo = (float)$_POST['saldo'];
    $fecha_apertura = $_POST['fecha_apertura'];
    $estado = $_POST['estado'];
    
    if (empty($cliente_id) || empty($sucursal_id) || empty($empleado_id) || empty($tipo) || empty($fecha_apertura) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Cuenta')) {
            $stmt = $conn->prepare("INSERT INTO Cuenta (cliente_id, sucursal_id, empleado_id, tipo, saldo, fecha_apertura, estado) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisds", $cliente_id, $sucursal_id, $empleado_id, $tipo, $saldo, $fecha_apertura, $estado);
            
            if ($stmt->execute()) {
                $mensaje = "Cuenta agregada exitosamente.";
            } else {
                $error = "Error al agregar la cuenta.";
            }
        } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Cuenta')) {
            $id = (int)$_POST['cuenta_id'];
            $stmt = $conn->prepare("UPDATE Cuenta SET cliente_id = ?, sucursal_id = ?, empleado_id = ?, tipo = ?, saldo = ?, fecha_apertura = ?, estado = ? WHERE cuenta_id = ?");
            $stmt->bind_param("iiisdsi", $cliente_id, $sucursal_id, $empleado_id, $tipo, $saldo, $fecha_apertura, $estado, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Cuenta actualizada exitosamente.";
            } else {
                $error = "Error al actualizar la cuenta.";
            }
        }
    }
}

// Obtener datos para edición
$cuenta_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Cuenta')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Cuenta WHERE cuenta_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cuenta_editar = $result->fetch_assoc();
}

// Obtener listas para los formularios
$clientes = [];
$stmt = $conn->prepare("SELECT cliente_id, nombre, apellido FROM Cliente ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$clientes = $result->fetch_all(MYSQLI_ASSOC);

$sucursales = [];
$stmt = $conn->prepare("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$stmt->execute();
$result = $stmt->get_result();
$sucursales = $result->fetch_all(MYSQLI_ASSOC);

$empleados = [];
$stmt = $conn->prepare("SELECT empleado_id, nombre, apellido FROM Empleado ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$empleados = $result->fetch_all(MYSQLI_ASSOC);

// Obtener lista de cuentas
$sql = "SELECT c.*, 
               cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
               s.nombre as sucursal_nombre,
               e.nombre as empleado_nombre, e.apellido as empleado_apellido
        FROM Cuenta c
        LEFT JOIN Cliente cl ON c.cliente_id = cl.cliente_id
        LEFT JOIN Sucursal s ON c.sucursal_id = s.sucursal_id
        LEFT JOIN Empleado e ON c.empleado_id = e.empleado_id
        WHERE $where_clause ORDER BY c.fecha_apertura DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$cuentas = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Cuentas - Sistema Bancario</title>
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
            line-height: 1.6;
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
            font-size: 24px;
            font-weight: 600;
        }
        
        .back-btn {
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
        
        .back-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .message {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .form-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .form-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }
        
        .form-group input, .form-group select {
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        .table-section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .table-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        .search-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .search-form h3 {
            margin-bottom: 15px;
            color: #333;
            font-size: 16px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e1e5e9;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }
        
        .results-info {
            background: #e3f2fd;
            color: #1565c0;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        
        .saldo {
            font-weight: 600;
        }
        
        .saldo-positivo {
            color: #28a745;
        }
        
        .saldo-negativo {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>💳 Gestionar Cuentas Bancarias</h1>
            <a href="index.php" class="back-btn">Volver al Menú Principal</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (!empty($mensaje)): ?>
            <div class="message success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <!-- Formulario de Agregar/Editar -->
        <?php if (tienePermiso($rol, 'C', 'Cuenta') || tienePermiso($rol, 'U', 'Cuenta')): ?>
        <div class="form-section">
            <h2><?php echo $cuenta_editar ? 'Editar Cuenta' : 'Agregar Nueva Cuenta'; ?></h2>
            <form method="POST">
                <?php if ($cuenta_editar): ?>
                    <input type="hidden" name="cuenta_id" value="<?php echo $cuenta_editar['cuenta_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente_id">Cliente:</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['cliente_id']; ?>" 
                                    <?php echo ($cuenta_editar && $cuenta_editar['cliente_id'] == $cliente['cliente_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['apellido'] . ', ' . $cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="sucursal_id">Sucursal:</label>
                        <select id="sucursal_id" name="sucursal_id" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>" 
                                    <?php echo ($cuenta_editar && $cuenta_editar['sucursal_id'] == $sucursal['sucursal_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="empleado_id">Empleado:</label>
                        <select id="empleado_id" name="empleado_id" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['empleado_id']; ?>" 
                                    <?php echo ($cuenta_editar && $cuenta_editar['empleado_id'] == $empleado['empleado_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo">Tipo de Cuenta:</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Seleccione el tipo</option>
                            <option value="Ahorros" <?php echo ($cuenta_editar && $cuenta_editar['tipo'] == 'Ahorros') ? 'selected' : ''; ?>>Ahorros</option>
                            <option value="Corriente" <?php echo ($cuenta_editar && $cuenta_editar['tipo'] == 'Corriente') ? 'selected' : ''; ?>>Corriente</option>
                            <option value="Plazo Fijo" <?php echo ($cuenta_editar && $cuenta_editar['tipo'] == 'Plazo Fijo') ? 'selected' : ''; ?>>Plazo Fijo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="saldo">Saldo:</label>
                        <input type="number" id="saldo" name="saldo" step="0.01" value="<?php echo $cuenta_editar ? $cuenta_editar['saldo'] : '0.00'; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_apertura">Fecha de Apertura:</label>
                        <input type="date" id="fecha_apertura" name="fecha_apertura" value="<?php echo $cuenta_editar ? $cuenta_editar['fecha_apertura'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" required>
                            <option value="">Seleccione el estado</option>
                            <option value="Activa" <?php echo ($cuenta_editar && $cuenta_editar['estado'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                            <option value="Inactiva" <?php echo ($cuenta_editar && $cuenta_editar['estado'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                            <option value="Bloqueada" <?php echo ($cuenta_editar && $cuenta_editar['estado'] == 'Bloqueada') ? 'selected' : ''; ?>>Bloqueada</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($cuenta_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Cuenta</button>
                        <a href="crud_cuentas.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Cuenta</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Cuentas</h2>
            <form method="POST" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente_id_buscar">Cliente:</label>
                        <select id="cliente_id_buscar" name="cliente_id">
                            <option value="">Todos los clientes</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['cliente_id']; ?>" 
                                    <?php echo (isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['cliente_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['apellido'] . ', ' . $cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_buscar">Tipo:</label>
                        <select id="tipo_buscar" name="tipo">
                            <option value="">Todos los tipos</option>
                            <option value="Ahorros" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Ahorros') ? 'selected' : ''; ?>>Ahorros</option>
                            <option value="Corriente" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Corriente') ? 'selected' : ''; ?>>Corriente</option>
                            <option value="Plazo Fijo" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Plazo Fijo') ? 'selected' : ''; ?>>Plazo Fijo</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado_buscar">Estado:</label>
                        <select id="estado_buscar" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="Activa" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                            <option value="Inactiva" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Inactiva') ? 'selected' : ''; ?>>Inactiva</option>
                            <option value="Bloqueada" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Bloqueada') ? 'selected' : ''; ?>>Bloqueada</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="sucursal_id_buscar">Sucursal:</label>
                        <select id="sucursal_id_buscar" name="sucursal_id">
                            <option value="">Todas las sucursales</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>" 
                                    <?php echo (isset($_POST['sucursal_id']) && $_POST['sucursal_id'] == $sucursal['sucursal_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                    <a href="crud_cuentas.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Cuentas Bancarias</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($cuentas); ?> cuenta(s) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Tipo</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Sucursal</th>
                        <th>Empleado</th>
                        <th>Fecha Apertura</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cuentas)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666;">No se encontraron cuentas.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cuentas as $cuenta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cuenta['cuenta_id']); ?></td>
                                <td><?php echo htmlspecialchars($cuenta['cliente_apellido'] . ', ' . $cuenta['cliente_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cuenta['tipo']); ?></td>
                                <td class="saldo <?php echo $cuenta['saldo'] >= 0 ? 'saldo-positivo' : 'saldo-negativo'; ?>">
                                    $<?php echo number_format($cuenta['saldo'], 2); ?>
                                </td>
                                <td><?php echo htmlspecialchars($cuenta['estado']); ?></td>
                                <td><?php echo htmlspecialchars($cuenta['sucursal_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cuenta['empleado_apellido'] . ', ' . $cuenta['empleado_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cuenta['fecha_apertura']); ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'Cuenta')): ?>
                                        <a href="?editar=<?php echo $cuenta['cuenta_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'Cuenta')): ?>
                                        <a href="?eliminar=<?php echo $cuenta['cuenta_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar esta cuenta?')">Eliminar</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 