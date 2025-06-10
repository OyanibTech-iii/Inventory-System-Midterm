<?php
class Inventory {
    private $conn;
    
    // Constructor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get inventory items with filters
    public function getInventory($search = '', $category = '', $stock_status = '', $start = 0, $limit = 10) {
        $query = "SELECT p.product_id as id, p.name, p.sku, 
                  COALESCE((SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1), 0) as current_stock,
                  p.minimum_stock_level as reorder_level, p.price as unit_price, 
                  p.category as category_name
                  FROM products p
                  WHERE p.is_active = 1";
        
        // Apply filters
        if (!empty($search)) {
            $query .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
        }
        
        if (!empty($category)) {
            $query .= " AND p.category = :category";
        }
        
        if (!empty($stock_status)) {
            switch ($stock_status) {
                case 'low':
                    $query .= " AND (SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) <= p.minimum_stock_level 
                               AND (SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) > 0";
                    break;
                case 'out':
                    $query .= " AND (SELECT COALESCE(current_quantity, 0) FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) = 0";
                    break;
                case 'normal':
                    $query .= " AND (SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) > p.minimum_stock_level";
                    break;
            }
        }
        
        $query .= " ORDER BY p.name ASC LIMIT :start, :limit";
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }
        
        if (!empty($category)) {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->bindParam(':start', $start, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get total count for pagination
    public function getTotalCount($search = '', $category = '', $stock_status = '') {
        $query = "SELECT COUNT(*) as total FROM products p
                  WHERE p.is_active = 1";
        
        // Apply filters
        if (!empty($search)) {
            $query .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
        }
        
        if (!empty($category)) {
            $query .= " AND p.category = :category";
        }
        
        if (!empty($stock_status)) {
            switch ($stock_status) {
                case 'low':
                    $query .= " AND (SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) <= p.minimum_stock_level 
                               AND (SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) > 0";
                    break;
                case 'out':
                    $query .= " AND (SELECT COALESCE(current_quantity, 0) FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) = 0";
                    break;
                case 'normal':
                    $query .= " AND (SELECT current_quantity FROM stock WHERE product_id = p.product_id ORDER BY date_added DESC LIMIT 1) > p.minimum_stock_level";
                    break;
            }
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Bind parameters
        if (!empty($search)) {
            $search_param = "%{$search}%";
            $stmt->bindParam(':search', $search_param);
        }
        
        if (!empty($category)) {
            $stmt->bindParam(':category', $category);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Get all categories
    public function getAllCategories() {
        $query = "SELECT DISTINCT category as id, category as name FROM products ORDER BY category ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Update stock quantity
    public function updateStock($product_id, $quantity) {
        // First check if there's an existing stock record
        $check_query = "SELECT stock_id FROM stock WHERE product_id = :product_id ORDER BY date_added DESC LIMIT 1";
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bindParam(':product_id', $product_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing stock
            $row = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $stock_id = $row['stock_id'];
            
            $query = "UPDATE stock SET current_quantity = :quantity WHERE stock_id = :stock_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':stock_id', $stock_id);
            
            return $stmt->execute();
        } else {
            // Create new stock entry
            $query = "INSERT INTO stock (product_id, supplier_id, quantity_added, current_quantity, unit_cost, date_added) 
                      VALUES (:product_id, 1, :quantity, :quantity, 
                             (SELECT price FROM products WHERE product_id = :product_id), NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':product_id', $product_id);
            $stmt->bindParam(':quantity', $quantity);
            
            return $stmt->execute();
        }
    }
    
    // Update reorder level
    public function updateReorderLevel($product_id, $level) {
        $query = "UPDATE products SET minimum_stock_level = :level WHERE product_id = :product_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':level', $level);
        $stmt->bindParam(':product_id', $product_id);
        
        return $stmt->execute();
    }
    
    // Get total number of products
    public function getTotalProducts() {
        $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Get total stock value
    public function getTotalStockValue() {
        $query = "SELECT SUM(s.current_quantity * p.price) as total_value 
                 FROM products p
                 JOIN stock s ON p.product_id = s.product_id
                 WHERE p.is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_value'] ?? 0;
    }
    
    // Get low stock count
    public function getLowStockCount() {
        $query = "SELECT COUNT(*) as count FROM products p
                 JOIN stock s ON p.product_id = s.product_id
                 WHERE p.is_active = 1
                 AND s.current_quantity <= p.minimum_stock_level 
                 AND s.current_quantity > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'];
    }
    
    // Get out of stock count
    public function getOutOfStockCount() {
        $query = "SELECT COUNT(*) as count FROM products p
                 LEFT JOIN stock s ON p.product_id = s.product_id
                 WHERE p.is_active = 1
                 AND (s.current_quantity = 0 OR s.current_quantity IS NULL)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['count'];
    }
}
?>