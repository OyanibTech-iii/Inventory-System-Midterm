<?php

class Order
{
    // Database connection and table name
    private $conn;
    private $table_name = "sales"; // This matches your database schema
    private $items_table = "sale_items"; // This matches your database schema

    // Object properties
    public $order_id; // This will correspond to sale_id in the database
    public $invoice_number;
    public $customer_id;
    public $user_id;
    public $order_date; // This will map to sale_date in the database
    public $total_amount;
    public $payment_method;
    public $notes;

    // Constructor with database connection
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create new order (maps to sales table)
    public function create()
    {
        // Generate invoice number
        $this->invoice_number = 'INV-' . date('Y-m-d') . '-' . sprintf('%04d', rand(1, 9999));

        // Insert query - adjusted for sales table
        $query = "INSERT INTO " . $this->table_name . "
                  (invoice_number, customer_id, user_id, sale_date, total_amount, payment_method, notes)
                  VALUES
                  (:invoice_number, :customer_id, :user_id, :sale_date, :total_amount, :payment_method, :notes)";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $this->invoice_number = htmlspecialchars(strip_tags($this->invoice_number));
        $this->customer_id = isset($this->customer_id) ? htmlspecialchars(strip_tags($this->customer_id)) : null;
        $this->user_id = htmlspecialchars(strip_tags($this->user_id));
        $this->total_amount = htmlspecialchars(strip_tags($this->total_amount));
        $this->payment_method = htmlspecialchars(strip_tags($this->payment_method));
        $this->notes = htmlspecialchars(strip_tags($this->notes));

        // Bind values
        $stmt->bindParam(':invoice_number', $this->invoice_number);
        $stmt->bindParam(':customer_id', $this->customer_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':sale_date', $this->order_date); // Map order_date to sale_date
        $stmt->bindParam(':total_amount', $this->total_amount);
        $stmt->bindParam(':payment_method', $this->payment_method);
        $stmt->bindParam(':notes', $this->notes);

        // Execute query
        if ($stmt->execute()) {
            // Get last inserted ID (sale_id)
            $this->order_id = $this->conn->lastInsertId();
            return true;
        }

        return false;
    }

    // Add order item (to sale_items table)
    public function addOrderItem($sale_id, $product_id, $quantity, $unit_price, $subtotal)
    {
        // Insert query - adjusted for sale_items table
        $query = "INSERT INTO " . $this->items_table . "
              (sale_id, product_id, quantity_sold, unit_price, discount, subtotal)
              VALUES
              (:sale_id, :product_id, :quantity_sold, :unit_price, :discount, :subtotal)";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Clean data
        $sale_id = htmlspecialchars(strip_tags($sale_id));
        $product_id = htmlspecialchars(strip_tags($product_id));
        $quantity = htmlspecialchars(strip_tags($quantity));
        $unit_price = htmlspecialchars(strip_tags($unit_price));
        $subtotal = htmlspecialchars(strip_tags($subtotal));
        $discount = 0.00; // Default discount

        // Bind values - adjusted column names
        $stmt->bindParam(':sale_id', $sale_id);
        $stmt->bindParam(':product_id', $product_id);
        $stmt->bindParam(':quantity_sold', $quantity);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':discount', $discount);
        $stmt->bindParam(':subtotal', $subtotal);

        // Execute query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read one order
    public function readOne()
    {
        // Query to read one order - adjusted for sales table
        $query = "SELECT s.*, u.username, u.email 
                  FROM " . $this->table_name . " s
                  LEFT JOIN users u ON s.user_id = u.user_id
                  WHERE s.sale_id = ?"; // Using sale_id from the sales table

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(1, $this->order_id);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return false if no order found
        if (!$row) {
            return false;
        }

        return $row;
    }

    // Get order items
    public function getOrderItems($sale_id)
    {
        // Query to get order items - adjusted for sale_items table
        $query = "SELECT si.*, p.name as product_name, p.sku 
                  FROM " . $this->items_table . " si
                  LEFT JOIN products p ON si.product_id = p.product_id
                  WHERE si.sale_id = ?
                  ORDER BY si.sale_item_id ASC";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind ID
        $stmt->bindParam(1, $sale_id);

        // Execute query
        $stmt->execute();

        // Return results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Read all orders for a user
    public function readAllByUser($user_id, $from_record_num = 0, $records_per_page = 10)
    {
        // Query to read all orders for a user - adjusted for sales and sale_items tables
        $query = "SELECT s.*, COUNT(si.sale_item_id) as total_items 
                  FROM " . $this->table_name . " s
                  LEFT JOIN " . $this->items_table . " si ON s.sale_id = si.sale_id
                  WHERE s.user_id = ?
                  GROUP BY s.sale_id
                  ORDER BY s.sale_date DESC
                  LIMIT ?, ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(1, $user_id);
        $stmt->bindParam(2, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(3, $records_per_page, PDO::PARAM_INT);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Count all orders for a user
    public function countAllByUser($user_id)
    {
        // Query to count all orders for a user - adjusted for sales table
        $query = "SELECT COUNT(*) as total_count FROM " . $this->table_name . " WHERE user_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind value
        $stmt->bindParam(1, $user_id);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total_count'];
    }

    // Read all orders (for admin)
    public function readAll($from_record_num = 0, $records_per_page = 10)
    {
        // Query to read all orders - adjusted for sales and sale_items tables
        $query = "SELECT s.*, u.username, COUNT(si.sale_item_id) as total_items 
                  FROM " . $this->table_name . " s
                  LEFT JOIN users u ON s.user_id = u.user_id
                  LEFT JOIN " . $this->items_table . " si ON s.sale_id = si.sale_id
                  GROUP BY s.sale_id
                  ORDER BY s.sale_date DESC
                  LIMIT ?, ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind values
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Count all orders
    public function countAll()
    {
        // Query to count all orders - adjusted for sales table
        $query = "SELECT COUNT(*) as total_count FROM " . $this->table_name;

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        // Get result
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total_count'];
    }
}
?>