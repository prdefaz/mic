<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config.php';

// Obtener parámetros GET de búsqueda
$nombre = isset($_GET['nombre']) ? $_GET['nombre'] : null;
$apellido = isset($_GET['apellido']) ? $_GET['apellido'] : null;
$email = isset($_GET['email']) ? $_GET['email'] : null;

// Obtener la ruta solicitada para operaciones CRUD
$request_uri = $_SERVER['REQUEST_URI'];
$script_name = $_SERVER['SCRIPT_NAME'];
$path = trim(str_replace(dirname($script_name), '', $request_uri), '/');
$segments = explode('/', $path);
$id = isset($segments[1]) && is_numeric($segments[1]) ? (int)$segments[1] : null;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        if ($id) {
            // Obtener un cliente específico por ID
            $stmt = $conn->prepare("SELECT * FROM Cliente WHERE cliente_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                echo json_encode($result->fetch_assoc());
            } else {
                http_response_code(404);
                echo json_encode(["error" => "Cliente no encontrado"]);
            }
        } else {
            // Construir consulta de búsqueda
            $sql = "SELECT * FROM Cliente WHERE 1=1";
            $params = [];
            $types = "";
            
            if ($nombre) {
                $sql .= " AND nombre LIKE ?";
                $params[] = "%$nombre%";
                $types .= "s";
            }
            
            if ($apellido) {
                $sql .= " AND apellido LIKE ?";
                $params[] = "%$apellido%";
                $types .= "s";
            }
            
            if ($email) {
                $sql .= " AND email LIKE ?";
                $params[] = "%$email%";
                $types .= "s";
            }
            
            $sql .= " ORDER BY apellido, nombre";
            
            $stmt = $conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $clientes = [];
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }
            
            echo json_encode($clientes);
        }
        break;
        
    case 'POST':
        // Crear nuevo cliente
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!empty($data['nombre']) && !empty($data['apellido'])) {
            $nombre = $data['nombre'];
            $apellido = $data['apellido'];
            $direccion = $data['direccion'] ?? '';
            $telefono = $data['telefono'] ?? '';
            $email = $data['email'] ?? '';
            $fecha_registro = $data['fecha_registro'] ?? date('Y-m-d');
            
            $stmt = $conn->prepare("INSERT INTO Cliente (nombre, apellido, direccion, telefono, email, fecha_registro) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $nombre, $apellido, $direccion, $telefono, $email, $fecha_registro);
            
            if ($stmt->execute()) {
                http_response_code(201);
                echo json_encode(["mensaje" => "Cliente creado exitosamente", "cliente_id" => $stmt->insert_id]);
            } else {
                http_response_code(500);
                echo json_encode(["error" => "Error al crear el cliente"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
        }
        break;
        
    case 'PUT':
        // Actualizar cliente existente
        if ($id) {
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!empty($data['nombre']) && !empty($data['apellido'])) {
                $nombre = $data['nombre'];
                $apellido = $data['apellido'];
                $direccion = $data['direccion'] ?? '';
                $telefono = $data['telefono'] ?? '';
                $email = $data['email'] ?? '';
                $fecha_registro = $data['fecha_registro'] ?? date('Y-m-d');
                
                $stmt = $conn->prepare("UPDATE Cliente SET nombre = ?, apellido = ?, direccion = ?, telefono = ?, email = ?, fecha_registro = ? WHERE cliente_id = ?");
                $stmt->bind_param("ssssssi", $nombre, $apellido, $direccion, $telefono, $email, $fecha_registro, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(["mensaje" => "Cliente actualizado exitosamente"]);
                } else {
                    http_response_code(500);
                    echo json_encode(["error" => "Error al actualizar el cliente"]);
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "Datos incompletos"]);
            }
        } else {
            http_response_code(400);
            echo json_encode(["error" => "ID de cliente no proporcionado"]);
        }
        break;
        
        case 'DELETE':
            // Obtener ID de la URL o de los parámetros
            $input = json_decode(file_get_contents('php://input'), true);
            $id = $input['id'] ?? $id; // Intenta obtener el ID de ambas formas
            
            if ($id) {
                // Verificar cuentas asociadas (código existente)
                $check_stmt = $conn->prepare("SELECT COUNT(*) as total FROM Cuenta WHERE cliente_id = ?");
                $check_stmt->bind_param("i", $id);
                $check_stmt->execute();
                $result = $check_stmt->get_result();
                $count = $result->fetch_assoc()['total'];
                
                if ($count > 0) {
                    http_response_code(400);
                    echo json_encode(["error" => "Cliente tiene cuentas asociadas"]);
                } else {
                    $delete_stmt = $conn->prepare("DELETE FROM Cliente WHERE cliente_id = ?");
                    $delete_stmt->bind_param("i", $id);
                    
                    if ($delete_stmt->execute()) {
                        echo json_encode(["message" => "Cliente eliminado"]);
                    } else {
                        http_response_code(500);
                        echo json_encode(["error" => "Error al eliminar"]);
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(["error" => "ID no proporcionado"]);
            }
            break;
        
    case 'OPTIONS':
        // Respuesta para preflight requests
        http_response_code(200);
        break;
        
    default:
        http_response_code(405);
        echo json_encode(["error" => "Método no permitido"]);
        break;
}

$conn->close();
?>