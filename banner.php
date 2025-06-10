<?php
include 'models/authentication.php';
require_once "helpers/permissions.php";
require_once "config/database.php";
require_once "config/connect.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
if (isset($_SESSION["user_id"])) {
    $username = $_SESSION["username"];
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Handle banner image upload if form is submitted
$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_banner"])) {
    // Check if file was uploaded without errors
    if (isset($_FILES["banner_image"]) && $_FILES["banner_image"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["banner_image"]["name"];
        $filetype = $_FILES["banner_image"]["type"];
        $filesize = $_FILES["banner_image"]["size"];

        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $message = "Error: Please select a valid file format.";
            $message_type = "danger";
        }

        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $message = "Error: File size is larger than the allowed limit (5MB).";
            $message_type = "danger";
        }

        // Verify MIME type of the file
        if (in_array($filetype, $allowed)) {
            // Check if file exists before uploading
            $target_dir = "assets/";
            $target_file = $target_dir . "banner.png";

            // If all checks passed, move the uploaded file
            if (move_uploaded_file($_FILES["banner_image"]["tmp_name"], $target_file)) {
                $message = "The banner image has been uploaded successfully.";
                $message_type = "success";
            } else {
                $message = "Error: There was a problem uploading your file. Please try again.";
                $message_type = "danger";
            }
        } else {
            $message = "Error: There was a problem with the file type. Please try again.";
            $message_type = "danger";
        }
    } else {
        $message = "Error: " . $_FILES["banner_image"]["error"];
        $message_type = "danger";
    }
}

include "views/header.php";
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    *,
    body {
        font-family: 'Poppins', sans-serif;
    }
</style>
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">

            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            </div>
            <div class="text-primary container-fluid mb-4">
                <div class="row">
                    <div class="col-md-12 text-center">
                        <h1 class="display-4">
                            <strong>
                                Welcome <?= $username; ?>
                            </strong>
                        </h1>
                        <div class="text-secondary">
                        <p>Lorem ipsum dolor, sit amet consectetur adipisicing elit. Voluptate, accusantium. Dolore sapiente laborum voluptatem, natus eveniet, officiis error ducimus nam ab qui earum repudiandae aut ipsa, ea necessitatibus quam voluptate.</p>
                        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Ipsa similique in quo illum, sunt eaque reiciendis exercitationem repellendus, dolorem deleniti voluptas eos debitis hic eius repellat facere quos numquam sit!</p>
                        <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Voluptas quas ut beatae explicabo ea qui quo dignissimos alias incidunt maxime aut fugit non voluptatibus rem culpa harum, veritatis molestias aspernatur!</p>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="card-title">Current Banner</h5>
                        </div>
                        <div class="card-body p-0"> <!-- Removed padding to allow image to fill the card -->
                            <div class="banner-container">
                                <img src="assets/banner.png" alt="Store Banner" class="w-100 rounded-0"
                                    style="object-fit: cover; height: 400px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (checkPermission('system_settings')): ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="card shadow">
                            <div class="card-header">
                                <h5 class="card-title">Upload New Banner Image</h5>
                            </div>
                            <div class="card-body">
                                <form action="banner.php" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="banner_image" class="form-label">Select Image</label>
                                        <input class="form-control" type="file" id="banner_image" name="banner_image"
                                            accept=".jpg, .jpeg, .png" required>
                                        <div class="form-text">Recommended dimensions: 1920×400 pixels. Max file size: 5MB.
                                        </div>
                                    </div>
                                    <button type="submit" name="update_banner" class="btn btn-primary">Update
                                        Banner</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h5 class="card-title">Banner Usage Instructions</h5>
                        </div>
                        <div class="card-body">
                            <ul>
                                <li>The banner is displayed at the top of the store's homepage</li>
                                <li>Current promotions and seasonal offers should be highlighted in the banner</li>
                                <li>Update the banner regularly to keep the store looking fresh</li>
                                <li>Make sure text is readable and contrasts well with the background</li>
                                <li>Keep consistent branding elements in all banner designs</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add padding at the bottom to ensure content doesn't get hidden by footer -->
            <div class="mb-5 pb-4"></div>
        </main>
    </div>
</div>

<!-- Modified footer markup to align with the main content area -->
<footer class="footer mt-auto py-3 bg-primary">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2">
                <!-- Empty space for sidebar alignment -->
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-light">Inventory Management System &copy; <?php echo date('Y'); ?></span>
                    <span class="text-light">Version 1.0</span>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function () {
        feather.replace();
    });

    // Preview upload image before submission
    document.addEventListener('DOMContentLoaded', function () {
        const bannerInput = document.getElementById('banner_image');
        if (bannerInput) {
            bannerInput.addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const previewContainer = document.querySelector('.banner-container img');
                        if (previewContainer) {
                            previewContainer.src = e.target.result;
                        }
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
    });
</script>
<!-- Custom scripts -->
<script src="assets/js/scripts.js"></script>
</body>

</html>