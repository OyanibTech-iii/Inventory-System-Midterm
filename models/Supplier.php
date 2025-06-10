<?php
class Supplier {
    // Database connection and table name
    private $conn;
    private $table_name = "suppliers";
    
    // Object properties
    public $supplier_id;
    public $name;
    public $contact_person;
    public $email;
    public $phone;
    public $address;
    public $city;
    public $state;
    public $postal_code;
    public $country;
    public $is_active;
    public $created_at;
    public $updated_at;
    
    // Constructor with database connection
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Read all suppliers with pagination
    public function readAll($from_record_num, $records_per_page) {
        // Query to read all suppliers
        $query = "SELECT
                    supplier_id, name, contact_person, email, phone, is_active
                FROM
                    " . $this->table_name . "
                ORDER BY
                    name ASC
                LIMIT ?, ?";
        
        // Prepare the statement
        $stmt = $this->conn->prepare($query);
        
        // Bind variables
        $stmt->bindParam(1, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(2, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Count all suppliers
    public function countAll() {
        $query = "SELECT COUNT(*) as total_rows FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }
    
    // Create supplier
    public function create() {
        // Query to insert a new supplier
        $query = "INSERT INTO
                    " . $this->table_name . "
                SET
                    name=:name, 
                    contact_person=:contact_person, 
                    email=:email, 
                    phone=:phone, 
                    address=:address, 
                    city=:city, 
                    state=:state, 
                    postal_code=:postal_code, 
                    country=:country, 
                    is_active=:is_active, 
                    created_at=:created_at";
        
        // Prepare the query
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_person = htmlspecialchars(strip_tags($this->contact_person));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        $this->country = htmlspecialchars(strip_tags($this->country));
        
        // Set timestamp
        $this->created_at = date('Y-m-d H:i:s');
        
        // Bind values
        $stmt->bindParam(":name", $this->name);
        $stmt->bindParam(":contact_person", $this->contact_person);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":city", $this->city);
        $stmt->bindParam(":state", $this->state);
        $stmt->bindParam(":postal_code", $this->postal_code);
        $stmt->bindParam(":country", $this->country);
        $stmt->bindParam(":is_active", $this->is_active);
        $stmt->bindParam(":created_at", $this->created_at);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Read one supplier by ID
    public function readOne() {
        // Query to read one supplier
        $query = "SELECT
                    *
                FROM
                    " . $this->table_name . "
                WHERE
                    supplier_id = ?
                LIMIT 0,1";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Bind ID
        $stmt->bindParam(1, $this->supplier_id);
        
        // Execute query
        $stmt->execute();
        
        // Get record
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set properties
        if($row) {
            $this->supplier_id = $row['supplier_id'];
            $this->name = $row['name'];
            $this->contact_person = $row['contact_person'];
            $this->email = $row['email'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->city = $row['city'];
            $this->state = $row['state'];
            $this->postal_code = $row['postal_code'];
            $this->country = $row['country'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'];
            return true;
        }
        
        return false;
    }
    
    // Update supplier
    public function update() {
        // Query to update a supplier
        $query = "UPDATE
                    " . $this->table_name . "
                SET
                    name=:name, 
                    contact_person=:contact_person, 
                    email=:email, 
                    phone=:phone, 
                    address=:address, 
                    city=:city, 
                    state=:state, 
                    postal_code=:postal_code, 
                    country=:country, 
                    is_active=:is_active, 
                    updated_at=:updated_at
                WHERE
                    supplier_id=:supplier_id";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize inputs
        $this->supplier_id = htmlspecialchars(strip_tags($this->supplier_id));
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->contact_person = htmlspecialchars(strip_tags($this->contact_person));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));
        $this->city = htmlspecialchars(strip_tags($this->city));
        $this->state = htmlspecialchars(strip_tags($this->state));
        $this->postal_code = htmlspecialchars(strip_tags($this->postal_code));
        $this->country = htmlspecialchars(strip_tags($this->country));
        
        // Set timestamp
        $this->updated_at = date('Y-m-d H:i:s');
        
        // Bind values
        $stmt->bindParam(':supplier_id', $this->supplier_id);
        $stmt->bindParam(':name', $this->name);
        $stmt->bindParam(':contact_person', $this->contact_person);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':phone', $this->phone);
        $stmt->bindParam(':address', $this->address);
        $stmt->bindParam(':city', $this->city);
        $stmt->bindParam(':state', $this->state);
        $stmt->bindParam(':postal_code', $this->postal_code);
        $stmt->bindParam(':country', $this->country);
        $stmt->bindParam(':is_active', $this->is_active);
        $stmt->bindParam(':updated_at', $this->updated_at);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Delete supplier
    public function delete() {
        // Query to delete a supplier
        $query = "DELETE FROM " . $this->table_name . " WHERE supplier_id = ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize input
        $this->supplier_id = htmlspecialchars(strip_tags($this->supplier_id));
        
        // Bind ID
        $stmt->bindParam(1, $this->supplier_id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Search suppliers
    public function search($keywords, $from_record_num, $records_per_page) {
        // Query to search suppliers
        $query = "SELECT
                    supplier_id, name, contact_person, email, phone, is_active
                FROM
                    " . $this->table_name . "
                WHERE
                    name LIKE ? OR
                    contact_person LIKE ? OR
                    email LIKE ? OR
                    phone LIKE ?
                ORDER BY
                    name ASC
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
        $stmt->bindParam(4, $keywords);
        $stmt->bindParam(5, $from_record_num, PDO::PARAM_INT);
        $stmt->bindParam(6, $records_per_page, PDO::PARAM_INT);
        
        // Execute query
        $stmt->execute();
        
        return $stmt;
    }
    
    // Count search results
    public function countSearch($keywords) {
        // Query to count search results
        $query = "SELECT
                    COUNT(*) as total_rows
                FROM
                    " . $this->table_name . "
                WHERE
                    name LIKE ? OR
                    contact_person LIKE ? OR
                    email LIKE ? OR
                    phone LIKE ?";
        
        // Prepare statement
        $stmt = $this->conn->prepare($query);
        
        // Sanitize keywords
        $keywords = htmlspecialchars(strip_tags($keywords));
        $keywords = "%{$keywords}%";
        
        // Bind variables
        $stmt->bindParam(1, $keywords);
        $stmt->bindParam(2, $keywords);
        $stmt->bindParam(3, $keywords);
        $stmt->bindParam(4, $keywords);
        
        // Execute query
        $stmt->execute();
        
        // Get row
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_rows'];
    }
}