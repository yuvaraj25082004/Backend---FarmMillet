<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

/* ---------- SET ROLE ---------- */
$role = "shg_fpo";

/* ---------- SAFE INPUT ---------- */
$organization_name = trim($data['organization_name'] ?? '');
$name              = trim($data['name'] ?? '');
$mobile            = trim($data['mobile_number'] ?? '');
$email             = trim($data['email'] ?? '');
$password          = trim($data['password'] ?? '');

$street             = trim($data['street'] ?? '');
$city               = trim($data['city'] ?? '');
$state              = trim($data['state'] ?? '');
$pincode            = trim($data['pincode'] ?? '');

$collection_center  = trim($data['collection_center_location'] ?? '');

/* ---------- VALIDATION ---------- */

// Organization Name
if (strlen($organization_name) < 3) {
    response(false, "Organization name must be at least 3 characters");
}

// Contact Person Name
if (strlen($name) < 3) {
    response(false, "Name must be at least 3 characters");
}

// Mobile Number
if (!preg_match("/^[6-9]\d{9}$/", $mobile)) {
    response(false, "Invalid mobile number");
}

// Email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    response(false, "Invalid email address");
}

// Password
if (strlen($password) < 6) {
    response(false, "Password must be minimum 6 characters");
}

// Address
if (empty($street) || empty($city) || empty($state)) {
    response(false, "Street, city and state are required");
}

// Pincode
if (!preg_match("/^[0-9]{6}$/", $pincode)) {
    response(false, "Invalid pincode");
}

// Collection Center Location
if (strlen($collection_center) < 3) {
    response(false, "Collection center location is required");
}

/* ---------- DUPLICATE CHECK ---------- */
$check_sql = "SELECT id FROM shg_fpo_users WHERE email = ? OR mobile_number = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $mobile);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    response(false, "Email or mobile already registered");
}
mysqli_stmt_close($stmt);

/* ---------- INSERT ---------- */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$insert_sql = "INSERT INTO shg_fpo_users
(role, organization_name, name, mobile_number, email, password,
 street, city, state, pincode, collection_center_location)
VALUES (?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param(
    $stmt,
    "sssssssssss",
    $role,
    $organization_name,
    $name,
    $mobile,
    $email,
    $hashed_password,
    $street,
    $city,
    $state,
    $pincode,
    $collection_center
);

if (mysqli_stmt_execute($stmt)) {
    response(true, "SHG / FPO registration successful");
} else {
    response(false, "Registration failed");
}

mysqli_stmt_close($stmt);

/* ---------- RESPONSE ---------- */
function response($status, $message) {
    echo json_encode([
        "status"  => $status,
        "message" => $message
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>
