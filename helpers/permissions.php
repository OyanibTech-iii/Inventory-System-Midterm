<?php

function checkPermission($permission_name) {
    // If user is admin (role_id = 1), grant all permissions
    if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true) {
        return true;
    }
    
    // For non-admin users, check specific permissions
    // This would typically query a permissions table based on role_id
    $role_id = $_SESSION["role_id"] ?? 0;
    
    // Get database connection
    require_once "config/database.php";
    $database = new Database();
    $db = $database->getConnection();
    
    // Make sure $db is not null before proceeding
    if (!$db) {
        // Handle connection error
        error_log("Database connection failed");
        return false;
    }
    
    // Query the permissions for this role
    $query = "SELECT p.permission_name 
              FROM role_permissions rp
              JOIN permissions p ON rp.permission_id = p.permission_id
              WHERE rp.role_id = ?";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(1, $role_id);
    $stmt->execute();
    
    // Check if the requested permission is in the results
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['permission_name'] == $permission_name) {
            return true;
        }
    }
    
    return false;
}

// Add this function to permissions.php
function checkUserRole($role_name) {
    // Check if user is logged in
    if (!isset($_SESSION["role_name"])) {
        return false;
    }
    
    // Compare the user's role with the requested role
    if ($_SESSION["role_name"] == $role_name) {
        return true;
    }
    
    return false;
}