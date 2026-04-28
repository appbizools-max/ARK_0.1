<?php
// api/controllers/AuthController.php

class AuthController
{
    private $conn;
    private $table_name = "users";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function login($username, $password)
    {
        $query = "SELECT user_id, name, username, password, role, status FROM " . $this->table_name . " WHERE username = :username LIMIT 0,1";
        $stmt = $this->conn->prepare($query);

        $username = htmlspecialchars(strip_tags($username));
        $stmt->bindParam(':username', $username);
        $stmt->execute();

        $num = $stmt->rowCount();

        if ($num > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $user_id = $row['user_id'];
            $name = $row['name'];
            $hashed_password = $row['password'];
            $role = $row['role'];
            $status = $row['status'];

            if ($status !== 'active') {
                return ["success" => false, "message" => "Account is inactive."];
            }

            if (password_verify($password, $hashed_password)) {
                $token = base64_encode(json_encode(["user_id" => $user_id, "role" => $role, "exp" => time() + 36000]));

                return [
                    "success" => true,
                    "message" => "Login successful",
                    "data" => [
                        "user_id" => $user_id,
                        "name" => $name,
                        "username" => $username,
                        "role" => $role,
                        "token" => $token
                    ]
                ];
            }
        }

        return ["success" => false, "message" => "Invalid username or password."];
    }
}
?>