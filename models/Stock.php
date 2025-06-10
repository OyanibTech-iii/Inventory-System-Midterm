<?php
class Stock {
    // Database connection and table name
    private $conn;
    private $table_name = "stock";
    
    // Object properties
    public $stock_id;
    public $product_id;
    public $supplier_id;
    public $quantity_added;
    public $current_quantity;
    public $unit_cost;
    public $batch_number;
    public $date_added;
    public $expiry_date;
    public $location_code;
    public $added_by; // Added property for username
    public $is_admin; // Added property for admin status
    
    // Constructor with DB connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read all stock records with pagination
    public function readAll($from_record_num, $records_per_page) {
        // Base query
        $query = "SELECT s.stock_id, s.product_id, s.supplier_id, s.quantity_added, s.current_quantity, 
                  s.unit_cost, s.batch_number, s.date_added, s.expiry_date, s.location_code, s.added_by, s.is_admin,
                  p.name as product_name, p.sku, p.minimum_stock_level
                  FROM " . $this->table_name . " s
                  LEFT JOIN products p ON p.product_id = s.product_id";
        
        // Apply search if provided
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_term = $_GET['search'];
            $query .= " WHERE p.name LIKE :search OR p.sku LIKE :search OR s.batch_number LIKE :search";
            $query .= " ORDER BY s.date_added DESC LIMIT :from_record_num, :records_per_page";
            
            $stmt = $this->conn->prepare($query);
            $search_param = "%{$search_term}%";
            $stmt->bindParam(":search", $search_param);
            $stmt->bindParam(":from_record_num", $from_record_num, PDO::PARAM_INT);
            $stmt->bindParam(":records_per_page", $records_per_page, PDO::PARAM_INT);
        }
        // Apply location filter if provided
        elseif (isset($_GET['location']) && !empty($_GET['location'])) {
            $location = $_GET['location'];
            $query .= " WHERE s.location_code = :location";
            $query .= " ORDER BY s.date_added DESC LIMIT :from_record_num, :records_per_page";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":location", $location);
            $stmt->bindParam(":from_record_num", $from_record_num, PDO::PARAM_INT);
            $stmt->bindParam(":records_per_page", $records_per_page, PDO::PARAM_INT);
        }
        // No filters
        else {
            $query .= " ORDER BY s.date_added DESC LIMIT :from_record_num, :records_per_page";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":from_record_num", $from_record_num, PDO::PARAM_INT);
            $stmt->bindParam(":records_per_page", $records_per_page, PDO::PARAM_INT);
        }
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Count all stock records for pagination
    public function countAll() {
        // Base query
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        
        // Apply search if provided
        if (isset($_GET['search']) && !empty($_GET['search'])) {
            $search_term = $_GET['search'];
            $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name . " s 
                      LEFT JOIN products p ON p.product_id = s.product_id 
                      WHERE p.name LIKE :search OR p.sku LIKE :search OR s.batch_number LIKE :search";
            
            $stmt = $this->conn->prepare($query);
            $search_param = "%{$search_term}%";
            $stmt->bindParam(":search", $search_param);
        }
        // Apply location filter if provided
        elseif (isset($_GET['location']) && !empty($_GET['location'])) {
            $location = $_GET['location'];
            $query .= " WHERE location_code = :location";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":location", $location);
        }
        // No filters
        else {
            $stmt = $this->conn->prepare($query);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }
    
    // Get all unique storage locations
    public function getLocations() {
        $query = "SELECT DISTINCT location_code FROM " . $this->table_name . " ORDER BY location_code";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Create new stock record
    public function addStock() {
        // Query
        $query = "INSERT INTO " . $this->table_name . "
                 (product_id, supplier_id, quantity_added, current_quantity, unit_cost, 
                  batch_number, date_added, expiry_date, location_code, added_by, is_admin)
                  VALUES
                  (:product_id, :supplier_id, :quantity_added, :current_quantity, :unit_cost,
                   :batch_number, :date_added, :expiry_date, :location_code, :added_by, :is_admin)";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->product_id = htmlspecialchars(strip_tags($this->product_id));
        $this->supplier_id = htmlspecialchars(strip_tags($this->supplier_id));
        $this->quantity_added = htmlspecialchars(strip_tags($this->quantity_added));
        $this->current_quantity = htmlspecialchars(strip_tags($this->current_quantity));
        $this->unit_cost = htmlspecialchars(strip_tags($this->unit_cost));
        $this->batch_number = htmlspecialchars(strip_tags($this->batch_number));
        $this->date_added = htmlspecialchars(strip_tags($this->date_added));
        $this->location_code = htmlspecialchars(strip_tags($this->location_code));
        $this->added_by = htmlspecialchars(strip_tags($this->added_by));
        
        // Bind values
        $stmt->bindParam(":product_id", $this->product_id);
        $stmt->bindParam(":supplier_id", $this->supplier_id);
        $stmt->bindParam(":quantity_added", $this->quantity_added);
        $stmt->bindParam(":current_quantity", $this->current_quantity);
        $stmt->bindParam(":unit_cost", $this->unit_cost);
        $stmt->bindParam(":batch_number", $this->batch_number);
        $stmt->bindParam(":date_added", $this->date_added);
        $stmt->bindParam(":location_code", $this->location_code);
        $stmt->bindParam(":added_by", $this->added_by);
        $stmt->bindParam(":is_admin", $this->is_admin, PDO::PARAM_INT);
        
        // Handle expiry date (can be null)
        if ($this->expiry_date !== null) {
            $stmt->bindParam(":expiry_date", $this->expiry_date);
        } else {
            $stmt->bindValue(":expiry_date", null, PDO::PARAM_NULL);
        }
        
        // Execute query
        if ($stmt->execute()) {
            // Update product stock level in the products table if needed
            $this->updateProductStock($this->product_id);
            return true;
        }
        
        return false;
    }
    
    // Read one stock record
    public function readOne() {
        $query = "SELECT
                    s.*, p.name as product_name, p.sku, p.minimum_stock_level,
                    sup.name as supplier_name
                  FROM
                    " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  LEFT JOIN suppliers sup ON s.supplier_id = sup.supplier_id
                  WHERE
                    s.stock_id = ?
                  LIMIT 0,1";
                  
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind stock_id
        $stmt->bindParam(1, $this->stock_id);
        
        // Execute query
        $stmt->execute();
        
        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            // Set properties
            $this->product_id = $row['product_id'];
            $this->supplier_id = $row['supplier_id'];
            $this->quantity_added = $row['quantity_added'];
            $this->current_quantity = $row['current_quantity'];
            $this->unit_cost = $row['unit_cost'];
            $this->batch_number = $row['batch_number'];
            $this->date_added = $row['date_added'];
            $this->expiry_date = $row['expiry_date'];
            $this->location_code = $row['location_code'];
            $this->added_by = $row['added_by'] ?? null;
            $this->is_admin = $row['is_admin'] ?? 0;
            
            return true;
        }
        
        return false;
    }
    
    // Update stock quantity
    public function update() {
        $query = "UPDATE " . $this->table_name . "
                  SET
                    current_quantity = :current_quantity,
                    location_code = :location_code,
                    expiry_date = :expiry_date
                  WHERE
                    stock_id = :stock_id";
                    
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->location_code = htmlspecialchars(strip_tags($this->location_code));
        
        // Bind values
        $stmt->bindParam(":current_quantity", $this->current_quantity);
        $stmt->bindParam(":location_code", $this->location_code);
        $stmt->bindParam(":stock_id", $this->stock_id);
        
        // Handle expiry date (can be null)
        if ($this->expiry_date !== null) {
            $stmt->bindParam(":expiry_date", $this->expiry_date);
        } else {
            $stmt->bindValue(":expiry_date", null, PDO::PARAM_NULL);
        }
        
        // Execute query
        if ($stmt->execute()) {
            // Update product stock level in the products table if needed
            $this->updateProductStock($this->product_id);
            return true;
        }
        
        return false;
    }
    
    // Update product stock total when adding/updating stock
 // Update product stock total when adding/updating stock
