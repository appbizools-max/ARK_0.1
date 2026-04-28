<?php
// api/controllers/QuoteController.php

class QuoteController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function createQuote($data)
    {
        try {
            $this->conn->beginTransaction();

            // 1. Create Quote Entry
            $quote_number = 'ARK-QT-' . time();
            $query = "INSERT INTO quotes (project_id, quote_number, total_amount, created_by, status) 
                      VALUES (:p_id, :q_num, :total, :u_id, 'draft')";
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                'p_id' => $data['project_id'],
                'q_num' => $quote_number,
                'total' => $data['total_amount'] ?? 0,
                'u_id' => $data['created_by']
            ]);
            $quote_id = $this->conn->lastInsertId();

            // 2. Insert Sections and Items
            if (isset($data['sections']) && is_array($data['sections'])) {
                foreach ($data['sections'] as $s_idx => $section) {
                    $s_query = "INSERT INTO quote_sections (quote_id, section_name, display_order) 
                                VALUES (:q_id, :name, :order)";
                    $s_stmt = $this->conn->prepare($s_query);
                    $s_stmt->execute([
                        'q_id' => $quote_id,
                        'name' => $section['name'],
                        'order' => $s_idx
                    ]);
                    $section_id = $this->conn->lastInsertId();

                    if (isset($section['items']) && is_array($section['items'])) {
                        foreach ($section['items'] as $i_idx => $item) {
                            $i_query = "INSERT INTO quote_items 
                                        (section_id, particulars, brand, unit, quantity, rate, amount, display_order) 
                                        VALUES (:s_id, :part, :brand, :unit, :qty, :rate, :amt, :order)";
                            $i_stmt = $this->conn->prepare($i_query);
                            $i_stmt->execute([
                                's_id' => $section_id,
                                'part' => $item['particulars'],
                                'brand' => $item['brand'] ?? '',
                                'unit' => $item['unit'] ?? '',
                                'qty' => $item['quantity'] ?? 0,
                                'rate' => $item['rate'] ?? 0,
                                'amt' => ($item['quantity'] ?? 0) * ($item['rate'] ?? 0),
                                'order' => $i_idx
                            ]);
                        }
                    }
                }
            }

            $this->conn->commit();
            return ["success" => true, "message" => "Quote created successfully", "quote_id" => $quote_id];
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ["success" => false, "message" => "Error creating quote: " . $e->getMessage()];
        }
    }

    public function getQuotesByProject($project_id)
    {
        $query = "SELECT * FROM quotes WHERE project_id = :p_id ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(['p_id' => $project_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getQuoteDetails($quote_id)
    {
        // 1. Get Quote
        $q_query = "SELECT * FROM quotes WHERE quote_id = :id";
        $q_stmt = $this->conn->prepare($q_query);
        $q_stmt->execute(['id' => $quote_id]);
        $quote = $q_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$quote)
            return null;

        // 2. Get Sections
        $s_query = "SELECT * FROM quote_sections WHERE quote_id = :id ORDER BY display_order";
        $s_stmt = $this->conn->prepare($s_query);
        $s_stmt->execute(['id' => $quote_id]);
        $sections = $s_stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($sections as &$section) {
            // 3. Get Items for each section
            $i_query = "SELECT * FROM quote_items WHERE section_id = :sid ORDER BY display_order";
            $i_stmt = $this->conn->prepare($i_query);
            $i_stmt->execute(['sid' => $section['section_id']]);
            $section['items'] = $i_stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $quote['sections'] = $sections;
        return $quote;
    }
}
?>