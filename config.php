<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'radio_guardia';

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Error de conexion: ' . $conn->connect_error]);
    exit;
}
