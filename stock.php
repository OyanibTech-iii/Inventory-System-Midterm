<?php
require_once("models/authentication.php");
require_once "config/database.php";
require_once "config/connect.php";
require_once "helpers/permissions.php"; // Make sure this path is correct
require_once "models/Stock.php";
require_once "models/Product.php";
require_once "helpers/pagination.php";

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Check permissions - Now this will work since we've included the file above
if (!checkPermission('view_stock')) {
    header("location: access_denied.php");
    exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize stock object
$stock = new Stock($db);
$product = new Product($db);

// Pagination variables
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Query stock
$stmt = $stock->readAll($from_record_num, $records_per_page);
$num = $stmt->rowCount();

// Get total count for pagination
$total_rows = $stock->countAll();
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
                <h1 class="h2">Stock Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (checkPermission('add_stock')): ?>
                    <a href="stock_add.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add Stock
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Search and Filter Forms -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="stock.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search by product name or SKU..."autocomplete="off">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <div class="dropdown float-end">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="locationFilter" data-bs-toggle="dropdown">
                            Filter by Location
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="locationFilter">
                            <li><a class="dropdown-item" href="stock.php">All Locations</a></li>
                            <?php
                            $locations = $stock->getLocations();
                            foreach($locations as $location) {
                                echo "<li><a class='dropdown-item' href='stock.php?location={$location['location_code']}'>{$location['location_code']}</a></li>";
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Stock Table -->
            <?php if ($num > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Current Quantity</th>
                            <th>Location</th>
                            <th>Batch #</th>
                            <th>Date Added</th>
                            <th>Expiry Date</th>
                            <th>Added By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['product_name']; ?></td>
                            <td><?php echo $row['sku']; ?></td>
                            <td>
                                <?php if ($row['current_quantity'] <= $row['minimum_stock_level']): ?>
                                <span class="badge bg-danger"><?php echo $row['current_quantity']; ?></span>
                                <?php else: ?>
                                <span class="badge bg-success"><?php echo $row['current_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $row['location_code']; ?></td>
                            <td><?php echo $row['batch_number']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['date_added'])); ?></td>
                            <td>
                                <?php 
                                if (!empty($row['expiry_date'])) {
                                    $expiry_date = strtotime($row['expiry_date']);
                                    $now = time();
                                    $diff = $expiry_date - $now;
                                    $days = floor($diff / (60 * 60 * 24));
                                    
                                    if ($days < 0) {
                                        echo '<span class="badge bg-danger">Expired</span>';
                                    } elseif ($days < 30) {
                                        echo '<span class="badge bg-warning">'.date('M d, Y', $expiry_date).'</span>';
                                    } else {
                                        echo date('M d, Y', $expiry_date);
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td>
                                <?php 
                                // Display username with different color if admin
                                if (!empty($row['added_by'])) {
                                    if (!empty($row['is_admin']) && $row['is_admin'] == 1) {
                                        echo '<span class="text-danger fw-bold">' . htmlspecialchars($row['added_by']) . '</span>';
                                    } else {
                                        echo htmlspecialchars($row['added_by']);
                                    }
                                } else {
                                    echo 'Unknown';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if (checkPermission('view_stock_details')): ?>
                                <a href="stock_view.php?id=<?php echo $row['stock_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('edit_stock')): ?>
                                <a href="stock_update.php?id=<?php echo $row['stock_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('adjust_stock')): ?>
                                <a href="stock_adjust.php?id=<?php echo $row['stock_id']; ?>" class="btn btn-sm btn-warning">
                                    <i class="bi bi-gear"></i>
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
                            "<li class='page-item'><a class='page-link' href='stock.php?page={$i}'>{$i}</a></li>";
                    }
                    ?>
                </ul>
            </nav>
            
            <?php else: ?>
            <div class="alert alert-info">No stock records found.</div>
            <?php endif; ?>
            
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>

<?php include "views/footer.php"; ?>