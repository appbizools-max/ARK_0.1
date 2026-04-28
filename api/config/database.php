<?php
// api/config/database.php

define('DB_HOST', 'localhost');
define('DB_NAME', 'ark_pm_system');
define('DB_USER', 'root');
define('DB_PASS', '');

class Database
{
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );

            $this->conn->exec("set names utf8");
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}

// ✅ Extra mysqli connection for inventory APIs
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("MySQLi Connection failed: " . $conn->connect_error);
}
?>