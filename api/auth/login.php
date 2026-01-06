<?php
header("Content-Type: application/json");
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$identity = trim($data['identity'] ?? '');
$password = trim($data['password'] ?? '');

if (!$identity || !$password) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Credentials required"
    ]);
    exit;
}

$stmt = $conn->prepare(
    "SELECT user_id, full_name, password, role 
     FROM users 
     WHERE email=? OR mobile=?"
);
$stmt->bind_param("ss", $identity, $identity);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"User not found"
    ]);
    exit;
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Invalid password"
    ]);
    exit;
}

echo json_encode([
    "status"=>"success",
    "user_id"=>$user['user_id'],
    "name"=>$user['full_name'],
    "role"=>$user['role']
]);
