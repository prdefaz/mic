<?php
/**
 * CRUD de Sucursales - Sistema Bancario
 * Archivo: crud_sucursales.php
 * Descripción: Gestión completa de sucursales bancarias
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
if (!tienePermiso($rol, 'R', 'Sucursal')) {
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
    $direccion = trim($_POST['direccion']);
    
    if (!empty($nombre)) {
        $where_clause .= " AND nombre LIKE ?";
        $params[] = "%$nombre%";
        $param_types .= "s";
    }
    
    if (!empty($direccion)) {
        $where_clause .= " AND direccion LIKE ?";
        $params[] = "%$direccion%";
        $param_types .= "s";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Sucursal')) {
    $id = (int)$_GET['eliminar'];
    
    // Verificar si la sucursal tiene empleados asociados
    $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Empleado WHERE sucursal_id = ?");
    $check_stmt->bind_param("i", $id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    
    if ($count > 0) {
        $error = "No se puede eliminar la sucursal porque tiene empleados asociados.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM Sucursal WHERE sucursal_id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            $mensaje = "Sucursal eliminada exitosamente.";
        } else {
            $error = "Error al eliminar la sucursal.";
        }
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $nombre = trim($_POST['nombre']);
    $direccion = trim($_POST['direccion']);
    $telefono = trim($_POST['telefono']);
    $fecha_apertura = $_POST['fecha_apertura'];
    
    if (empty($nombre) || empty($direccion) || empty($telefono) || empty($fecha_apertura)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Sucursal')) {
            $stmt = $conn->prepare("INSERT INTO Sucursal (nombre, direccion, telefono, fecha_apertura) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nombre, $direccion, $telefono, $fecha_apertura);
            
            if ($stmt->execute()) {
                $mensaje = "Sucursal agregada exitosamente.";
            } else {
                $error = "Error al agregar la sucursal.";
            }
        } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Sucursal')) {
            $id = (int)$_POST['sucursal_id'];
            $stmt = $conn->prepare("UPDATE Sucursal SET nombre = ?, direccion = ?, telefono = ?, fecha_apertura = ? WHERE sucursal_id = ?");
            $stmt->bind_param("ssssi", $nombre, $direccion, $telefono, $fecha_apertura, $id);
            
            if ($stmt->execute()) {
                $mensaje = "Sucursal actualizada exitosamente.";
            } else {
                $error = "Error al actualizar la sucursal.";
            }
        }
    }
}

// Obtener datos para edición
$sucursal_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Sucursal')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Sucursal WHERE sucursal_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sucursal_editar = $result->fetch_assoc();
}

// Obtener lista de sucursales
$sql = "SELECT * FROM Sucursal WHERE $where_clause ORDER BY nombre";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$sucursales = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Sucursales - Sistema Bancario</title>
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
            <h1>🏢 Gestionar Sucursales</h1>
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
        <?php if (tienePermiso($rol, 'C', 'Sucursal') || tienePermiso($rol, 'U', 'Sucursal')): ?>
        <div class="form-section">
            <h2><?php echo $sucursal_editar ? 'Editar Sucursal' : 'Agregar Nueva Sucursal'; ?></h2>
            <form method="POST">
                <?php if ($sucursal_editar): ?>
                    <input type="hidden" name="sucursal_id" value="<?php echo $sucursal_editar['sucursal_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo $sucursal_editar ? htmlspecialchars($sucursal_editar['nombre']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <input type="text" id="direccion" name="direccion" value="<?php echo $sucursal_editar ? htmlspecialchars($sucursal_editar['direccion']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo $sucursal_editar ? htmlspecialchars($sucursal_editar['telefono']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_apertura">Fecha de Apertura:</label>
                        <input type="date" id="fecha_apertura" name="fecha_apertura" value="<?php echo $sucursal_editar ? $sucursal_editar['fecha_apertura'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($sucursal_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Sucursal</button>
                        <a href="crud_sucursales.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Sucursal</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Sucursales</h2>
            <form method="POST" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre_buscar">Nombre:</label>
                        <input type="text" id="nombre_buscar" name="nombre" value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion_buscar">Dirección:</label>
                        <input type="text" id="direccion_buscar" name="direccion" value="<?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                    <a href="crud_sucursales.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Sucursales</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($sucursales); ?> sucursal(es) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Fecha de Apertura</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($sucursales)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #666;">No se encontraron sucursales.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($sucursales as $sucursal): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sucursal['sucursal_id']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['direccion']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($sucursal['fecha_apertura']); ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'Sucursal')): ?>
                                        <a href="?editar=<?php echo $sucursal['sucursal_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'Sucursal')): ?>
                                        <a href="?eliminar=<?php echo $sucursal['sucursal_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar esta sucursal?')">Eliminar</a>
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