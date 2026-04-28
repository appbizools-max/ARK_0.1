<?php
include('../config/database.php'); // nee db file

$data = json_decode(file_get_contents("php://input"), true);

$project_id = $data['project_id'];
$tasks = $data['tasks'];

// OLD DATA DELETE
$conn->query("DELETE FROM project_timeline WHERE project_id = '$project_id'");

// INSERT NEW TASKS
foreach ($tasks as $task) {

    $name = $task['name'];
    $category = $task['category'];
    $start = $task['startDate'];
    $end = $task['endDate'];

    $duration = (strtotime($end) - strtotime($start)) / (60 * 60 * 24);

    $sql = "INSERT INTO project_timeline 
    (project_id, task_name, task_category, start_date, end_date, duration_days)
    VALUES 
    ('$project_id', '$name', '$category', '$start', '$end', '$duration')";

    $conn->query($sql);
}

echo json_encode([
    "success" => true
]);