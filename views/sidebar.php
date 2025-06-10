<link rel="stylesheet" href="sidebarhover.css">
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-primary sidebar collapse">
    <div class="position-sticky pt-3">
        <?php
        // Check if user is logged in and is not an admin
        if (isset($_SESSION["user_id"]) && (!isset($_SESSION["is_admin"]) || $_SESSION["is_admin"] !== true)):
            ?>
            <!-- User Profile Section - Only shown for regular users -->
            <div class="user-profile-section text-center mb-4">
                <div class="profile-image-container mb-2">
                    <?php
                    // Check if user has a custom profile image
                    $profile_image = "assets/default_profile.png"; // Default image path
                    if (isset($_SESSION["profile_image"]) && !empty($_SESSION["profile_image"])) {
                        $profile_image = $_SESSION["profile_image"];
                    }
                    ?>
                    <img src="<?php echo $profile_image; ?>" alt="Profile" class="rounded-circle profile-image"
                        style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #fff;">
                    <div class="change-image-overlay">
                        <a href="profile.php" class="text-white small">Change</a>
                    </div>
                </div>
                <h6 class="text-white mb-0"><?php echo htmlspecialchars($_SESSION["username"]); ?></h6>
                <p class="text-light small mb-3">Welcome to ShoeStore!</p>
                <a href="profile.php" class="btn btn-sm btn-primary text-light mb-3">Edit Profile</a>
            </div>
            <hr class="text-white">
        <?php endif; ?>

        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>"
                    href="index.php">
                    <i data-feather="activity"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'banner.php' ? 'active' : ''; ?>"
                    href="banner.php">
                    <i data-feather="image"></i>
                    Banner
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'product.php' ? 'active' : ''; ?>"
                    href="product.php">
                    <i data-feather="package"></i>
                    Products
                </a>
            </li>


            <?php if (checkPermission('view_sales') || checkPermission('view_own_sales')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>"
                        href="sales.php">
                        <i data-feather="shopping-cart"></i>
                        Sales
                    </a>
                </li>
            <?php endif; ?>

            <?php if (checkPermission('view_stock')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stock.php' ? 'active' : ''; ?>"
                        href="stock.php">
                        <i data-feather="package"></i>
                        Stock
                    </a>
                </li>
            <?php endif; ?>

            <?php if (checkPermission('view_suppliers')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'supplier.php' ? 'active' : ''; ?>"
                        href="supplier.php">
                        <i data-feather="users"></i>
                        Suppliers
                    </a>
                </li>
            <?php endif; ?>

            <?php if (checkPermission('view_reports')): ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"
                        href="reports.php">
                        <i data-feather="bar-chart-2"></i>
                        Reports
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>