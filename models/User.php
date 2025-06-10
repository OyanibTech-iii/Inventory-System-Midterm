<?php
// File: models/User.php (with fixes for the foreign key constraint issue)
// Assuming there's an existing User class, we'll enhance it

class User
{
    // Database connection and table name
    private $conn;
    private $table_name = "users";

    // Object properties
    public $user_id;
    public $username;
    public $password;
    public $role_id;
    public $email;
    public $created_at;
    public $last_login;

    // Constructor with database connection
    public function __construct($db)
    {
        $this->conn = $db;
    }
    public function isAdmin($user_id)
    {
        // Query to check if the user has role_id = 1 (admin)
        $query = "SELECT role_id FROM " . $this->table_name . " WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            // Return true if role_id is 1 (admin)
            return ($row['role_id'] == 1);
        }

        return false;
    }

    // Login method with profile image support
    public function login()
    {
        // Regular login process for all users (including administrator)
        $query = "SELECT u.*, r.role_name 
         FROM " . $this->table_name . " u
         JOIN roles r ON u.role_id = r.role_id
         WHERE BINARY u.username = ?";  // Added BINARY keyword here
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        if ($stmt->rowCount() == 1) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (password_verify($this->password, $row["password_hash"])) {
                // Set session variables
                $_SESSION["user_id"] = $row["user_id"];
                $_SESSION["username"] = $row["username"];
                $_SESSION["role_id"] = $row["role_id"];

                // Add profile image to session if available
                if (isset($row["profile_image"]) && !empty($row["profile_image"])) {
                    $_SESSION["profile_image"] = $row["profile_image"];
                }

                // Check if this user is an admin (role_id = 1)
                if ($row["role_id"] == 1) {
                    $_SESSION["is_admin"] = true;
                } else {
                    $_SESSION["is_admin"] = false;
                }

                // Update last login time
                $this->updateLastLogin($row["user_id"]);

                // Log successful login
                $this->logLogin($row["user_id"], $row["username"], true);

                return true;
            } else {
                // Log failed login attempt
                $this->logLogin(null, $this->username, false);
                return false;
            }
        } else {
            // Log failed login attempt
            $this->logLogin(null, $this->username, false);
            return false;
        }
    }

    // Update last login time
    private function updateLastLogin($user_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                 SET last_login = NOW() 
                 WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $user_id);

        return $stmt->execute();
    }

    // Get all users with their roles
    public function getAllUsers()
    {
        $query = "SELECT u.*, r.role_name 
                 FROM " . $this->table_name . " u
                 JOIN roles r ON u.role_id = r.role_id
                 ORDER BY u.user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    // Update user role
    public function updateUserRole($user_id, $new_role_id)
    {
        $query = "UPDATE " . $this->table_name . " 
                 SET role_id = ? 
                 WHERE user_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $new_role_id);
        $stmt->bindParam(2, $user_id);

        return $stmt->execute();
    }

    // Log login attempts - FIXED to handle NULL user_id for special admin
    private function logLogin($user_id, $username, $success)
    {
        // Create login_logs table if it doesn't exist
        $this->createLoginLogsTableIfNotExists();

        // Prepare query
        $query = "INSERT INTO login_logs (user_id, username, login_time, ip_address, login_success) 
                 VALUES (?, ?, NOW(), ?, ?)";

        $stmt = $this->conn->prepare($query);
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $success_value = $success ? 1 : 0;

        // Bind parameters - user_id can be NULL for failed logins
        $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
        $stmt->bindParam(2, $username);
        $stmt->bindParam(3, $ip_address);
        $stmt->bindParam(4, $success_value);

        return $stmt->execute();
    }

    // Create login_logs table if it doesn't exist - MODIFIED to handle special admin login
    private function createLoginLogsTableIfNotExists()
    {
        $query = "CREATE TABLE IF NOT EXISTS login_logs (
                    log_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    username VARCHAR(50) NOT NULL,
                    login_time DATETIME NOT NULL,
                    ip_address VARCHAR(50) NOT NULL,
                    login_success TINYINT(1) NOT NULL DEFAULT 0,
                    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
                 )";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
    }

    public function usernameExists($username)
    {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE BINARY username = ?";  // Added BINARY keyword
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $username);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function emailExists($email)
    {
        $query = "SELECT user_id FROM " . $this->table_name . " WHERE BINARY email = ?";  // Added BINARY keyword
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    // ADDED: Register method
    public function register()
    {
        // Insert query
        $query = "INSERT INTO " . $this->table_name . " 
                (username, email, password_hash, role_id, created_at) 
                VALUES (?, ?, ?, 5, NOW())";

        // Prepare statement
        $stmt = $this->conn->prepare($query);

        // Sanitize and hash password
        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $password_hash = password_hash($this->password, PASSWORD_DEFAULT);

        // Bind parameters
        $stmt->bindParam(1, $this->username);
        $stmt->bindParam(2, $this->email);
        $stmt->bindParam(3, $password_hash);

        // Execute query
        if ($stmt->execute()) {
            return true;
        }

        return false;
    }

    // Get login history
    public function getLoginHistory()
    {
        // Create login_logs table if it doesn't exist
        $this->createLoginLogsTableIfNotExists();

        $query = "SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 100";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        return false;
    }

    // Method to add profile_image column if it doesn't exist
    public function addProfileImageColumn()
    {
        $query = "SHOW COLUMNS FROM {$this->table_name} LIKE 'profile_image'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            // Column doesn't exist, add it
            $query = "ALTER TABLE {$this->table_name} ADD COLUMN profile_image VARCHAR(255) NULL";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
    }

    // Method to update profile image
    public function updateProfileImage($user_id, $image_path)
    {
        // Ensure the profile_image column exists
        $this->addProfileImageColumn();

        $query = "UPDATE {$this->table_name} SET profile_image = ? WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $image_path);
        $stmt->bindParam(2, $user_id);

        return $stmt->execute();
    }
}