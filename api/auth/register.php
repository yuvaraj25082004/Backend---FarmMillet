<?php
header("Content-Type: application/json");
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$full_name = trim($data['full_name'] ?? '');
$email     = trim($data['email'] ?? '');
$mobile    = trim($data['mobile'] ?? '');
$role      = 'farmer';

if (!$full_name || !$email || !$mobile) {
    echo json_encode(["status"=>"error","message"=>"All fields required"]);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO users (full_name,email,mobile,role) VALUES (?,?,?,?)"
);
$stmt->bind_param("ssss", $full_name, $email, $mobile, $role);

if ($stmt->execute()) {
    echo json_encode([
        "status"=>"success",
        "user_id"=>$stmt->insert_id
    ]);
} else {
    echo json_encode(["status"=>"error","message"=>"User already exists"]);
}
