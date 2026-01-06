<?php
header("Content-Type: application/json");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include "../../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

/* ---------- SET ROLE ---------- */
$role = "farmer";

/* ---------- SAFE INPUT ---------- */
$name           = trim($data['name'] ?? '');
$mobile         = trim($data['mobile_number'] ?? '');
$email          = trim($data['email'] ?? '');
$password       = trim($data['password'] ?? '');

$street         = trim($data['street'] ?? '');
$city           = trim($data['city'] ?? '');
$state          = trim($data['state'] ?? '');
$pincode        = trim($data['pincode'] ?? '');

$farm_location  = trim($data['farm_location'] ?? '');
$account_number = trim($data['account_number'] ?? '');
$ifsc_code      = strtoupper(trim($data['ifsc_code'] ?? ''));

/* ---------- VALIDATION ---------- */
if (strlen($name) < 3) response(false, "Name must be at least 3 characters");
if (!preg_match("/^[6-9]\d{9}$/", $mobile)) response(false, "Invalid mobile number");
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) response(false, "Invalid email address");
if (strlen($password) < 6) response(false, "Password must be minimum 6 characters");
if (empty($street) || empty($city) || empty($state)) response(false, "Address required");
if (!preg_match("/^[0-9]{6}$/", $pincode)) response(false, "Invalid pincode");

if (empty($farm_location)) response(false, "Farm location required");
if (!preg_match("/^[0-9]{9,18}$/", $account_number)) response(false, "Invalid account number");
if (!preg_match("/^[A-Z]{4}0[A-Z0-9]{6}$/", $ifsc_code)) response(false, "Invalid IFSC code");

/* ---------- DUPLICATE CHECK (FIXED TABLE) ---------- */
$check_sql = "SELECT id FROM farmer WHERE email = ? OR mobile_number = ?";
$stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($stmt, "ss", $email, $mobile);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);

if (mysqli_stmt_num_rows($stmt) > 0) {
    response(false, "Email or mobile already registered");
}
mysqli_stmt_close($stmt);

/* ---------- INSERT (FIXED TABLE) ---------- */
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$insert_sql = "INSERT INTO farmer
(role, name, mobile_number, email, password, street, city, state, pincode,
 farm_location, account_number, ifsc_code)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";

$stmt = mysqli_prepare($conn, $insert_sql);
mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssss",
    $role,
    $name,
    $mobile,
    $email,
    $hashed_password,
    $street,
    $city,
    $state,
    $pincode,
    $farm_location,
    $account_number,
    $ifsc_code
);

if (mysqli_stmt_execute($stmt)) {
    response(true, "Farmer registration successful");
} else {
    response(false, "Registration failed");
}

mysqli_stmt_close($stmt);

function response($status, $message) {
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit;
}
?>
