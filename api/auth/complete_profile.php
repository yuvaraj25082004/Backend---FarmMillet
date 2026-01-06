<?php
header("Content-Type: application/json");
require_once("../../config/db.php");

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(["status"=>"error","message"=>"User ID required"]);
    exit;
}

$stmt = $conn->prepare("


INSERT INTO farmers
(user_id, street, city, state, pincode, farm_location, millet_type, quantity_kg, harvest_date, bank_account, ifsc_code)
VALUES (?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->bind_param(
"issssssisss",
$user_id,
$data['street'],
$data['city'],
$data['state'],
$data['pincode'],
$data['farm_location'],
$data['millet_type'],
$data['quantity_kg'],
$data['harvest_date'],
$data['bank_account'],
$data['ifsc_code']
);

if ($stmt->execute()) {
    echo json_encode(["status"=>"success","message"=>"Profile saved"]);
} else {
    echo json_encode(["status"=>"error","message"=>"Profile failed"]);
}

