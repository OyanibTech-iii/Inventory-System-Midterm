<?php
require_once("models/authentication.php");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

require_once "helpers/permissions.php";

// Check permissions
if (!checkPermission('view_sales') && !checkPermission('view_own_sales')) {
    header("location: access_denied.php");
    exit;
}

// Include required files
require_once "config/database.php";
require_once "config/connect.php";
require_once "models/Sale.php";
require_once "helpers/pagination.php";
require_once "helpers/permissions.php";

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize sale object
$sale = new Sale($db);

// For view_own_sales permission only, restrict to current user's sales
if (!checkPermission('view_sales') && checkPermission('view_own_sales')) {
    $sale->user_id = $_SESSION['user_id'];
}

// Pagination variables
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Handle date filter
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

if (!empty($date_from) && !empty($date_to)) {
    $sale->date_from = $date_from;
    $sale->date_to = $date_to;
}

// Query sales
$stmt = $sale->readAll($from_record_num, $records_per_page);
$num = $stmt->rowCount();

// Get total count for pagination
$total_rows = $sale->countAll();
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
                <h1 class="h2">Sales</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (checkPermission('add_sale')): ?>
                    <a href="sale_create.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> New Sale
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Search and Filter Forms -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="sales.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search by invoice number..." autocomplete="off">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="sales.php" method="POST" class="row g-3" >
                        <div class="col-md-5">
                            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" placeholder="From Date">
                        </div>
                        <div class="col-md-5">
                            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" placeholder="To Date">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-secondary w-100">Filter</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Sales Table -->
            <?php if ($num > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Total Amount</th>
                            <th>Payment Method</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['invoice_number']; ?></td>
                            <td><?php echo $row['customer_name'] ? $row['customer_name'] : 'Walk-in Customer'; ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['sale_date'])); ?></td>
                            <td><?php echo $row['item_count']; ?></td>
                            <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                            <td><?php echo $row['payment_method']; ?></td>
                            <td>
                                <?php if (checkPermission('view_sales') || (checkPermission('view_own_sales') && $row['user_id'] == $_SESSION['user_id'])): ?>
                                <a href="sale_view.php?id=<?php echo $row['sale_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('generate_invoice')): ?>
                                <a href="sale_invoice.php?id=<?php echo $row['sale_id']; ?>" class="btn btn-sm btn-secondary" target="_blank">
                                    <i class="bi bi-file-text"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('void_sale') && strtotime($row['sale_date']) > strtotime('-24 hours')): ?>
                                <a href="sale_void.php?id=<?php echo $row['sale_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to void this sale?')">
                                    <i class="bi bi-x-circle"></i>
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
                            "<li class='page-item'><a class='page-link' href='sales.php?page={$i}'>{$i}</a></li>";
                    }
                    ?>
                </ul>
            </nav>
            
            <?php else: ?>
            <div class="alert alert-info">No sales found.</div>
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
