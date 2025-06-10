<?php
class Product
{
    // Database connection and table name
    private $conn;
    private $table_name = "products";

    // Object properties
    public $product_id;
    public $name;
    public $description;
    public $category;
    public $price;
    public $sku;
    public $minimum_stock_level;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $stock_level; // Add stock_level property 

    // Constructor with DB connection
    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Read all products with pagination
    function readAll($from_record_num, $records_per_page)
    {
        // Select query
        $query = "SELECT 
                    p.product_id, p.name, p.category, p.price, p.sku, 
                    p.minimum_stock_level, p.is_active,
                    COALESCE(s.current_quantity, 0) as stock_level
                  FROM " . $this->table_name . " p
                  LEFT JOIN (
                      SELECT product_id, SUM(current_quantity) as current_quantity
                      FROM stock
                      GROUP BY product_id
                  ) s ON p.product_id = s.product_id
                  ORDER BY p.name ASC
                  LIMIT ?, ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind variables
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);

        // Execute query
        $stmt->execute();

        return $stmt;
    }

    // Get total count of products
    public function countAll()
    {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row['total_rows'];
    }

    // Get all categories
    public function getCategories()
    {
        $query = "SELECT DISTINCT category FROM " . $this->table_name . " ORDER BY category";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create new product
    public function create()
    {
        // Query to insert product
        $query = "INSERT INTO products
                  SET
                    name=:name,
                    sku=:sku,
                    category=:category,
                    price=:price,
                    description=:description,
                    minimum_stock_level=:minimum_stock_level,
                    is_active=:is_active,
                    created_at=:created_at";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->sku = htmlspecialchars(strip_tags($this->sku));
        $this->category = htmlspecialchars(strip_tags($this->category));
        $this->description = htmlspecialchars(strip_tags($this->description));

        // Set timestamp
        $created_at = date('Y-m-d H:i:s');

        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":sku", $this->sku);
        $stmt->bindParam(":category", $this->category);
        $stmt->bindParam(":price", $this->price);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":minimum_stock_level", $this->minimum_stock_level);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":created_at", $created_at);

        // Execute query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Read one product
    public function readOne()
    {
        $query = "SELECT
                    p.*, COALESCE(s.current_quantity, 0) as stock_level
                  FROM
                    " . $this->table_name . " p
                  LEFT JOIN (
                      SELECT product_id, SUM(current_quantity) as current_quantity
                      FROM stock
                      GROUP BY product_id
                  ) s ON p.product_id = s.product_id
                  WHERE
                    p.product_id = ?
                  LIMIT 0,1";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind product_id
        $stmt->bindParam(1, $this->product_id);

        // Execute query
        $stmt->execute();

        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            // Set properties
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->category = $row['category'];
            $this->price = $row['price'];
            $this->sku = $row['sku'];
            $this->minimum_stock_level = $row['minimum_stock_level'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            $this->stock_level = $row['stock_level']; // Set the stock level property

            return true;
        }

        return false;
    }

    // Get current stock level for a product
    public function getStockLevel($product_id)
    {
        $query = "SELECT COALESCE(SUM(current_quantity), 0) as stock_level
                  FROM stock
                  WHERE product_id = ?";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Bind product_id
        $stmt->bindParam(1, $product_id);

        // Execute query
        $stmt->execute();

        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return (int) $row['stock_level'];
    }

    // Add updateStock method
    public function updateStock()
    {
        // First, retrieve the current stock record
        $query = "SELECT id FROM stock WHERE product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->product_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            // Update existing stock record
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stock_id = $row['id'];

            $query = "UPDATE stock 
                      SET current_quantity = ?, 
                          updated_at = ? 
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $updated_at = date('Y-m-d H:i:s');

            $stmt->bindParam(1, $this->stock_level);
            $stmt->bindParam(2, $updated_at);
            $stmt->bindParam(3, $stock_id);
        } else {
            // Create new stock record
            $query = "INSERT INTO stock 
                      SET product_id = ?, 
                          current_quantity = ?, 
                          created_at = ?";
            $stmt = $this->conn->prepare($query);
            $created_at = date('Y-m-d H:i:s');

            $stmt->bindParam(1, $this->product_id);
            $stmt->bindParam(2, $this->stock_level);
            $stmt->bindParam(3, $created_at);
        }

        return $stmt->execute();
    }
    /**
     * Update the stock level for a product
     * 
     * @param int $product_id ID of the product to update
     * @param int $new_stock_level New stock level for the product
     * @return boolean True if update successful, false otherwise
     */
    public function updateStockLevel($product_id, $new_stock_level)
    {
        // Basic validation
        if ($new_stock_level < 0) {
            return false;
        }
    
        // First, retrieve the current stock record
        $query = "SELECT stock_id FROM stock WHERE product_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $product_id);
        $stmt->execute();
    
        if ($stmt->rowCount() > 0) {
            // Update existing stock record
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $stock_id = $row['stock_id'];
    
            $query = "UPDATE stock 
                  SET current_quantity = ?
                  WHERE stock_id = ?";
            $stmt = $this->conn->prepare($query);
    
            $stmt->bindParam(1, $new_stock_level);
            $stmt->bindParam(2, $stock_id);
        } else {
            // Create new stock record
            $query = "INSERT INTO stock 
                  SET product_id = ?, 
                      supplier_id = 1, /* You might want to make this a parameter */
                      quantity_added = ?, 
                      current_quantity = ?, 
                      unit_cost = 0.00, /* You might want to make this a parameter */
                      date_added = NOW()";
            $stmt = $this->conn->prepare($query);
    
            $stmt->bindParam(1, $product_id);
            $stmt->bindParam(2, $new_stock_level);
            $stmt->bindParam(3, $new_stock_level);
        }
    
        return $stmt->execute();
    }
}
