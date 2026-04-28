<?php
// api/controllers/LeadController.php

class LeadController
{
    private $conn;
    private $table_name = "leads";

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create($data)
    {
        // Find a principal architect to assign to if not specified
        $assigned_to = $data['assigned_to'] ?? null;
        if (!$assigned_to) {
            $q_p = "SELECT user_id FROM users WHERE role = 'principal_architect' LIMIT 1";
            $s_p = $this->conn->prepare($q_p);
            $s_p->execute();
            $p = $s_p->fetch(PDO::FETCH_ASSOC);
            $assigned_to = $p ? $p['user_id'] : null;
        }

        $query = "INSERT INTO " . $this->table_name . " 
                (lead_source, client_name, contact_number, email, location, project_type, estimated_budget, status, created_by, assigned_to) 
                VALUES (:lead_source, :client_name, :contact_number, :email, :location, :project_type, :estimated_budget, 'enquiry', :created_by, :assigned_to)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':lead_source', $data['lead_source']);
        $stmt->bindParam(':client_name', $data['client_name']);
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':email', $data['email']);
        $stmt->bindParam(':location', $data['location']);
        $stmt->bindParam(':project_type', $data['project_type']);
        $stmt->bindParam(':estimated_budget', $data['estimated_budget']);
        $stmt->bindParam(':created_by', $data['created_by']);
        $stmt->bindParam(':assigned_to', $assigned_to);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Lead created successfully and routed to Principal."];
        }
        return ["success" => false, "message" => "Unable to create lead."];
    }

    public function getAll()
    {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateStatus($lead_id, $status, $assignmentData = null)
    {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE lead_id = :lead_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':lead_id', $lead_id);

        if ($stmt->execute()) {
            if ($status === 'won' && $assignmentData) {
                return $this->convertToProject($lead_id, $assignmentData);
            }
            return ["success" => true, "message" => "Status updated."];
        }
        return ["success" => false, "message" => "Failed to update status."];
    }

    private function convertToProject($lead_id, $data)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Fetch Lead
            $q = "SELECT * FROM leads WHERE lead_id = :id";
            $s = $this->conn->prepare($q);
            $s->execute(['id' => $lead_id]);
            $lead = $s->fetch(PDO::FETCH_ASSOC);

            // 2. Create Client
            $client_code = "CL" . str_pad($lead_id, 3, '0', STR_PAD_LEFT);
            $c_query = "INSERT INTO clients (client_code, name, phone, address, lead_source, converted_from_lead_id, created_by) 
                        VALUES (:code, :name, :phone, :address, :source, :lead_id, :created_by)";
            $c_stmt = $this->conn->prepare($c_query);
            $c_stmt->execute([
                'code' => $client_code,
                'name' => $lead['client_name'],
                'phone' => $lead['contact_number'],
                'address' => $lead['location'],
                'source' => $lead['lead_source'],
                'lead_id' => $lead_id,
                'created_by' => $data['created_by']
            ]);
            $client_id = $this->conn->lastInsertId();

            // 3. Create Project
            $p_code = "PR" . str_pad($lead_id, 3, '0', STR_PAD_LEFT);
            $p_query = "INSERT INTO projects (project_code, project_name, client_id, project_type, site_address, start_date, expected_end_date, total_budget, advance_received, project_head_id, created_by, status) 
                        VALUES (:code, :name, :c_id, :type, :site, :start, :end, :budget, :advance, :head_id, :created_by, 'active')";
            $p_stmt = $this->conn->prepare($p_query);
            $p_stmt->execute([
                'code' => $p_code,
                'name' => $data['project_name'],
                'c_id' => $client_id,
                'type' => $lead['project_type'] ?? 'interior',
                'site' => $lead['location'],
                'start' => $data['start_date'],
                'end' => $data['expected_end_date'] ?? null,
                'budget' => $data['total_budget'],
                'advance' => $data['advance_paid'],
                'head_id' => $data['project_head_id'],
                'created_by' => $data['created_by']
            ]);
            $project_id = $this->conn->lastInsertId();

            // 3.5 Initialize Timeline
            require_once 'TimelineController.php';
            $timeline = new TimelineController($this->conn);
            $timeline->generateMasterTimeline($project_id, $data['start_date'], $data['expected_end_date'], $data['created_by']);

            // 4. Assignments
            $a_query = "INSERT INTO project_assignments (project_id, user_id, role, assigned_date, status) VALUES (:p_id, :u_id, :role, CURDATE(), 'active')";
            $a_stmt = $this->conn->prepare($a_query);

            // Assign Project Head
            $a_stmt->execute(['p_id' => $project_id, 'u_id' => $data['project_head_id'], 'role' => 'project_head']);

            // Assign Site Engineer
            if (isset($data['site_engineer_id'])) {
                $a_stmt->execute(['p_id' => $project_id, 'u_id' => $data['site_engineer_id'], 'role' => 'site_engineer']);
            }

            // 5. Initial Payment Transaction (if advance paid > 0)
            if ($data['advance_paid'] > 0) {
                $t_query = "INSERT INTO finance_transactions (project_id, type, amount, category, description, transaction_date, recorded_by) 
                            VALUES (:p_id, 'credit', :amount, 'Advance Payment', 'Finalized project advance', CURDATE(), :created_by)";
                $t_stmt = $this->conn->prepare($t_query);
                $t_stmt->execute([
                    'p_id' => $project_id,
                    'amount' => $data['advance_paid'],
                    'created_by' => $data['created_by']
                ]);
            }

            $this->conn->commit();
            return ["success" => true, "message" => "Lead converted to Project successfully.", "project_id" => $project_id];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Conversion failed: " . $e->getMessage()];
        }
    }
}
?>