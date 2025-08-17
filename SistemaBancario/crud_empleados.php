<?php
/**
 * CRUD de Empleados - Sistema Bancario
 * Archivo: crud_empleados.php
 * Descripción: Gestión completa de empleados bancarios
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
if (!tienePermiso($rol, 'R', 'Empleado')) {
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
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $puesto = trim($_POST['puesto']);
    $sucursal_id = trim($_POST['sucursal_id']);
    
    if (!empty($nombre)) {
        $where_clause .= " AND e.nombre LIKE ?";
        $params[] = "%$nombre%";
        $param_types .= "s";
    }
    
    if (!empty($apellido)) {
        $where_clause .= " AND e.apellido LIKE ?";
        $params[] = "%$apellido%";
        $param_types .= "s";
    }
    
    if (!empty($puesto)) {
        $where_clause .= " AND e.puesto LIKE ?";
        $params[] = "%$puesto%";
        $param_types .= "s";
    }
    
    if (!empty($sucursal_id)) {
        $where_clause .= " AND e.sucursal_id = ?";
        $params[] = $sucursal_id;
        $param_types .= "i";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Empleado')) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar si el empleado tiene cuentas asociadas
    $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Cuenta WHERE empleado_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    
    if ($count > 0) {
        $error = "No se puede eliminar el empleado porque tiene cuentas asociadas.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM Empleado WHERE empleado_id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            $mensaje = "Empleado eliminado exitosamente.";
        } else {
            $error = "Error al eliminar el empleado.";
        }
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $sucursal_id = (int)$_POST['sucursal_id'];
    $nombre = trim($_POST['nombre']);
    $apellido = trim($_POST['apellido']);
    $puesto = trim($_POST['puesto']);
    $telefono = trim($_POST['telefono']);
    $email = trim($_POST['email']);
    $fecha_contratacion = $_POST['fecha_contratacion'];
    
    if (empty($nombre) || empty($apellido) || empty($puesto) || empty($fecha_contratacion)) {
        $error = "Los campos nombre, apellido, puesto y fecha de contratación son obligatorios.";
    } else {
        if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Empleado')) {
            $stmt = $conn->prepare("INSERT INTO Empleado (sucursal_id, nombre, apellido, puesto, telefono, email, fecha_contratacion) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssss", $sucursal_id, $nombre, $apellido, $puesto, $telefono, $email, $fecha_contratacion);
            
            if ($stmt->execute()) {
                $mensaje = "Empleado agregado exitosamente.";
            } else {
                $error = "Error al agregar el empleado.";
            }
        } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Empleado')) {
            $id = (int)$_POST['empleado_id'];
            $stmt = $conn->prepare("UPDATE Empleado SET sucursal_id = ?, nombre = ?, apellido = ?, puesto = ?, telefono = ?, email = ?, fecha_contratacion = ? WHERE empleado_id = ?");
            $stmt->bind_param("issssssi", $sucursal_id, $nombre, $apellido, $puesto, $telefono, $email, $fecha_contratacion, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Empleado actualizado exitosamente.";
            } else {
                $error = "Error al actualizar el empleado.";
            }
        }
    }
}

// Obtener datos para edición
$empleado_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Empleado')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Empleado WHERE empleado_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $empleado_editar = $result->fetch_assoc();
}

// Obtener lista de sucursales para el formulario
$sucursales = [];
$stmt = $conn->prepare("SELECT sucursal_id, nombre FROM Sucursal ORDER BY nombre");
$stmt->execute();
$result = $stmt->get_result();
$sucursales = $result->fetch_all(MYSQLI_ASSOC);

// Obtener lista de empleados
$sql = "SELECT e.*, s.nombre as sucursal_nombre FROM Empleado e 
        LEFT JOIN Sucursal s ON e.sucursal_id = s.sucursal_id 
        WHERE $where_clause ORDER BY e.apellido, e.nombre";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$empleados = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Empleados - Sistema Bancario</title>
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👥 Gestionar Empleados</h1>
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
        <?php if (tienePermiso($rol, 'C', 'Empleado') || tienePermiso($rol, 'U', 'Empleado')): ?>
        <div class="form-section">
            <h2><?php echo $empleado_editar ? 'Editar Empleado' : 'Agregar Nuevo Empleado'; ?></h2>
            <form method="POST">
                <?php if ($empleado_editar): ?>
                    <input type="hidden" name="empleado_id" value="<?php echo $empleado_editar['empleado_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="sucursal_id">Sucursal:</label>
                        <select id="sucursal_id" name="sucursal_id" required>
                            <option value="">Seleccione una sucursal</option>
                            <?php foreach ($sucursales as $sucursal): ?>
                                <option value="<?php echo $sucursal['sucursal_id']; ?>" 
                                    <?php echo ($empleado_editar && $empleado_editar['sucursal_id'] == $sucursal['sucursal_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sucursal['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo $empleado_editar ? htmlspecialchars($empleado_editar['nombre']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" value="<?php echo $empleado_editar ? htmlspecialchars($empleado_editar['apellido']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="puesto">Puesto:</label>
                        <input type="text" id="puesto" name="puesto" value="<?php echo $empleado_editar ? htmlspecialchars($empleado_editar['puesto']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo $empleado_editar ? htmlspecialchars($empleado_editar['telefono']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $empleado_editar ? htmlspecialchars($empleado_editar['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_contratacion">Fecha de Contratación:</label>
                        <input type="date" id="fecha_contratacion" name="fecha_contratacion" value="<?php echo $empleado_editar ? $empleado_editar['fecha_contratacion'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($empleado_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Empleado</button>
                        <a href="crud_empleados.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Empleado</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Empleados</h2>
            <form method="POST" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_buscar">Nombre:</label>
                        <input type="text" id="nombre_buscar" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido_buscar">Apellido:</label>
                        <input type="text" id="apellido_buscar" name="apellido" value="<?php echo isset($_POST['apellido']) ? htmlspecialchars($_POST['apellido']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="puesto_buscar">Puesto:</label>
                        <input type="text" id="puesto_buscar" name="puesto" value="<?php echo isset($_POST['puesto']) ? htmlspecialchars($_POST['puesto']) : ''; ?>">
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
                    <a href="crud_empleados.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Empleados</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($empleados); ?> empleado(s) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Puesto</th>
                        <th>Sucursal</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Fecha Contratación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($empleados)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; color: #666;">No se encontraron empleados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($empleados as $empleado): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($empleado['empleado_id']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['puesto']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['sucursal_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['email']); ?></td>
                                <td><?php echo htmlspecialchars($empleado['fecha_contratacion']); ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'Empleado')): ?>
                                        <a href="?editar=<?php echo $empleado['empleado_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'Empleado')): ?>
                                        <a href="?eliminar=<?php echo $empleado['empleado_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar este empleado?')">Eliminar</a>
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