private function updateProductStock($product_id) {
    // Check if the total_stock column exists in the products table
    $check_query = "SHOW COLUMNS FROM products LIKE 'total_stock'";
    $check_stmt = $this->conn->prepare($check_query);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Column exists, proceed with update
        // Calculate the current total stock for this product
        $query = "SELECT SUM(current_quantity) as total FROM " . $this->table_name . " WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_stock = $row['total'] ?? 0;
        
        // Update the product's total_stock field
        $update_query = "UPDATE products SET total_stock = ? WHERE product_id = ?";
        $update_stmt = $this->conn->prepare($update_query);
        $update_stmt->bindParam(1, $total_stock);
        $update_stmt->bindParam(2, $product_id);
        
        return $update_stmt->execute();
    }
    
    // If column doesn't exist, just return true as there's nothing to update
    return true;
}
    
    // Search stocks
    public function search($keywords, $from_record_num, $records_per_page) {
        // Search query
        $query = "SELECT 
                    s.stock_id, s.product_id, s.supplier_id, s.quantity_added, 
                    s.current_quantity, s.unit_cost, s.batch_number, 
                    s.date_added, s.expiry_date, s.location_code, s.added_by, s.is_admin,
                    p.name as product_name, p.sku, p.minimum_stock_level
                  FROM " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  WHERE 
                    p.name LIKE ? OR p.sku LIKE ? OR s.batch_number LIKE ?
                  ORDER BY s.date_added DESC
                  LIMIT ?, ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        // Bind variables
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->bindParam(4, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(5, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Filter by location
    public function filterByLocation($location, $from_record_num, $records_per_page) {
        // Filter query
        $query = "SELECT 
                    s.stock_id, s.product_id, s.supplier_id, s.quantity_added, 
                    s.current_quantity, s.unit_cost, s.batch_number, 
                    s.date_added, s.expiry_date, s.location_code, s.added_by, s.is_admin,
                    p.name as product_name, p.sku, p.minimum_stock_level
                  FROM " . $this->table_name . " s
                  LEFT JOIN products p ON s.product_id = p.product_id
                  WHERE 
                    s.location_code = ?
                  ORDER BY s.date_added DESC
                  LIMIT ?, ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize location
        $location = htmlspecialchars(strip_tags($location));
        
        // Bind variables
        $stmt->bindParam(1, $location);
        $stmt->bindParam(2, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(3, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
}