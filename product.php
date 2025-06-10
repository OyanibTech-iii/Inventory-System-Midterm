<?php
include 'models/authentication.php';
// Include required files
require_once "config/database.php";
require_once "models/product.php";
require_once "helpers/permissions.php";
include "views/header.php";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Product object
$product = new Product($db);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Set page parameters for pagination
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Read products with pagination
$stmt = $product->readAll($from_record_num, $records_per_page);
$num = $stmt->rowCount();

// Get total count for pagination
$total_rows = $product->countAll();
$total_pages = ceil($total_rows / $records_per_page);

// Check if the current user is an admin
$isAdmin = checkPermission('manage_products');

// Count items in cart
$cartItemCount = 0;
foreach ($_SESSION['cart'] as $item) {
    $cartItemCount += $item['quantity'];
}
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,body{
    font-family: 'Poppins', sans-serif;
}
</style>
<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Products</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="cart.php" class="btn btn-sm btn-outline-success position-relative">
                            <i data-feather="shopping-cart"></i> View Cart
                            <?php if ($cartItemCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $cartItemCount; ?>
                            </span>
                            <?php endif; ?>
                        </a>
                        <?php if($isAdmin): ?>
                        <a href="product_create.php" class="btn btn-sm btn-outline-primary">
                            <i data-feather="plus"></i> Add New Product
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Search and filter options -->
            <div class="row mb-3">
                <div class="col-md-8">
                    <form action="product.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search products..." 
                            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                        <button type="submit" class="btn btn-primary">Search</button>
                    </form>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php
                        $categories = $product->getCategories();
                        foreach($categories as $category) {
                            $selected = (isset($_GET['category']) && $_GET['category'] == $category['category']) ? 'selected' : '';
                            echo "<option value='{$category['category']}' {$selected}>{$category['category']}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>
            
            <!-- Products table -->
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>SKU</th>
                            <th>Stock Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if($num > 0) {
                            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                extract($row);
                                
                                // Check if stock is low
                                $stock_class = '';
                                if($stock_level <= $minimum_stock_level) {
                                    $stock_class = 'text-danger fw-bold';
                                }
                                
                                echo "<tr>
                                    <td>{$product_id}</td>
                                    <td>{$name}</td>
                                    <td>{$category}</td>
                                    <td>\${$price}</td>
                                    <td>{$sku}</td>
                                    <td class='{$stock_class}'>{$stock_level}</td>
                                    <td>
                                        <div class='btn-group btn-group-sm'>
                                            <a href='product_view.php?id={$product_id}' class='btn btn-outline-primary'>
                                                <i data-feather='eye'></i>
                                            </a>";
                                            
                                if($isAdmin) {
                                    echo "<a href='product_edit.php?id={$product_id}' class='btn btn-outline-secondary'>
                                            <i data-feather='edit'></i>
                                        </a>";
                                }
                                
                                if($stock_level > 0) {
                                    echo "<a href='add_to_cart.php?id={$product_id}' class='btn btn-outline-success'>
                                            <i data-feather='shopping-cart'></i>
                                        </a>";
                                }
                                            
                                echo "</div>
                                    </td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' class='text-center'>No products found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php 
                    // Previous page link
                    if($page > 1) {
                        echo "<li class='page-item'>
                                <a class='page-link' href='product.php?page=".($page-1)."'>Previous</a>
                              </li>";
                    } else {
                        echo "<li class='page-item disabled'>
                                <a class='page-link' href='#'>Previous</a>
                              </li>";
                    }
                    
                    // Page numbers
                    for($i=1; $i<=$total_pages; $i++) {
                        if($i == $page) {
                            echo "<li class='page-item active'><a class='page-link' href='#'>{$i}</a></li>";
                        } else {
                            echo "<li class='page-item'><a class='page-link' href='product.php?page={$i}'>{$i}</a></li>";
                        }
                    }
                    
                    // Next page link
                    if($page < $total_pages) {
                        echo "<li class='page-item'>
                                <a class='page-link' href='product.php?page=".($page+1)."'>Next</a>
                              </li>";
                    } else {
                        echo "<li class='page-item disabled'>
                                <a class='page-link' href='#'>Next</a>
                              </li>";
                    }
                    ?>
                </ul>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Footer -->
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
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>