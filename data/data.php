<?php
header('Content-Type: application/json; charset=utf-8');

$host = '127.0.0.1';
$dbName = 'biblioteca_db';
$user = 'root';
$password = '';

$mysqli = new mysqli($host, $user, $password, $dbName);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo json_encode(['error' => 'Conexión fallida: ' . $mysqli->connect_error]);
    exit;
}

$mysqli->set_charset('utf8mb4');
$query = 'SELECT id, titulo, autor, estado FROM libros';
$result = $mysqli->query($query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Error en consulta: ' . $mysqli->error]);
    $mysqli->close();
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);

$result->free();
$mysqli->close();
