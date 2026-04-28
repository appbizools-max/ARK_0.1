<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

include '../config/database.php';

global $conn;
// 🔥 change DB name if different

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "DB Connection Failed"
    ]);
    exit;
}

/* =========================
   GET VENDORS
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $result = $conn->query("SELECT name FROM vendors");

    $vendors = [];

    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row['name'];
    }

    echo json_encode([
        "status" => "success",
        "data" => $vendors
    ]);
}

/* =========================
   ADD NEW VENDOR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    $vendor = $data['vendor'] ?? '';

    if ($vendor == '') {
        echo json_encode([
            "status" => "error",
            "message" => "Vendor name required"
        ]);
        exit;
    }

    // check duplicate
    $check = $conn->prepare("SELECT id FROM vendors WHERE name=?");
    $check->bind_param("s", $vendor);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        echo json_encode([
            "status" => "success",
            "message" => "Already exists"
        ]);
        exit;
    }

    // insert
    $stmt = $conn->prepare("INSERT INTO vendors (name) VALUES (?)");
    $stmt->bind_param("s", $vendor);

    if ($stmt->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "Vendor added"
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Insert failed"
        ]);
    }
}

$conn->close();
?>`