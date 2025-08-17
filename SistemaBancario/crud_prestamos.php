<?php
/**
 * CRUD de Préstamos - Sistema Bancario
 * Archivo: crud_prestamos.php
 * Descripción: Gestión completa de préstamos bancarios
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
if (!tienePermiso($rol, 'R', 'Prestamo')) {
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
    $estado = trim($_POST['estado']);
    $sucursal_id = trim($_POST['sucursal_id']);
    $empleado_id = trim($_POST['empleado_id']);
    
    if (!empty($cliente_id)) {
        $where_clause .= " AND p.cliente_id = ?";
        $params[] = $cliente_id;
        $param_types .= "i";
    }
    
    if (!empty($estado)) {
        $where_clause .= " AND p.estado = ?";
        $params[] = $estado;
        $param_types .= "s";
    }
    
    if (!empty($sucursal_id)) {
        $where_clause .= " AND p.sucursal_id = ?";
        $params[] = $sucursal_id;
        $param_types .= "i";
    }
    
    if (!empty($empleado_id)) {
        $where_clause .= " AND p.empleado_id = ?";
        $params[] = $empleado_id;
        $param_types .= "i";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Prestamo')) {
    $id = (int)$_GET['eliminar'];
    
    $delete_stmt = $conn->prepare("DELETE FROM Prestamo WHERE prestamo_id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $mensaje = "Préstamo eliminado exitosamente.";
    } else {
        $error = "Error al eliminar el préstamo.";
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $cliente_id = (int)$_POST['cliente_id'];
    $empleado_id = (int)$_POST['empleado_id'];
    $sucursal_id = (int)$_POST['sucursal_id'];
    $monto = (float)$_POST['monto'];
    $interes = (float)$_POST['interes'];
    $plazo_meses = (int)$_POST['plazo_meses'];
    $fecha_aprobacion = $_POST['fecha_aprobacion'];
    $estado = $_POST['estado'];
    
    if (empty($cliente_id) || empty($empleado_id) || empty($sucursal_id) || empty($monto) || empty($interes) || empty($plazo_meses) || empty($fecha_aprobacion) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Prestamo')) {
            $stmt = $conn->prepare("INSERT INTO Prestamo (cliente_id, empleado_id, sucursal_id, monto, interes, plazo_meses, fecha_aprobacion, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiddis", $cliente_id, $empleado_id, $sucursal_id, $monto, $interes, $plazo_meses, $fecha_aprobacion, $estado);
            
            if ($stmt->execute()) {
                $mensaje = "Préstamo agregado exitosamente.";
            } else {
                $error = "Error al agregar el préstamo.";
            }
        } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Prestamo')) {
            $id = (int)$_POST['prestamo_id'];
            $stmt = $conn->prepare("UPDATE Prestamo SET cliente_id = ?, empleado_id = ?, sucursal_id = ?, monto = ?, interes = ?, plazo_meses = ?, fecha_aprobacion = ?, estado = ? WHERE prestamo_id = ?");
            $stmt->bind_param("iiiddisi", $cliente_id, $empleado_id, $sucursal_id, $monto, $interes, $plazo_meses, $fecha_aprobacion, $estado, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Préstamo actualizado exitosamente.";
            } else {
                $error = "Error al actualizar el préstamo.";
            }
        }
    }
}

// Obtener datos para edición
$prestamo_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Prestamo')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Prestamo WHERE prestamo_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $prestamo_editar = $result->fetch_assoc();
}

// Obtener listas para los formularios
$clientes = [];
$stmt = $conn->prepare("SELECT cliente_id, nombre, apellido FROM Cliente ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$clientes = $result->fetch_all(MYSQLI_ASSOC);

$empleados = [];
$stmt = $conn->prepare("SELECT empleado_id, nombre, apellido FROM Empleado ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$empleados = $result->fetch_all(MYSQLI_ASSOC);

$sucursales = [];
$stmt = $conn->prepare("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$stmt->execute();
$result = $stmt->get_result();
$sucursales = $result->fetch_all(MYSQLI_ASSOC);

// Obtener lista de préstamos
$sql = "SELECT p.*, 
               cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
               e.nombre as empleado_nombre, e.apellido as empleado_apellido,
               s.nombre as sucursal_nombre
        FROM Prestamo p
        LEFT JOIN Cliente cl ON p.cliente_id = cl.cliente_id
        LEFT JOIN Empleado e ON p.empleado_id = e.empleado_id
        LEFT JOIN Sucursal s ON p.sucursal_id = s.sucursal_id
        WHERE $where_clause ORDER BY p.fecha_aprobacion DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$prestamos = $result->fetch_all(MYSQLI_ASSOC);

// Función para calcular cuota mensual
function calcularCuotaMensual($monto, $interes, $plazo_meses) {
    $tasa_mensual = $interes / 100 / 12;
    if ($tasa_mensual == 0) {
        return $monto / $plazo_meses;
    }
    return $monto * ($tasa_mensual * pow(1 + $tasa_mensual, $plazo_meses)) / (pow(1 + $tasa_mensual, $plazo_meses) - 1);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Préstamos - Sistema Bancario</title>
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
        
        .monto {
            font-weight: 600;
            color: #28a745;
        }
        
        .interes {
            font-weight: 600;
            color: #007bff;
        }
        
        .estado-aprobado {
            color: #28a745;
            font-weight: 600;
        }
        
        .estado-pagado {
            color: #17a2b8;
            font-weight: 600;
        }
        
        .estado-moroso {
            color: #dc3545;
            font-weight: 600;
        }
        
        .estado-cancelado {
            color: #6c757d;
            font-weight: 600;
        }
        
        .cuota-info {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏦 Gestionar Préstamos</h1>
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
        <?php if (tienePermiso($rol, 'C', 'Prestamo') || tienePermiso($rol, 'U', 'Prestamo')): ?>
        <div class="form-section">
            <h2><?php echo $prestamo_editar ? 'Editar Préstamo' : 'Agregar Nuevo Préstamo'; ?></h2>
            <form method="POST">
                <?php if ($prestamo_editar): ?>
                    <input type="hidden" name="prestamo_id" value="<?php echo $prestamo_editar['prestamo_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cliente_id">Cliente:</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['cliente_id']; ?>" 
                                    <?php echo ($prestamo_editar && $prestamo_editar['cliente_id'] == $cliente['cliente_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['apellido'] . ', ' . $cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="empleado_id">Empleado:</label>
                        <select id="empleado_id" name="empleado_id" required>
                            <option value="">Seleccione un empleado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['empleado_id']; ?>" 
                                    <?php echo ($prestamo_editar && $prestamo_editar['empleado_id'] == $empleado['empleado_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sucursal_id">Sucursal:</label>
                        <select id="sucursal_id" name="sucursal_id" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>" 
                                    <?php echo ($prestamo_editar && $prestamo_editar['sucursal_id'] == $sucursal['sucursal_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="monto">Monto del Préstamo:</label>
                        <input type="number" id="monto" name="monto" step="0.01" min="0" value="<?php echo $prestamo_editar ? $prestamo_editar['monto'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="interes">Interés Anual (%):</label>
                        <input type="number" id="interes" name="interes" step="0.01" min="0" max="100" value="<?php echo $prestamo_editar ? $prestamo_editar['interes'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="plazo_meses">Plazo (Meses):</label>
                        <input type="number" id="plazo_meses" name="plazo_meses" min="1" max="360" value="<?php echo $prestamo_editar ? $prestamo_editar['plazo_meses'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_aprobacion">Fecha de Aprobación:</label>
                        <input type="date" id="fecha_aprobacion" name="fecha_aprobacion" value="<?php echo $prestamo_editar ? $prestamo_editar['fecha_aprobacion'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" required>
                            <option value="">Seleccione el estado</option>
                            <option value="Aprobado" <?php echo ($prestamo_editar && $prestamo_editar['estado'] == 'Aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="Pagado" <?php echo ($prestamo_editar && $prestamo_editar['estado'] == 'Pagado') ? 'selected' : ''; ?>>Pagado</option>
                            <option value="Moroso" <?php echo ($prestamo_editar && $prestamo_editar['estado'] == 'Moroso') ? 'selected' : ''; ?>>Moroso</option>
                            <option value="Cancelado" <?php echo ($prestamo_editar && $prestamo_editar['estado'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($prestamo_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Préstamo</button>
                        <a href="crud_prestamos.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Préstamo</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Préstamos</h2>
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
                        <label for="estado_buscar">Estado:</label>
                        <select id="estado_buscar" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="Aprobado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Aprobado') ? 'selected' : ''; ?>>Aprobado</option>
                            <option value="Pagado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Pagado') ? 'selected' : ''; ?>>Pagado</option>
                            <option value="Moroso" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Moroso') ? 'selected' : ''; ?>>Moroso</option>
                            <option value="Cancelado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
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
                </div>
                
                <div class="form-row">
                    <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                    <a href="crud_prestamos.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Préstamos</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($prestamos); ?> préstamo(s) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Monto</th>
                        <th>Interés</th>
                        <th>Plazo</th>
                        <th>Cuota Mensual</th>
                        <th>Estado</th>
                        <th>Sucursal</th>
                        <th>Empleado</th>
                        <th>Fecha Aprobación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($prestamos)): ?>
                        <tr>
                            <td colspan="11" style="text-align: center; color: #666;">No se encontraron préstamos.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($prestamos as $prestamo): ?>
                            <?php $cuota_mensual = calcularCuotaMensual($prestamo['monto'], $prestamo['interes'], $prestamo['plazo_meses']); ?>
                            <tr>
                                <td><?php echo htmlspecialchars($prestamo['prestamo_id']); ?></td>
                                <td><?php echo htmlspecialchars($prestamo['cliente_apellido'] . ', ' . $prestamo['cliente_nombre']); ?></td>
                                <td class="monto">$<?php echo number_format($prestamo['monto'], 2); ?></td>
                                <td class="interes"><?php echo number_format($prestamo['interes'], 2); ?>%</td>
                                <td><?php echo htmlspecialchars($prestamo['plazo_meses']); ?> meses</td>
                                <td class="monto">$<?php echo number_format($cuota_mensual, 2); ?></td>
                                <td class="estado-<?php echo strtolower($prestamo['estado']); ?>"><?php echo htmlspecialchars($prestamo['estado']); ?></td>
                                <td><?php echo htmlspecialchars($prestamo['sucursal_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($prestamo['empleado_apellido'] . ', ' . $prestamo['empleado_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($prestamo['fecha_aprobacion']); ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'Prestamo')): ?>
                                        <a href="?editar=<?php echo $prestamo['prestamo_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'Prestamo')): ?>
                                        <a href="?eliminar=<?php echo $prestamo['prestamo_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar este préstamo?')">Eliminar</a>
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