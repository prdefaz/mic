<?php
/**
 * CRUD de Clientes - Sistema Bancario
 * Archivo: crud_clientes.php
 * Descripción: Gestión de clientes (solo lectura para desarrolladores y supervisores)
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
if (!tienePermiso($rol, 'R', 'Cliente')) {
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
    $email = trim($_POST['email']);
    $fecha_desde = trim($_POST['fecha_desde']);
    $fecha_hasta = trim($_POST['fecha_hasta']);
    
    if (!empty($nombre)) {
        $where_clause .= " AND nombre LIKE ?";
        $params[] = "%$nombre%";
        $param_types .= "s";
    }
    
    if (!empty($apellido)) {
        $where_clause .= " AND apellido LIKE ?";
        $params[] = "%$apellido%";
        $param_types .= "s";
    }
    
    if (!empty($email)) {
        $where_clause .= " AND email LIKE ?";
        $params[] = "%$email%";
        $param_types .= "s";
    }
    
    if (!empty($fecha_desde)) {
        $where_clause .= " AND fecha_registro >= ?";
        $params[] = $fecha_desde;
        $param_types .= "s";
    }
    
    if (!empty($fecha_hasta)) {
        $where_clause .= " AND fecha_registro <= ?";
        $params[] = $fecha_hasta;
        $param_types .= "s";
    }
}

// Solo procesar operaciones CRUD si es administrador
if (tienePermiso($rol, 'C', 'Cliente') || tienePermiso($rol, 'U', 'Cliente') || tienePermiso($rol, 'D', 'Cliente')) {
    // Procesar eliminación
    if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Cliente')) {
        $id = (int)$_GET['eliminar'];
        
        // Verificar si el cliente tiene cuentas asociadas
        $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Cuenta WHERE cliente_id = ?");
        $check_stmt->bind_param("i", $id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $count = $result->fetch_assoc()['total'];
        
        if ($count > 0) {
            $error = "No se puede eliminar el cliente porque tiene cuentas asociadas.";
        } else {
            $delete_stmt = $conn->prepare("DELETE FROM Cliente WHERE cliente_id = ?");
            $delete_stmt->bind_param("i", $id);
            
            if ($delete_stmt->execute()) {
                $mensaje = "Cliente eliminado exitosamente.";
            } else {
                $error = "Error al eliminar el cliente.";
            }
        }
    }

    // Procesar formulario de agregar/editar
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $direccion = trim($_POST['direccion']);
        $telefono = trim($_POST['telefono']);
        $email = trim($_POST['email']);
        $fecha_registro = $_POST['fecha_registro'];
        
        if (empty($nombre) || empty($apellido) || empty($fecha_registro)) {
            $error = "Los campos nombre, apellido y fecha de registro son obligatorios.";
        } else {
            if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Cliente')) {
                $stmt = $conn->prepare("INSERT INTO Cliente (nombre, apellido, direccion, telefono, email, fecha_registro) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $nombre, $apellido, $direccion, $telefono, $email, $fecha_registro);
                
                if ($stmt->execute()) {
                    $mensaje = "Cliente agregado exitosamente.";
                } else {
                    $error = "Error al agregar el cliente.";
                }
            } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Cliente')) {
                $id = (int)$_POST['cliente_id'];
                $stmt = $conn->prepare("UPDATE Cliente SET nombre = ?, apellido = ?, direccion = ?, telefono = ?, email = ?, fecha_registro = ? WHERE cliente_id = ?");
                $stmt->bind_param("ssssssi", $nombre, $apellido, $direccion, $telefono, $email, $fecha_registro, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Cliente actualizado exitosamente.";
                } else {
                    $error = "Error al actualizar el cliente.";
                }
            }
        }
    }

    // Obtener datos para edición
    $cliente_editar = null;
    if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Cliente')) {
        $id = (int)$_GET['editar'];
        $stmt = $conn->prepare("SELECT * FROM Cliente WHERE cliente_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cliente_editar = $result->fetch_assoc();
    }
}

// Obtener lista de clientes
$sql = "SELECT * FROM Cliente WHERE $where_clause ORDER BY apellido, nombre";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$clientes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Clientes - Sistema Bancario</title>
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
        
        .readonly-notice {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👤 Gestionar Clientes</h1>
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
        
        <?php if (!tienePermiso($rol, 'C', 'Cliente')): ?>
            <div class="readonly-notice">
                <strong>Modo de Solo Lectura:</strong> Su rol actual solo permite visualizar información de clientes. 
                Para realizar modificaciones, contacte a un administrador.
            </div>
        <?php endif; ?>
        
        <!-- Formulario de Agregar/Editar -->
        <?php if (tienePermiso($rol, 'C', 'Cliente') || tienePermiso($rol, 'U', 'Cliente')): ?>
        <div class="form-section">
            <h2><?php echo $cliente_editar ? 'Editar Cliente' : 'Agregar Nuevo Cliente'; ?></h2>
            <form method="POST">
                <?php if ($cliente_editar): ?>
                    <input type="hidden" name="cliente_id" value="<?php echo $cliente_editar['cliente_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['nombre']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido">Apellido:</label>
                        <input type="text" id="apellido" name="apellido" value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['apellido']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="direccion">Dirección:</label>
                        <input type="text" id="direccion" name="direccion" value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['direccion']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono:</label>
                        <input type="text" id="telefono" name="telefono" value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['telefono']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo $cliente_editar ? htmlspecialchars($cliente_editar['email']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_registro">Fecha de Registro:</label>
                        <input type="date" id="fecha_registro" name="fecha_registro" value="<?php echo $cliente_editar ? $cliente_editar['fecha_registro'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($cliente_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Cliente</button>
                        <a href="crud_clientes.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Cliente</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Clientes</h2>
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
                        <label for="email_buscar">Email:</label>
                        <input type="text" id="email_buscar" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
                    <a href="crud_clientes.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Clientes</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($clientes); ?> cliente(s) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Dirección</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Fecha Registro</th>
                        <?php if (tienePermiso($rol, 'U', 'Cliente') || tienePermiso($rol, 'D', 'Cliente')): ?>
                            <th>Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr>
                            <td colspan="<?php echo tienePermiso($rol, 'U', 'Cliente') || tienePermiso($rol, 'D', 'Cliente') ? '8' : '7'; ?>" style="text-align: center; color: #666;">No se encontraron clientes.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($clientes as $cliente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cliente['cliente_id']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['nombre']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['apellido']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['direccion']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['email']); ?></td>
                                <td><?php echo htmlspecialchars($cliente['fecha_registro']); ?></td>
                                <?php if (tienePermiso($rol, 'U', 'Cliente') || tienePermiso($rol, 'D', 'Cliente')): ?>
                                    <td class="actions">
                                        <?php if (tienePermiso($rol, 'U', 'Cliente')): ?>
                                            <a href="?editar=<?php echo $cliente['cliente_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                        <?php endif; ?>
                                        
                                        <?php if (tienePermiso($rol, 'D', 'Cliente')): ?>
                                            <a href="?eliminar=<?php echo $cliente['cliente_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar este cliente?')">Eliminar</a>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 