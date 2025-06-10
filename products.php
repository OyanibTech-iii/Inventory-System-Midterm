<?php
require_once("models/authentication.php");
// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Check permissions
if (!checkPermission('view_products')) {
    header("location: access_denied.php");
    exit;
}

// Include required files
require_once "config/database.php";
require_once "config/connect.php";
require_once "models/Product.php";
require_once "helpers/pagination.php";

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize product object
$product = new Product($db);

// Pagination variables
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Query products
$stmt = $product->readAll($from_record_num, $records_per_page);
$num = $stmt->rowCount();

// Get total count for pagination
$total_rows = $product->countAll();
$total_pages = ceil($total_rows / $records_per_page);

// Include header
include "views/header.php";
?>
<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Products</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (checkPermission('add_product')): ?>
                    <a href="product_create.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add New Product
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Search Form -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="products.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search products...">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="dropdown float-end">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="categoryFilter" data-bs-toggle="dropdown">
                            Filter by Category
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="categoryFilter">
                            <li><a class="dropdown-item" href="products.php">All Categories</a></li>
                            <?php
                            $categories = $product->getCategories();
                            foreach($categories as $category) {
                                echo "<li><a class='dropdown-item' href='products.php?category={$category['category']}'>{$category['category']}</a></li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Products Table -->
            <?php if ($num > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock Level</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['sku']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['category']; ?></td>
                            <td>$<?php echo number_format($row['price'], 2); ?></td>
                            <td>
                                <?php if ($row['stock_level'] <= $row['minimum_stock_level']): ?>
                                <span class="badge bg-danger"><?php echo $row['stock_level']; ?></span>
                                <?php else: ?>
                                <span class="badge bg-success"><?php echo $row['stock_level']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $row['is_active'] ? 
                                    '<span class="badge bg-success">Active</span>' : 
                                    '<span class="badge bg-secondary">Inactive</span>'; ?>
                            </td>
                            <td>
                                <?php if (checkPermission('view_product_details')): ?>
                                <a href="product_read.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('edit_product')): ?>
                                <a href="product_update.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('delete_product')): ?>
                                <a href="product_delete.php?id=<?php echo $row['product_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="bi bi-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php 
                    // Display pagination links
                    for ($i = 1; $i <= $total_pages; $i++) {
                        echo ($i == $page) ? 
                            "<li class='page-item active'><a class='page-link' href='#'>{$i}</a></li>" : 
                            "<li class='page-item'><a class='page-link' href='products.php?page={$i}'>{$i}</a></li>";
                    }
                    ?>
                </ul>
            </nav>
            
            <?php else: ?>
            <div class="alert alert-info">No products found.</div>
            <?php endif; ?>
            
        </main>
    </div>
</div>

<?php include "views/footer.php"; ?>