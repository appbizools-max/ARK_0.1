<?php
header("Content-Type: application/json");

include "../../config/database.php";

$project_id = $_GET['project_id'] ?? '';

if (!$project_id) {
    echo json_encode(["success" => false, "message" => "Project ID missing"]);
    exit;
}

$sql = "SELECT task_id, col_index, state FROM timeline_cells WHERE project_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $project_id);
$stmt->execute();

$result = $stmt->get_result();

$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);