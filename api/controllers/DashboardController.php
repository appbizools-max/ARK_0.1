<?php
// api/controllers/DashboardController.php

class DashboardController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getSummary()
    {
        $stats = [];

        // Active Projects
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM projects WHERE status = 'active'");
        $stmt->execute();
        $stats['active_projects'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Pending Leads
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM leads WHERE status != 'converted'");
        $stmt->execute();
        $stats['pending_leads'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Total Clients
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM clients");
        $stmt->execute();
        $stats['total_clients'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        // Financial Overview
        $stmt = $this->conn->prepare("SELECT 
                                        SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) as income,
                                        SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END) as expense
                                      FROM finance_transactions");
        $stmt->execute();
        $finance = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_income'] = $finance['income'] ?? 0;
        $stats['total_expense'] = $finance['expense'] ?? 0;

        // Recently Completed/Updated Projects (for a chart)
        $stmt = $this->conn->prepare("SELECT project_name, total_budget FROM projects ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $stats['recent_projects_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Expense by Category (for a chart)
        $stmt = $this->conn->prepare("SELECT category as name, SUM(amount) as value FROM finance_transactions WHERE type = 'debit' GROUP BY category");
        $stmt->execute();
        $stats['expense_category_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return ["success" => true, "data" => $stats];
    }
}
?>