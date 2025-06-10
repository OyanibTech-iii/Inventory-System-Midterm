<?php
class Sale {
    private $conn;
    private $table_name = "sales";
    
    // Properties
    public $sale_id;
    public $invoice_number;
    public $customer_id;
    public $user_id;
    public $sale_date;
    public $total_amount;
    public $payment_method;
    public $notes;
    public $items = []; // Array of sale items
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Create new sale with multiple items
    public function create() {
        // Begin transaction
        $this->conn->beginTransaction();
        
        try {
            // Generate unique invoice number
            $this->invoice_number = $this->generateInvoiceNumber();
            
            // Insert sale header
            $query = "INSERT INTO " . $this->table_name . "
                    SET
                        invoice_number=:invoice_number,
                        customer_id=:customer_id,
                        user_id=:user_id,
                        sale_date=:sale_date,
                        total_amount=:total_amount,
                        payment_method=:payment_method,
                        notes=:notes";
            
            $stmt = $this->conn->prepare($query);
            
            // Bind values
            // ...code for binding parameters...
            
            $stmt->execute();
            
            // Get newly inserted sale ID
            $this->sale_id = $this->conn->lastInsertId();
            
            // Insert sale items and update stock
            foreach($this->items as $item) {
                // Insert into sale_items table
                $item_query = "INSERT INTO sale_items
                           SET
                              sale_id=:sale_id,
                              product_id=:product_id,
                              quantity_sold=:quantity_sold,
                              unit_price=:unit_price,
                              discount=:discount,
                              subtotal=:subtotal";
                
                $item_stmt = $this->conn->prepare($item_query);
                
                // Bind values
                $item_stmt->bindParam(":sale_id", $this->sale_id);
                $item_stmt->bindParam(":product_id", $item['product_id']);
                // ... bind other parameters
                
                $item_stmt->execute();
                
                // Update stock quantities
                $this->updateStockAfterSale($item['product_id'], $item['quantity_sold']);
            }
            
            // Commit the transaction
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return false;
        }
    }
    
    // Method to update stock after sale
    private function updateStockAfterSale($product_id, $quantity) {
        // Implementation to deduct quantities from stock
        // typically using FIFO or LIFO method
    }
    
    // Generate invoice number
    private function generateInvoiceNumber() {
        return "INV-" . date('Ymd') . "-" . uniqid();
    }
    
    // Add these properties to the existing properties section
public $date_from;
public $date_to;

// Add these methods to the Sale class

// Read all sales with pagination and filtering
public function readAll($from_record_num, $records_per_page) {
    // Base query
    $query = "SELECT s.*, c.name as customer_name, 
             (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.sale_id) as item_count
             FROM " . $this->table_name . " s
             LEFT JOIN customers c ON s.customer_id = c.customer_id
             WHERE 1=1";
    
    // Add date range filter if set
    if(!empty($this->date_from) && !empty($this->date_to)) {
        $query .= " AND s.sale_date BETWEEN :date_from AND :date_to";
    }
    
    // Add user filter if set
    if(!empty($this->user_id)) {
        $query .= " AND s.user_id = :user_id";
    }
    
    // Add pagination
    $query .= " ORDER BY s.sale_date DESC
               LIMIT :from_record_num, :records_per_page";
    
    $stmt = $this->conn->prepare($query);
    
    // Bind parameters
    if(!empty($this->date_from) && !empty($this->date_to)) {
        $stmt->bindParam(":date_from", $this->date_from);
        $stmt->bindParam(":date_to", $this->date_to);
    }
    
    if(!empty($this->user_id)) {
        $stmt->bindParam(":user_id", $this->user_id);
    }
    
    $stmt->bindParam(":from_record_num", $from_record_num, PDO::PARAM_INT);
    $stmt->bindParam(":records_per_page", $records_per_page, PDO::PARAM_INT);
    
    $stmt->execute();
    
    return $stmt;
}

// Count all sales (for pagination)
public function countAll() {
    $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name . " s WHERE 1=1";
    
    // Add date range filter if set
    if(!empty($this->date_from) && !empty($this->date_to)) {
        $query .= " AND s.sale_date BETWEEN :date_from AND :date_to";
    }
    
    // Add user filter if set
    if(!empty($this->user_id)) {
        $query .= " AND s.user_id = :user_id";
    }
    
    $stmt = $this->conn->prepare($query);
    
    // Bind parameters
    if(!empty($this->date_from) && !empty($this->date_to)) {
        $stmt->bindParam(":date_from", $this->date_from);
        $stmt->bindParam(":date_to", $this->date_to);
    }
    
    if(!empty($this->user_id)) {
        $stmt->bindParam(":user_id", $this->user_id);
    }
    
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row['total_rows'];
}
}