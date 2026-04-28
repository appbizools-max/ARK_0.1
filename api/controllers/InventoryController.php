<?php
// api/controllers/InventoryController.php

class InventoryController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function createRequest($data)
    {
        $query = "INSERT INTO material_requests 
                (project_id, item_name, quantity, unit, requested_by, status, urgency) 
                VALUES (:project_id, :item_name, :quantity, :unit, :requested_by, 'pending', :urgency)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':project_id', $data['project_id']);
        $stmt->bindParam(':item_name', $data['item_name']);
        $stmt->bindParam(':quantity', $data['quantity']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':requested_by', $data['requested_by']);
        $stmt->bindParam(':urgency', $data['urgency']);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Material request submitted."];
        }
        return ["success" => false, "message" => "Failed to submit request."];
    }

    public function getProjectInventory($project_id)
    {
        $query = "SELECT * FROM inventory WHERE project_id = :p_id ORDER BY last_updated DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':p_id', $project_id);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingRequests($project_id = null)
    {
        $query = "SELECT mr.*, p.project_name, u.name as requester_name 
                  FROM material_requests mr
                  JOIN projects p ON mr.project_id = p.project_id
                  JOIN users u ON mr.requested_by = u.user_id";

        if ($project_id) {
            $query .= " WHERE mr.project_id = :p_id";
        }
        $query .= " ORDER BY mr.created_at DESC";

        $stmt = $this->conn->prepare($query);
        if ($project_id) {
            $stmt->bindParam(':p_id', $project_id);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRequestStatus($request_id, $status, $updated_by)
    {
        $query = "UPDATE material_requests SET status = :status, approved_by = :user_id WHERE request_id = :r_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':user_id', $updated_by);
        $stmt->bindParam(':r_id', $request_id);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Request " . $status];
        }
        return ["success" => false, "message" => "Failed to update request."];
    }
}
?>