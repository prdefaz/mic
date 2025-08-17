<?php
/**
 * CRUD de Usuarios del Sistema - Sistema Bancario
 * Archivo: crud_usuarios.php
 * Descripción: Gestión completa de usuarios del sistema
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
if (!tienePermiso($rol, 'R', 'Usuario')) {
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
    $username = trim($_POST['username']);
    $rol_buscar = trim($_POST['rol']);
    $empleado_id = trim($_POST['empleado_id']);
    
    if (!empty($username)) {
        $where_clause .= " AND u.username LIKE ?";
        $params[] = "%$username%";
        $param_types .= "s";
    }
    
    if (!empty($rol_buscar)) {
        $where_clause .= " AND u.rol = ?";
        $params[] = $rol_buscar;
        $param_types .= "s";
    }
    
    if (!empty($empleado_id)) {
        $where_clause .= " AND u.empleado_id = ?";
        $params[] = $empleado_id;
        $param_types .= "i";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'Usuario')) {
    $id = (int)$_GET['eliminar'];
    
    // No permitir eliminar el propio usuario
    if ($id == $_SESSION['usuario_id']) {
        $error = "No puede eliminar su propio usuario.";
    } else {
        $delete_stmt = $conn->prepare("DELETE FROM Usuario WHERE usuario_id = ?");
        $delete_stmt->bind_param("i", $id);
        
        if ($delete_stmt->execute()) {
            $mensaje = "Usuario eliminado exitosamente.";
        } else {
            $error = "Error al eliminar el usuario.";
        }
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $empleado_id = !empty($_POST['empleado_id']) ? (int)$_POST['empleado_id'] : null;
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $rol_usuario = $_POST['rol'];
    
    if (empty($username) || empty($rol_usuario)) {
        $error = "Los campos usuario y rol son obligatorios.";
    } elseif (isset($_POST['agregar']) && empty($password)) {
        $error = "La contraseña es obligatoria para nuevos usuarios.";
    } else {
        // Verificar si el username ya existe
        $check_stmt = $conn->prepare("SELECT usuario_id FROM Usuario WHERE username = ? AND usuario_id != ?");
        $id_check = isset($_POST['usuario_id']) ? (int)$_POST['usuario_id'] : 0;
        $check_stmt->bind_param("si", $username, $id_check);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "El nombre de usuario ya existe.";
        } else {
            if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'Usuario')) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO Usuario (empleado_id, username, password_hash, rol) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $empleado_id, $username, $password_hash, $rol_usuario);
                
                if ($stmt->execute()) {
                    $mensaje = "Usuario agregado exitosamente.";
                } else {
                    $error = "Error al agregar el usuario.";
                }
            } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'Usuario')) {
                $id = (int)$_POST['usuario_id'];
                
                if (!empty($password)) {
                    // Actualizar con nueva contraseña
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE Usuario SET empleado_id = ?, username = ?, password_hash = ?, rol = ? WHERE usuario_id = ?");
                    $stmt->bind_param("isssi", $empleado_id, $username, $password_hash, $rol_usuario, $id);
                } else {
                    // Actualizar sin cambiar contraseña
                    $stmt = $conn->prepare("UPDATE Usuario SET empleado_id = ?, username = ?, rol = ? WHERE usuario_id = ?");
                    $stmt->bind_param("issi", $empleado_id, $username, $rol_usuario, $id);
                }
                
                if ($stmt->execute()) {
                    $mensaje = "Usuario actualizado exitosamente.";
                } else {
                    $error = "Error al actualizar el usuario.";
                }
            }
        }
    }
}

// Obtener datos para edición
$usuario_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'Usuario')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM Usuario WHERE usuario_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $usuario_editar = $result->fetch_assoc();
}

// Obtener listas para los formularios
$empleados = [];
$stmt = $conn->prepare("SELECT empleado_id, nombre, apellido FROM Empleado ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$empleados = $result->fetch_all(MYSQLI_ASSOC);

// Obtener lista de usuarios
$sql = "SELECT u.*, 
               e.nombre as empleado_nombre, e.apellido as empleado_apellido
        FROM Usuario u
        LEFT JOIN Empleado e ON u.empleado_id = e.empleado_id
        WHERE $where_clause ORDER BY u.username";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$usuarios = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Sistema Bancario</title>
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
        
        .rol-administrador {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .rol-desarrollador {
            background: #007bff;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .rol-supervisor {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .password-note {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 12px;
            border: 1px solid #ffeaa7;
        }
        
        .current-user {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🔐 Gestionar Usuarios del Sistema</h1>
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
        <?php if (tienePermiso($rol, 'C', 'Usuario') || tienePermiso($rol, 'U', 'Usuario')): ?>
        <div class="form-section">
            <h2><?php echo $usuario_editar ? 'Editar Usuario' : 'Agregar Nuevo Usuario'; ?></h2>
            <form method="POST">
                <?php if ($usuario_editar): ?>
                    <input type="hidden" name="usuario_id" value="<?php echo $usuario_editar['usuario_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="empleado_id">Empleado (Opcional):</label>
                        <select id="empleado_id" name="empleado_id">
                            <option value="">Sin empleado asociado</option>
                            <?php foreach ($empleados as $empleado): ?>
                                <option value="<?php echo $empleado['empleado_id']; ?>" 
                                    <?php echo ($usuario_editar && $usuario_editar['empleado_id'] == $empleado['empleado_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empleado['apellido'] . ', ' . $empleado['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Nombre de Usuario:</label>
                        <input type="text" id="username" name="username" value="<?php echo $usuario_editar ? htmlspecialchars($usuario_editar['username']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" <?php echo !$usuario_editar ? 'required' : ''; ?>>
                        <?php if ($usuario_editar): ?>
                            <div class="password-note">Deje en blanco para mantener la contraseña actual</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="rol">Rol:</label>
                        <select id="rol" name="rol" required>
                            <option value="">Seleccione el rol</option>
                            <option value="Administrador" <?php echo ($usuario_editar && $usuario_editar['rol'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="Desarrollador" <?php echo ($usuario_editar && $usuario_editar['rol'] == 'Desarrollador') ? 'selected' : ''; ?>>Desarrollador</option>
                            <option value="Supervisor" <?php echo ($usuario_editar && $usuario_editar['rol'] == 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($usuario_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Usuario</button>
                        <a href="crud_usuarios.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Usuario</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Usuarios</h2>
            <form method="POST" class="search-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="username_buscar">Nombre de Usuario:</label>
                        <input type="text" id="username_buscar" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="rol_buscar">Rol:</label>
                        <select id="rol_buscar" name="rol">
                            <option value="">Todos los roles</option>
                            <option value="Administrador" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'Administrador') ? 'selected' : ''; ?>>Administrador</option>
                            <option value="Desarrollador" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'Desarrollador') ? 'selected' : ''; ?>>Desarrollador</option>
                            <option value="Supervisor" <?php echo (isset($_POST['rol']) && $_POST['rol'] == 'Supervisor') ? 'selected' : ''; ?>>Supervisor</option>
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
                </div>
                
                <div class="form-row">
                    <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                    <a href="crud_usuarios.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Usuarios del Sistema</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($usuarios); ?> usuario(s) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Empleado</th>
                        <th>Rol</th>
                        <th>Fecha Creación</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #666;">No se encontraron usuarios.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="<?php echo ($usuario['usuario_id'] == $_SESSION['usuario_id']) ? 'current-user' : ''; ?>">
                                <td><?php echo htmlspecialchars($usuario['usuario_id']); ?></td>
                                <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                                <td><?php echo $usuario['empleado_nombre'] ? htmlspecialchars($usuario['empleado_apellido'] . ', ' . $usuario['empleado_nombre']) : 'Sin empleado'; ?></td>
                                <td>
                                    <span class="rol-<?php echo strtolower($usuario['rol']); ?>">
                                        <?php echo htmlspecialchars($usuario['rol']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($usuario['fecha_creacion']))); ?></td>
                                <td><?php echo $usuario['ultimo_acceso'] ? htmlspecialchars(date('d/m/Y H:i', strtotime($usuario['ultimo_acceso']))) : 'Nunca'; ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'Usuario')): ?>
                                        <a href="?editar=<?php echo $usuario['usuario_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'Usuario') && $usuario['usuario_id'] != $_SESSION['usuario_id']): ?>
                                        <a href="?eliminar=<?php echo $usuario['usuario_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar este usuario?')">Eliminar</a>
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