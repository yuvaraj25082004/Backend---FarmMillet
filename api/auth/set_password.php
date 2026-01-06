<?php
header("Content-Type: application/json");
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$user_id  = $data['user_id'] ?? null;
$password = $data['password'] ?? null;
$confirm  = $data['confirm_password'] ?? null;

if (!$user_id || !$password || !$confirm) {
    echo json_encode([
        "status"=>"error",
        "message"=>"All fields required"
    ]);
    exit;
}

if ($password !== $confirm) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Passwords do not match"
    ]);
    exit;
}

$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $conn->prepare(
    "UPDATE users SET password=? WHERE user_id=?"
);
$stmt->bind_param("si", $hashed, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        "status"=>"success",
        "message"=>"Password set successfully"
    ]);
} else {
    echo json_encode([
        "status"=>"error",
        "message"=>"Failed to set password"
    ]);
}
