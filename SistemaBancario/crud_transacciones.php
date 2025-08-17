<?php
/**
 * CRUD de Transacciones - Sistema Bancario
 * Archivo: crud_transacciones.php
 * Descripción: Gestión completa de transacciones bancarias
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
if (!tienePermiso($rol, 'R', 'Transaccion')) {
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
    $cuenta_id = trim($_POST['cuenta_id']);
    $tipo = trim($_POST['tipo']);
    $empleado_id = trim($_POST['empleado_id']);
    $fecha_desde = trim($_POST['fecha_desde']);
    $fecha_hasta = trim($_POST['fecha_hasta']);
    
    if (!empty($cuenta_id)) {
        $where_clause .= " AND t.cuenta_id = ?";
        $params[] = $cuenta_id;
        $param_types .= "i";
    }
    
    if (!empty($tipo)) {
        $where_clause .= " AND t.tipo = ?";
        $params[] = $tipo;
        $param_types .= "s";
    }
    
    if (!empty($empleado_id)) {
        $where_clause .= " AND t.empleado_id = ?";
        $params[] = $empleado_id;
        $param_types .= "i";
    }
    
    if (!empty($fecha_desde)) {
        $where_clause .= " AND DATE(t.fecha_hora) >= ?";
        $params[] = $fecha_desde;
        $param_types .= "s";
    }
    
    if (!empty($fecha_hasta)) {
        $where_clause .= " AND DATE(t.fecha_hora) <= ?";
        $params[] = $fecha_hasta;
        $param_types .= "s";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Transaccion')) {
    $id = (int)$_GET['eliminar'];
    
    $delete_stmt = $conn->prepare("DELETE FROM Transaccion WHERE transaccion_id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $mensaje = "Transacción eliminada exitosamente.";
    } else {
        $error = "Error al eliminar la transacción.";
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $cuenta_id = (int)$_POST['cuenta_id'];
    $empleado_id = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
    $tipo = $_POST['tipo'];
    $monto = (float)$_POST['monto'];
    $fecha_hora = $_POST['fecha_hora'];
    $descripcion = trim($_POST['descripcion']);
    
    if (empty($cuenta_id) || empty($tipo) || empty($monto) || empty($fecha_hora)) {
        $error = "Los campos cuenta, tipo, monto y fecha son obligatorios.";
    } else {
        if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Transaccion')) {
            $stmt = $conn->prepare("INSERT INTO Transaccion (cuenta_id, empleado_id, tipo, monto, fecha_hora, descripcion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisds", $cuenta_id, $empleado_id, $tipo, $monto, $fecha_hora, $descripcion);
            
            if ($stmt->execute()) {
                $mensaje = "Transacción agregada exitosamente.";
            } else {
                $error = "Error al agregar la transacción.";
            }
        } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Transaccion')) {
            $id = (int)$_POST['transaccion_id'];
            $stmt = $conn->prepare("UPDATE Transaccion SET cuenta_id = ?, empleado_id = ?, tipo = ?, monto = ?, fecha_hora = ?, descripcion = ? WHERE transaccion_id = ?");
            $stmt->bind_param("iisdsi", $cuenta_id, $empleado_id, $tipo, $monto, $fecha_hora, $descripcion, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Transacción actualizada exitosamente.";
            } else {
                $error = "Error al actualizar la transacción.";
            }
        }
    }
}

// Obtener datos para edición
$transaccion_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Transaccion')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Transaccion WHERE transaccion_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaccion_editar = $result->fetch_assoc();
}

// Obtener listas para los formularios
$cuentas = [];
$stmt = $conn->prepare("SELECT c.cuenta_id, c.tipo, c.saldo, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido 
                        FROM Cuenta c 
                        LEFT JOIN Cliente cl ON c.cliente_id = cl.cliente_id 
                        WHERE c.estado = 'Activa' 
                        ORDER BY cl.apellido, cl.nombre");
$stmt->execute();
$result = $stmt->get_result();
$cuentas = $result->fetch_all(MYSQLI_ASSOC);

$empleados = [];
$stmt = $conn->prepare("SELECT empleado_id, nombre, apellido FROM Empleado ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$empleados = $result->fetch_all(MYSQLI_ASSOC);

// Obtener lista de transacciones
$sql = "SELECT t.*, 
               c.tipo as cuenta_tipo, c.saldo as cuenta_saldo,
               cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
               e.nombre as empleado_nombre, e.apellido as empleado_apellido
        FROM Transaccion t
        LEFT JOIN Cuenta c ON t.cuenta_id = c.cuenta_id
        LEFT JOIN Cliente cl ON c.cliente_id = cl.cliente_id
        LEFT JOIN Empleado e ON t.empleado_id = e.empleado_id
        WHERE $where_clause ORDER BY t.fecha_hora DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$transacciones = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Transacciones - Sistema Bancario</title>
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
        
        .form-group input, .form-group select, .form-group textarea {
            padding: 10px;
            border: 2px solid #e1e5e9;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
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
        
        .monto {
            font-weight: 600;
        }
        
        .monto-deposito {
            color: #28a745;
        }
        
        .monto-retiro {
            color: #dc3545;
        }
        
        .monto-transferencia {
            color: #007bff;
        }
        
        .tipo-transaccion {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tipo-deposito {
            background: #d4edda;
            color: #155724;
        }
        
        .tipo-retiro {
            background: #f8d7da;
            color: #721c24;
        }
        
        .tipo-transferencia {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>💰 Gestionar Transacciones</h1>
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
        <?php if (tienePermiso($rol, 'C', 'Transaccion') || tienePermiso($rol, 'U', 'Transaccion')): ?>
        <div class="form-section">
            <h2><?php echo $transaccion_editar ? 'Editar Transacción' : 'Agregar Nueva Transacción'; ?></h2>
            <form method="POST">
                <?php if ($transaccion_editar): ?>
                    <input type="hidden" name="transaccion_id" value="<?php echo $transaccion_editar['transaccion_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cuenta_id">Cuenta:</label>
                        <select id="cuenta_id" name="cuenta_id" required>
                            <option value="">Seleccione una cuenta</option>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <option value="<?php echo $cuenta['cuenta_id']; ?>" 
                                    <?php echo ($transaccion_editar && $transaccion_editar['cuenta_id'] == $cuenta['cuenta_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cuenta['cliente_apellido'] . ', ' . $cuenta['cliente_nombre'] . ' - ' . $cuenta['tipo'] . ' ($' . number_format($cuenta['saldo'], 2) . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="empleado_id">Empleado (Opcional):</label>
                        <select id="empleado_id" name="empleado_id">
                            <option value="">Sin empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['empleado_id']; ?>" 
                                    <?php echo ($transaccion_editar && $transaccion_editar['empleado_id'] == $empleado['empleado_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo de Transacción:</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Seleccione el tipo</option>
                            <option value="Depósito" <?php echo ($transaccion_editar && $transaccion_editar['tipo'] == 'Depósito') ? 'selected' : ''; ?>>Depósito</option>
                            <option value="Retiro" <?php echo ($transaccion_editar && $transaccion_editar['tipo'] == 'Retiro') ? 'selected' : ''; ?>>Retiro</option>
                            <option value="Transferencia" <?php echo ($transaccion_editar && $transaccion_editar['tipo'] == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="monto">Monto:</label>
                        <input type="number" id="monto" name="monto" step="0.01" min="0" value="<?php echo $transaccion_editar ? $transaccion_editar['monto'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_hora">Fecha y Hora:</label>
                        <input type="datetime-local" id="fecha_hora" name="fecha_hora" value="<?php echo $transaccion_editar ? date('Y-m-d\TH:i', strtotime($transaccion_editar['fecha_hora'])) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="descripcion">Descripción:</label>
                        <textarea id="descripcion" name="descripcion" placeholder="Descripción de la transacción..."><?php echo $transaccion_editar ? htmlspecialchars($transaccion_editar['descripcion']) : ''; ?></textarea>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($transaccion_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Transacción</button>
                        <a href="crud_transacciones.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Transacción</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Transacciones</h2>
            <form method="POST" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="cuenta_id_buscar">Cuenta:</label>
                        <select id="cuenta_id_buscar" name="cuenta_id">
                            <option value="">Todas las cuentas</option>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <option value="<?php echo $cuenta['cuenta_id']; ?>" 
                                    <?php echo (isset($_POST['cuenta_id']) && $_POST['cuenta_id'] == $cuenta['cuenta_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cuenta['cliente_apellido'] . ', ' . $cuenta['cliente_nombre'] . ' - ' . $cuenta['tipo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="tipo_buscar">Tipo:</label>
                        <select id="tipo_buscar" name="tipo">
                            <option value="">Todos los tipos</option>
                            <option value="Depósito" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Depósito') ? 'selected' : ''; ?>>Depósito</option>
                            <option value="Retiro" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Retiro') ? 'selected' : ''; ?>>Retiro</option>
                            <option value="Transferencia" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Transferencia') ? 'selected' : ''; ?>>Transferencia</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="empleado_id_buscar">Empleado:</label>
                        <select id="empleado_id_buscar" name="empleado_id">
                            <option value="">Todos los empleados</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['empleado_id']; ?>" 
                                    <?php echo (isset($_POST['empleado_id']) && $_POST['empleado_id'] == $empleado['empleado_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_desde">Fecha Desde:</label>
                        <input type="date" id="fecha_desde" name="fecha_desde" value="<?php echo isset($_POST['fecha_desde']) ? htmlspecialchars($_POST['fecha_desde']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_hasta">Fecha Hasta:</label>
                        <input type="date" id="fecha_hasta" name="fecha_hasta" value="<?php echo isset($_POST['fecha_hasta']) ? htmlspecialchars($_POST['fecha_hasta']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                    <a href="crud_transacciones.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Transacciones</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($transacciones); ?> transacción(es) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Cuenta</th>
                        <th>Tipo</th>
                        <th>Monto</th>
                        <th>Empleado</th>
                        <th>Fecha y Hora</th>
                        <th>Descripción</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transacciones)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666;">No se encontraron transacciones.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transacciones as $transaccion): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($transaccion['transaccion_id']); ?></td>
                                <td><?php echo htmlspecialchars($transaccion['cliente_apellido'] . ', ' . $transaccion['cliente_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($transaccion['cuenta_tipo']); ?></td>
                                <td>
                                    <span class="tipo-transaccion tipo-<?php echo strtolower($transaccion['tipo']); ?>">
                                        <?php echo htmlspecialchars($transaccion['tipo']); ?>
                                    </span>
                                </td>
                                <td class="monto monto-<?php echo strtolower($transaccion['tipo']); ?>">
                                    $<?php echo number_format($transaccion['monto'], 2); ?>
                                </td>
                                <td><?php echo $transaccion['empleado_nombre'] ? htmlspecialchars($transaccion['empleado_apellido'] . ', ' . $transaccion['empleado_nombre']) : 'Sin empleado'; ?></td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($transaccion['fecha_hora']))); ?></td>
                                <td><?php echo htmlspecialchars($transaccion['descripcion']); ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'Transaccion')): ?>
                                        <a href="?editar=<?php echo $transaccion['transaccion_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'Transaccion')): ?>
                                        <a href="?eliminar=<?php echo $transaccion['transaccion_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar esta transacción?')">Eliminar</a>
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