<?php
// api/controllers/UserController.php

class UserController
{
    private $conn;
    private $table_name = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        $query = "INSERT INTO " . $this->table_name . " 
                (name, username, password, role, phone, employee_id, status) 
                VALUES (:name, :username, :password, :role, :phone, :employee_id, :status)";

        $stmt = $this->conn->prepare($query);

        $hashed_password = password_hash($data['password'], PASSWORD_BCRYPT);

        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':username', $data['username']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $data['role']);
        $stmt->bindParam(':phone', $data['phone']);
        $stmt->bindParam(':employee_id', $data['employee_id']);
        $stmt->bindParam(':status', $data['status']);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "User created successfully."];
        }

        return ["success" => false, "message" => "Unable to create user."];
    }

    public function getAll()
    {
        $query = "SELECT user_id, name, username, role, phone, employee_id, status, created_at FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDelete($user_id)
    {
        $query = "UPDATE " . $this->table_name . " SET status = 'inactive' WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "User deactivated successfully."];
        }
        return ["success" => false, "message" => "Unable to deactivate user."];
    }
}
?>