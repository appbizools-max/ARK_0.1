<?php
header("Content-Type: application/json");

include "../../config/database.php";

$data = json_decode(file_get_contents("php://input"), true);

$project_id = $data['project_id'] ?? '';
$task_id    = $data['task_id'] ?? '';
$col_index  = $data['col_index'] ?? '';
$state      = $data['state'] ?? 0;
$user_id    = $data['user_id'] ?? '';

if (!$project_id || $task_id === '' || $col_index === '') {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

$sql = "INSERT INTO timeline_cells (project_id, task_id, col_index, state, updated_by)
        VALUES (?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            state = VALUES(state),
            updated_by = VALUES(updated_by)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("siiis", $project_id, $task_id, $col_index, $state, $user_id);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->error]);
}