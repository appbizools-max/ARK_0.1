<?php
// api/init_db.php
require_once 'config/database.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME);
    echo "Database " . DB_NAME . " checked/created successfully.<br>";

    $db = new Database();
    $conn = $db->getConnection();

    $sql_file = '../database/schema.sql';
    $sql = file_get_contents($sql_file);

    $queries = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($queries as $query) {
        if (!empty($query)) {
            $conn->exec($query);
        }
    }

    echo "Schema imported successfully. Tables are ready.<br>";
    echo "<a href='seed.php'>Click here to Seed the Admin Account</a>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>