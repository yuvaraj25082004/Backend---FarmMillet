<?php
$conn = new mysqli("localhost", "root", "", "farmmillet_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status"=>"error","message"=>"DB Connection Failed"]);
    exit;
}
?>
