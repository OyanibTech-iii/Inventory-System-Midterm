<?php
/**
 * Initialization File
 * 
 * This file runs at the beginning of every page request to set up the environment
 */
require_once 'config/connect.php';

// Define constants
define('LOG_ERRORS', true); // Set to true to enable error logging
define('ERROR_LOG_PATH', __DIR__ . '/../logs/error.log');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DEBUG_MODE', true); // Set to true to enable debug mode

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Create logs directory if it doesn't exist
if (LOG_ERRORS && !file_exists(__DIR__ . '/../logs/')) {
    mkdir(__DIR__ . '/../logs/', 0755, true);
}

// Load user permissions if logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role_id']) && !isset($_SESSION['permissions'])) {
    loadUserPermissions($_SESSION['role_id']);
}

/**
 * Load user permissions from database based on role ID
 * 
 * @param int $role_id The role ID to load permissions for
 * @return void
 */
function loadUserPermissions($role_id) {
    global $conn;
    
    try {
        $query = "SELECT role_name, permissions FROM roles WHERE role_id = :role_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $role = $stmt->fetch(PDO::FETCH_ASSOC);
            $_SESSION['role'] = $role['role_name'];
            $_SESSION['permissions'] = json_decode($role['permissions'], true);
        }
    } catch (PDOException $e) {
        if (DEBUG_MODE) {
            echo "Error loading permissions: " . $e->getMessage();
        }
        
        if (LOG_ERRORS) {
            error_log(date(DATETIME_FORMAT) . " - Permission loading error: " . $e->getMessage() . "\n", 3, ERROR_LOG_PATH);
        }
    }
}

/**
 * Log activity
 * 
 * @param string $activity Description of the activity
 * @param int $user_id ID of the user performing the activity
 * @param string $module The module where the activity was performed
 * @param int $reference_id ID of the record being affected (optional)
 * @return bool True on success, false on failure
 */
function logActivity($activity, $user_id, $module, $reference_id = null) {
    global $conn;
    
    try {
        $query = "INSERT INTO activity_logs (user_id, activity, module, reference_id, ip_address, created_at) 
                 VALUES (:user_id, :activity, :module, :reference_id, :ip_address, NOW())";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':activity', $activity, PDO::PARAM_STR);
        $stmt->bindParam(':module', $module, PDO::PARAM_STR);
        $stmt->bindParam(':reference_id', $reference_id, PDO::PARAM_INT);
        $stmt->bindParam(':ip_address', $_SERVER['REMOTE_ADDR'], PDO::PARAM_STR);
        
        return $stmt->execute();
    } catch (PDOException $e) {
        if (LOG_ERRORS) {
            error_log(date(DATETIME_FORMAT) . " - Activity logging error: " . $e->getMessage() . "\n", 3, ERROR_LOG_PATH);
        }
        return false;
    }
}

/**
 * Create a basic User model class if it doesn't exist
 * This is needed for the login functionality
 */
if (!class_exists('User')) {
    class User {
        // Database connection
        private $conn;
        
        // User properties
        public $user_id;
        public $username;
        public $password;
        public $email;
        public $role_id;
        public $created_at;
        public $last_login;
        
        /**
         * Constructor
         * 
         * @param PDO $db Database connection
         */
        public function __construct($db) {
            $this->conn = $db;
        }
        
        /**
         * Login user
         * 
         * @return bool True if login successful, false otherwise
         */
        public function login() {
            // Check if username exists
            $query = "SELECT user_id, username, password_hash, role_id, email, created_at 
                     FROM users 
                     WHERE username = :username";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $this->username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verify password
                if (password_verify($this->password, $row['password_hash'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role_id'] = $row['role_id'];
                    $_SESSION['email'] = $row['email'];
                    
                    // Update last login time
                    $this->updateLastLogin($row['user_id']);
                    
                    // Load permissions
                    loadUserPermissions($row['role_id']);
                    
                    return true;
                }
            }
            
            return false;
        }
        
        /**
         * Update last login time
         * 
         * @param int $user_id User ID
         * @return void
         */
        private function updateLastLogin($user_id) {
            $query = "UPDATE users SET last_login = NOW() WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
        }
    }
}

// Create an activity_logs table if it doesn't exist
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'activity_logs'")->rowCount();
    
    if ($tableCheck == 0) {
        $createTable = "CREATE TABLE `activity_logs` (
            `log_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `activity` varchar(255) NOT NULL,
            `module` varchar(50) NOT NULL,
            `reference_id` int(11) DEFAULT NULL,
            `ip_address` varchar(45) NOT NULL,
            `created_at` datetime NOT NULL,
            PRIMARY KEY (`log_id`),
            KEY `user_id` (`user_id`),
            CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
        
        $conn->exec($createTable);
    }
} catch (PDOException $e) {
    if (DEBUG_MODE) {
        echo "Table creation error: " . $e->getMessage();
    }
    
    if (LOG_ERRORS) {
        error_log(date(DATETIME_FORMAT) . " - Table creation error: " . $e->getMessage() . "\n", 3, ERROR_LOG_PATH);
    }
}