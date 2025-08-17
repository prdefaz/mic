<?php
/**
 * CRUD de Tarjetas de Crédito - Sistema Bancario
 * Archivo: crud_tarjetas_credito.php
 * Descripción: Gestión completa de tarjetas de crédito
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
if (!tienePermiso($rol, 'R', 'TarjetaCredito')) {
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
    $cuenta_id = trim($_POST['cuenta_id']);
    
    if (!empty($cliente_id)) {
        $where_clause .= " AND tc.cliente_id = ?";
        $params[] = $cliente_id;
        $param_types .= "i";
    }
    
    if (!empty($tipo)) {
        $where_clause .= " AND tc.tipo = ?";
        $params[] = $tipo;
        $param_types .= "s";
    }
    
    if (!empty($estado)) {
        $where_clause .= " AND tc.estado = ?";
        $params[] = $estado;
        $param_types .= "s";
    }
    
    if (!empty($cuenta_id)) {
        $where_clause .= " AND tc.cuenta_id = ?";
        $params[] = $cuenta_id;
        $param_types .= "i";
    }
}

// Procesar eliminación
if (isset($_GET['eliminar']) && tienePermiso($rol, 'D', 'TarjetaCredito')) {
    $id = (int)$_GET['eliminar'];
    
    $delete_stmt = $conn->prepare("DELETE FROM TarjetaCredito WHERE tarjeta_id = ?");
    $delete_stmt->bind_param("i", $id);
    
    if ($delete_stmt->execute()) {
        $mensaje = "Tarjeta de crédito eliminada exitosamente.";
    } else {
        $error = "Error al eliminar la tarjeta de crédito.";
    }
}

// Procesar formulario de agregar/editar
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['agregar']) || isset($_POST['editar']))) {
    $cuenta_id = (int)$_POST['cuenta_id'];
    $cliente_id = (int)$_POST['cliente_id'];
    $tipo = $_POST['tipo'];
    $numero = trim($_POST['numero']);
    $limite_credito = (float)$_POST['limite_credito'];
    $fecha_emision = $_POST['fecha_emision'];
    $fecha_vencimiento = $_POST['fecha_vencimiento'];
    $estado = $_POST['estado'];
    
    if (empty($cuenta_id) || empty($cliente_id) || empty($tipo) || empty($numero) || empty($limite_credito) || empty($fecha_emision) || empty($fecha_vencimiento) || empty($estado)) {
        $error = "Todos los campos son obligatorios.";
    } else {
        // Validar formato del número de tarjeta (16 dígitos)
        if (!preg_match('/^\d{16}$/', $numero)) {
            $error = "El número de tarjeta debe tener exactamente 16 dígitos.";
        } else {
            if (isset($_POST['agregar']) && tienePermiso($rol, 'C', 'TarjetaCredito')) {
                $stmt = $conn->prepare("INSERT INTO TarjetaCredito (cuenta_id, cliente_id, tipo, numero, limite_credito, fecha_emision, fecha_vencimiento, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissdsss", $cuenta_id, $cliente_id, $tipo, $numero, $limite_credito, $fecha_emision, $fecha_vencimiento, $estado);
                
                if ($stmt->execute()) {
                    $mensaje = "Tarjeta de crédito agregada exitosamente.";
                } else {
                    $error = "Error al agregar la tarjeta de crédito.";
                }
            } elseif (isset($_POST['editar']) && tienePermiso($rol, 'U', 'TarjetaCredito')) {
                $id = (int)$_POST['tarjeta_id'];
                $stmt = $conn->prepare("UPDATE TarjetaCredito SET cuenta_id = ?, cliente_id = ?, tipo = ?, numero = ?, limite_credito = ?, fecha_emision = ?, fecha_vencimiento = ?, estado = ? WHERE tarjeta_id = ?");
                $stmt->bind_param("iissdsssi", $cuenta_id, $cliente_id, $tipo, $numero, $limite_credito, $fecha_emision, $fecha_vencimiento, $estado, $id);
                
                if ($stmt->execute()) {
                    $mensaje = "Tarjeta de crédito actualizada exitosamente.";
                } else {
                    $error = "Error al actualizar la tarjeta de crédito.";
                }
            }
        }
    }
}

// Obtener datos para edición
$tarjeta_editar = null;
if (isset($_GET['editar']) && tienePermiso($rol, 'U', 'TarjetaCredito')) {
    $id = (int)$_GET['editar'];
    $stmt = $conn->prepare("SELECT * FROM TarjetaCredito WHERE tarjeta_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $tarjeta_editar = $result->fetch_assoc();
}

// Obtener listas para los formularios
$cuentas = [];
$stmt = $conn->prepare("SELECT c.cuenta_id, c.tipo as cuenta_tipo, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido 
                        FROM Cuenta c 
                        LEFT JOIN Cliente cl ON c.cliente_id = cl.cliente_id 
                        WHERE c.estado = 'Activa' 
                        ORDER BY cl.apellido, cl.nombre");
$stmt->execute();
$result = $stmt->get_result();
$cuentas = $result->fetch_all(MYSQLI_ASSOC);

$clientes = [];
$stmt = $conn->prepare("SELECT cliente_id, nombre, apellido FROM Cliente ORDER BY apellido, nombre");
$stmt->execute();
$result = $stmt->get_result();
$clientes = $result->fetch_all(MYSQLI_ASSOC);

// Obtener lista de tarjetas de crédito
$sql = "SELECT tc.*, 
               c.tipo as cuenta_tipo,
               cl.nombre as cliente_nombre, cl.apellido as cliente_apellido
        FROM TarjetaCredito tc
        LEFT JOIN Cuenta c ON tc.cuenta_id = c.cuenta_id
        LEFT JOIN Cliente cl ON tc.cliente_id = cl.cliente_id
        WHERE $where_clause ORDER BY tc.fecha_emision DESC";
$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$tarjetas = $result->fetch_all(MYSQLI_ASSOC);

// Función para enmascarar número de tarjeta
function enmascararNumero($numero) {
    return substr($numero, 0, 4) . ' **** **** ' . substr($numero, -4);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Tarjetas de Crédito - Sistema Bancario</title>
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
        
        .limite-credito {
            font-weight: 600;
            color: #28a745;
        }
        
        .tipo-tarjeta {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .tipo-clasica {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .tipo-oro {
            background: #fff3cd;
            color: #856404;
        }
        
        .tipo-platino {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .tipo-negra {
            background: #343a40;
            color: white;
        }
        
        .estado-activa {
            color: #28a745;
            font-weight: 600;
        }
        
        .estado-bloqueada {
            color: #dc3545;
            font-weight: 600;
        }
        
        .estado-cancelada {
            color: #6c757d;
            font-weight: 600;
        }
        
        .numero-tarjeta {
            font-family: 'Courier New', monospace;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>💳 Gestionar Tarjetas de Crédito</h1>
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
        <?php if (tienePermiso($rol, 'C', 'TarjetaCredito') || tienePermiso($rol, 'U', 'TarjetaCredito')): ?>
        <div class="form-section">
            <h2><?php echo $tarjeta_editar ? 'Editar Tarjeta de Crédito' : 'Agregar Nueva Tarjeta de Crédito'; ?></h2>
            <form method="POST">
                <?php if ($tarjeta_editar): ?>
                    <input type="hidden" name="tarjeta_id" value="<?php echo $tarjeta_editar['tarjeta_id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="cuenta_id">Cuenta:</label>
                        <select id="cuenta_id" name="cuenta_id" required>
                            <option value="">Seleccione una cuenta</option>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <option value="<?php echo $cuenta['cuenta_id']; ?>" 
                                    <?php echo ($tarjeta_editar && $tarjeta_editar['cuenta_id'] == $cuenta['cuenta_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cuenta['cliente_apellido'] . ', ' . $cuenta['cliente_nombre'] . ' - ' . $cuenta['cuenta_tipo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cliente_id">Cliente:</label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Seleccione un cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['cliente_id']; ?>" 
                                    <?php echo ($tarjeta_editar && $tarjeta_editar['cliente_id'] == $cliente['cliente_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['apellido'] . ', ' . $cliente['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="tipo">Tipo de Tarjeta:</label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Seleccione el tipo</option>
                            <option value="Clásica" <?php echo ($tarjeta_editar && $tarjeta_editar['tipo'] == 'Clásica') ? 'selected' : ''; ?>>Clásica</option>
                            <option value="Oro" <?php echo ($tarjeta_editar && $tarjeta_editar['tipo'] == 'Oro') ? 'selected' : ''; ?>>Oro</option>
                            <option value="Platino" <?php echo ($tarjeta_editar && $tarjeta_editar['tipo'] == 'Platino') ? 'selected' : ''; ?>>Platino</option>
                            <option value="Negra" <?php echo ($tarjeta_editar && $tarjeta_editar['tipo'] == 'Negra') ? 'selected' : ''; ?>>Negra</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="numero">Número de Tarjeta:</label>
                        <input type="text" id="numero" name="numero" maxlength="16" pattern="\d{16}" value="<?php echo $tarjeta_editar ? htmlspecialchars($tarjeta_editar['numero']) : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="limite_credito">Límite de Crédito:</label>
                        <input type="number" id="limite_credito" name="limite_credito" step="0.01" min="0" value="<?php echo $tarjeta_editar ? $tarjeta_editar['limite_credito'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado:</label>
                        <select id="estado" name="estado" required>
                            <option value="">Seleccione el estado</option>
                            <option value="Activa" <?php echo ($tarjeta_editar && $tarjeta_editar['estado'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                            <option value="Bloqueada" <?php echo ($tarjeta_editar && $tarjeta_editar['estado'] == 'Bloqueada') ? 'selected' : ''; ?>>Bloqueada</option>
                            <option value="Cancelada" <?php echo ($tarjeta_editar && $tarjeta_editar['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="fecha_emision">Fecha de Emisión:</label>
                        <input type="date" id="fecha_emision" name="fecha_emision" value="<?php echo $tarjeta_editar ? $tarjeta_editar['fecha_emision'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_vencimiento">Fecha de Vencimiento:</label>
                        <input type="date" id="fecha_vencimiento" name="fecha_vencimiento" value="<?php echo $tarjeta_editar ? $tarjeta_editar['fecha_vencimiento'] : ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <?php if ($tarjeta_editar): ?>
                        <button type="submit" name="editar" class="btn btn-primary">Actualizar Tarjeta</button>
                        <a href="crud_tarjetas_credito.php" class="btn btn-warning">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="agregar" class="btn btn-primary">Agregar Tarjeta</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <!-- Formulario de Búsqueda -->
        <div class="form-section">
            <h2>Buscar Tarjetas de Crédito</h2>
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
                            <option value="Clásica" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Clásica') ? 'selected' : ''; ?>>Clásica</option>
                            <option value="Oro" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Oro') ? 'selected' : ''; ?>>Oro</option>
                            <option value="Platino" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Platino') ? 'selected' : ''; ?>>Platino</option>
                            <option value="Negra" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Negra') ? 'selected' : ''; ?>>Negra</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="estado_buscar">Estado:</label>
                        <select id="estado_buscar" name="estado">
                            <option value="">Todos los estados</option>
                            <option value="Activa" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Activa') ? 'selected' : ''; ?>>Activa</option>
                            <option value="Bloqueada" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Bloqueada') ? 'selected' : ''; ?>>Bloqueada</option>
                            <option value="Cancelada" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="cuenta_id_buscar">Cuenta:</label>
                        <select id="cuenta_id_buscar" name="cuenta_id">
                            <option value="">Todas las cuentas</option>
                            <?php foreach ($cuentas as $cuenta): ?>
                                <option value="<?php echo $cuenta['cuenta_id']; ?>" 
                                    <?php echo (isset($_POST['cuenta_id']) && $_POST['cuenta_id'] == $cuenta['cuenta_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cuenta['cliente_apellido'] . ', ' . $cuenta['cliente_nombre'] . ' - ' . $cuenta['cuenta_tipo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                    <a href="crud_tarjetas_credito.php" class="btn btn-warning">Limpiar</a>
                </div>
            </form>
        </div>
        
        <!-- Tabla de Resultados -->
        <div class="table-section">
            <h2>Lista de Tarjetas de Crédito</h2>
            
            <?php if (isset($_POST['buscar'])): ?>
                <div class="results-info">
                    Se encontraron <?php echo count($tarjetas); ?> tarjeta(s) que coinciden con la búsqueda.
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Cuenta</th>
                        <th>Tipo</th>
                        <th>Número</th>
                        <th>Límite</th>
                        <th>Estado</th>
                        <th>Emisión</th>
                        <th>Vencimiento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tarjetas)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: #666;">No se encontraron tarjetas de crédito.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tarjetas as $tarjeta): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tarjeta['tarjeta_id']); ?></td>
                                <td><?php echo htmlspecialchars($tarjeta['cliente_apellido'] . ', ' . $tarjeta['cliente_nombre']); ?></td>
                                <td><?php echo htmlspecialchars($tarjeta['cuenta_tipo']); ?></td>
                                <td>
                                    <span class="tipo-tarjeta tipo-<?php echo strtolower($tarjeta['tipo']); ?>">
                                        <?php echo htmlspecialchars($tarjeta['tipo']); ?>
                                    </span>
                                </td>
                                <td class="numero-tarjeta"><?php echo enmascararNumero($tarjeta['numero']); ?></td>
                                <td class="limite-credito">$<?php echo number_format($tarjeta['limite_credito'], 2); ?></td>
                                <td class="estado-<?php echo strtolower($tarjeta['estado']); ?>"><?php echo htmlspecialchars($tarjeta['estado']); ?></td>
                                <td><?php echo htmlspecialchars($tarjeta['fecha_emision']); ?></td>
                                <td><?php echo htmlspecialchars($tarjeta['fecha_vencimiento']); ?></td>
                                <td class="actions">
                                    <?php if (tienePermiso($rol, 'U', 'TarjetaCredito')): ?>
                                        <a href="?editar=<?php echo $tarjeta['tarjeta_id']; ?>" class="btn btn-warning btn-sm">Editar</a>
                                    <?php endif; ?>
                                    
                                    <?php if (tienePermiso($rol, 'D', 'TarjetaCredito')): ?>
                                        <a href="?eliminar=<?php echo $tarjeta['tarjeta_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Está seguro de que desea eliminar esta tarjeta de crédito?')">Eliminar</a>
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