<?php
include 'models/authentication.php';
require_once "helpers/permissions.php";
require_once "config/database.php";
require_once "config/connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle profile image update
$message = '';
$messageType = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["profile_image"])) {
    $upload_dir = "uploads/profiles/";
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Check if a file was actually uploaded
    if ($_FILES["profile_image"]["error"] == UPLOAD_ERR_NO_FILE) {
        $message = "No file was uploaded. Please select an image file.";
        $messageType = "warning";
    } else if ($_FILES["profile_image"]["error"] !== UPLOAD_ERR_OK) {
        // Handle other upload errors
        $message = "There was an error with the file upload. Error code: " . $_FILES["profile_image"]["error"];
        $messageType = "danger";
    } else if (!is_uploaded_file($_FILES["profile_image"]["tmp_name"]) || empty($_FILES["profile_image"]["tmp_name"])) {
        $message = "Invalid file upload attempt.";
        $messageType = "danger";
    } else {
        $user_id = $_SESSION["user_id"];
        $file_name = $user_id . "_" . basename($_FILES["profile_image"]["name"]);
        $target_file = $upload_dir . $file_name;
        $upload_ok = 1;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        
        // Check if image file is an actual image
        $check = getimagesize($_FILES["profile_image"]["tmp_name"]);
        if ($check === false) {
            $message = "File is not an image.";
            $messageType = "danger";
            $upload_ok = 0;
        }
        
        // Check file size (limit to 2MB)
        if ($_FILES["profile_image"]["size"] > 2000000) {
            $message = "Sorry, your file is too large. Max 2MB allowed.";
            $messageType = "danger";
            $upload_ok = 0;
        }
        
        // Allow only certain file formats
        if ($image_file_type != "jpg" && $image_file_type != "png" && $image_file_type != "jpeg" && $image_file_type != "gif") {
            $message = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $messageType = "danger";
            $upload_ok = 0;
        }
        
        // If everything is ok, try to upload file
        if ($upload_ok == 1) {
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                // First check if profile_image column exists in the users table
                $check_column = "SHOW COLUMNS FROM users LIKE 'profile_image'";
                $check_stmt = $db->prepare($check_column);
                $check_stmt->execute();
                $column_exists = $check_stmt->rowCount() > 0;

                // If the column doesn't exist, create it
                if (!$column_exists) {
                    $create_column = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255)";
                    $create_stmt = $db->prepare($create_column);
                    $create_stmt->execute();
                }
                
                // Update profile image path in database
                $query = "UPDATE users SET profile_image = ? WHERE user_id = ?";
                $stmt = $db->prepare($query);
                $profile_path = $target_file;
                $stmt->bindParam(1, $profile_path);
                $stmt->bindParam(2, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION["profile_image"] = $profile_path;
                    $message = "Your profile image has been updated successfully.";
                    $messageType = "success";
                } else {
                    $message = "There was an error updating your profile image in the database.";
                    $messageType = "danger";
                }
            } else {
                $message = "There was an error uploading your file.";
                $messageType = "danger";
            }
        }
    }
}

include "views/header.php";
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,body{
    font-family: 'Poppins', sans-serif;
}

</style>
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
            </div>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Profile Picture</h5>
                        </div>
                        <div class="card-body text-center">
                            <?php 
                            $profile_image = "assets/profile.png"; // Updated default image path
                            if (isset($_SESSION["profile_image"]) && !empty($_SESSION["profile_image"])) {
                                $profile_image = $_SESSION["profile_image"];
                            }
                            ?>
                            <img src="<?php echo $profile_image; ?>" class="img-fluid rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover; border: 3px solid #ddd;">
                            
                            <form action="profile.php" method="post" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="profile_image" class="form-label">Change Profile Picture</label>
                                    <input class="form-control" type="file" id="profile_image" name="profile_image">
                                    <div class="form-text">JPG, PNG, or GIF. Max 2MB.</div>
                                </div>
                                <button type="submit" class="btn btn-primary">Upload New Image</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Account Information</h5>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title">Welcome to ShoeStore</h5>
                            <p class="card-text">Browse our exclusive collection of shoes and find your perfect style.</p>
                            
                            <hr>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Username</label>
                                <p><?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Email</label>
                                <?php
                                // Get user email from database
                                $query = "SELECT email FROM users WHERE user_id = ?";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(1, $_SESSION["user_id"]);
                                $stmt->execute();
                                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <p><?php echo htmlspecialchars($user_data['email'] ?? 'No email available'); ?></p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Role</label>
                                <?php
                                // Get user role from database
                                $query = "SELECT r.role_name FROM users u 
                                         JOIN roles r ON u.role_id = r.role_id 
                                         WHERE u.user_id = ?";
                                $stmt = $db->prepare($query);
                                $stmt->bindParam(1, $_SESSION["user_id"]);
                                $stmt->execute();
                                $role_data = $stmt->fetch(PDO::FETCH_ASSOC);
                                ?>
                                <p><?php echo htmlspecialchars($role_data['role_name'] ?? 'User'); ?></p>
                            </div>
                            
                            <a href="#" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</a>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Latest Shoe Arrivals</h5>
                        </div>
                        <div class="card-body">
                            <p>Check out our newest shoe collection and stay on trend!</p>
                            <a href="product.php" class="btn btn-primary">Browse Shoes</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Change Password Modal -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <form action="update_password.php" method="post">
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password" name="current_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="submit" class="btn btn-primary">Change Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Add padding at the bottom -->
            <div class="mb-5 pb-4"></div>
        </main>
    </div>
</div>

<?php include "views/footer.php"; ?>