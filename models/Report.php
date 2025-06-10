<?php
class Report {
    // Database connection
    private $conn;
    
    // Report date range
    public $date_from;
    public $date_to;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Get sales summary information
     * @return array Sales summary data
     */
    public function getSalesSummary() {
        $query = "SELECT 
                    SUM(total_amount) as total_sales,
                    COUNT(sale_id) as total_orders,
                    (SELECT SUM(quantity_sold) FROM sale_items 
                     JOIN sales s ON sale_items.sale_id = s.sale_id 
                     WHERE s.sale_date BETWEEN :date_from AND :date_to) as total_items,
                    CASE 
                        WHEN COUNT(sale_id) > 0 THEN SUM(total_amount) / COUNT(sale_id)
                        ELSE 0 
                    END as avg_order_value
                  FROM sales 
                  WHERE sale_date BETWEEN :date_from AND :date_to";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_from', $this->date_from);
        $stmt->bindParam(':date_to', $this->date_to);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Default values in case of no results
        $summary = [
            'total_sales' => $row['total_sales'] ?? 0,
            'total_orders' => $row['total_orders'] ?? 0,
            'total_items' => $row['total_items'] ?? 0,
            'avg_order_value' => $row['avg_order_value'] ?? 0
        ];
        
        return $summary;
    }
    
    /**
     * Get top selling products for the period
     * @param int $limit Number of products to return
     * @return array List of top selling products
     */
    public function getTopSellingProducts($limit = 5) {
        $query = "SELECT 
                    p.product_id,
                    p.name,
                    SUM(si.quantity_sold) as quantity
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.product_id
                  JOIN sales s ON si.sale_id = s.sale_id
                  WHERE s.sale_date BETWEEN :date_from AND :date_to
                  GROUP BY p.product_id
                  ORDER BY quantity DESC
                  LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_from', $this->date_from);
        $stmt->bindParam(':date_to', $this->date_to);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get sales data by product category
     * @return array Category sales data
     */
    public function getSalesByCategory() {
        $query = "SELECT 
                    p.category as name,
                    SUM(si.quantity_sold * si.unit_price) as amount
                  FROM sale_items si
                  JOIN products p ON si.product_id = p.product_id
                  JOIN sales s ON si.sale_id = s.sale_id
                  WHERE s.sale_date BETWEEN :date_from AND :date_to
                  GROUP BY p.category
                  ORDER BY amount DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_from', $this->date_from);
        $stmt->bindParam(':date_to', $this->date_to);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get sales trend data for charting
     * @return array Daily sales data
     */
    public function getSalesTrend() {
        $query = "SELECT 
                    DATE(s.sale_date) as date,
                    SUM(total_amount) as amount
                  FROM sales s
                  WHERE s.sale_date BETWEEN :date_from AND :date_to
                  GROUP BY DATE(s.sale_date)
                  ORDER BY date ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':date_from', $this->date_from);
        $stmt->bindParam(':date_to', $this->date_to);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get products with low inventory
     * @return array Low stock items
     */
    public function getLowStockItems() {
        $query = "SELECT 
                    p.product_id,
                    p.name,
                    (SELECT SUM(current_quantity) FROM stock WHERE product_id = p.product_id) as current_stock,
                    p.minimum_stock_level as reorder_level
                  FROM products p
                  WHERE (SELECT SUM(current_quantity) FROM stock WHERE product_id = p.product_id) <= p.minimum_stock_level
                  ORDER BY ((SELECT SUM(current_quantity) FROM stock WHERE product_id = p.product_id) / p.minimum_stock_level) ASC, p.name ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>