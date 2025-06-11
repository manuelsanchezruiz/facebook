<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Configuración de conexión a la base de datos PostgreSQL
$host = "dpg-d14b7149c44c73d4fsvg-a.oregon-postgres.render.com";
$user = "manuel";
$password = "Zyk1wngn1RgXLK2MNCMZL5yBRNjjEsDW";
$dbname = "base_ubicaciones";
$port = "5432";

// Obtener la IP real del cliente (teniendo en cuenta proxies)
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

try {
    // Conectar a la base de datos PostgreSQL
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;user=$user;password=$password";
    $conn = new PDO($dsn);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Obtener los datos enviados desde el cliente
    $data = json_decode(file_get_contents("php://input"), true);
    
    // Datos de geolocalización
    $latitude = isset($data['latitude']) ? floatval($data['latitude']) : null;
    $longitude = isset($data['longitude']) ? floatval($data['longitude']) : null;
    $accuracy = isset($data['accuracy']) ? floatval($data['accuracy']) : null;
    
    // Datos del usuario
    $username = isset($data['username']) ? $data['username'] : 'Anónimo';
    $os = isset($data['os']) ? $data['os'] : 'Desconocido';
    $userAgent = isset($data['userAgent']) ? substr($data['userAgent'], 0, 255) : 'Desconocido';
    $timestamp = isset($data['timestamp']) ? $data['timestamp'] : date('Y-m-d H:i:s');
    $error = isset($data['error']) ? $data['error'] : null;
    
    // Obtener IP del cliente (si no viene en los datos)
    $ip = isset($data['ip']) ? $data['ip'] : getClientIP();

    // Insertar en la base de datos
    $sql = "INSERT INTO ubicaciones (
        username, 
        ip_address, 
        os, 
        user_agent, 
        latitud, 
        longitud, 
        precision_gps, 
        fecha_hora, 
        error
    ) VALUES (
        :username, 
        :ip, 
        :os, 
        :user_agent, 
        :latitude, 
        :longitude, 
        :accuracy, 
        :timestamp, 
        :error
    )";

    $stmt = $conn->prepare($sql);
    
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':ip', $ip);
    $stmt->bindParam(':os', $os);
    $stmt->bindParam(':user_agent', $userAgent);
    $stmt->bindParam(':latitude', $latitude);
    $stmt->bindParam(':longitude', $longitude);
    $stmt->bindParam(':accuracy', $accuracy);
    $stmt->bindParam(':timestamp', $timestamp);
    $stmt->bindParam(':error', $error);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Datos guardados correctamente"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error al guardar los datos"]);
    }
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error de conexión: " . $e->getMessage(),
        "data_received" => $data
    ]);
}

$conn = null;
?>