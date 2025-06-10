<?php
require_once("models/authentication.php");

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Include required files
require_once "config/database.php";
require_once "config/connect.php";
require_once "models/Inventory.php";
require_once "helpers/permissions.php";

// Check permissions
if (!checkPermission('inventory_management')) {
    header("location: access_denied.php");
    exit;
}

$database = new Database();
$db = $database->getConnection();

// Initialize inventory object
$inventory = new Inventory($db);

// Process form submissions
$message = "";
$messageType = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_stock"])) {
    $id = $_POST["id"];
    $new_quantity = $_POST["quantity"];

    if ($inventory->updateStock($id, $new_quantity)) {
        $message = "Stock updated successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to update stock.";
        $messageType = "danger";
    }
}

// Similarly, for reorder level updates:
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_reorder"])) {
    $id = $_POST["id"];
    $reorder_level = $_POST["reorder_level"];

    if ($inventory->updateReorderLevel($id, $reorder_level)) {
        $message = "Reorder level updated successfully!";
        $messageType = "success";
    } else {
        $message = "Failed to update reorder level.";
        $messageType = "danger";
    }
}

// Get inventory data
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';
$stock_status = isset($_GET['stock_status']) ? $_GET['stock_status'] : '';

// Pagination
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$records_per_page = 10;
$start_from = ($page - 1) * $records_per_page;

// Get inventory items
$inventory_items = $inventory->getInventory($search, $category, $stock_status, $start_from, $records_per_page);
$total_records = $inventory->getTotalCount($search, $category, $stock_status);
$total_pages = ceil($total_records / $records_per_page);

// Get all categories for filter
$categories = $inventory->getAllCategories();

// Include header
include "views/header.php";
?>

<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Inventory Management</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();">
                            <i data-feather="printer"></i> Print
                        </button>
                        <?php if (checkPermission('export_inventory')): ?>
                            <a href="inventory_export.php?type=pdf" class="btn btn-sm btn-outline-secondary">
                                <i data-feather="file"></i> Export PDF
                            </a>
                            <a href="inventory_export.php?type=excel" class="btn btn-sm btn-outline-secondary">
                                <i data-feather="file-text"></i> Export Excel
                            </a>
                        <?php endif; ?>
                        <!-- <?php if (checkPermission('add_product')): ?>
                            <a href="add_product.php" class="btn btn-sm btn-primary">
                                <i data-feather="plus"></i> Add New Product
                            </a>
                        <?php endif; ?> -->
                    </div>
                </div>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Search and Filter Options -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form action="inventory.php" method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" name="search" class="form-control"
                                        placeholder="Search products..."
                                        value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <select name="category" class="form-select">
                                        <option value="">All Categories</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($category == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select name="stock_status" class="form-select">
                                        <option value="">All Stock Status</option>
                                        <option value="low" <?php echo ($stock_status == 'low') ? 'selected' : ''; ?>>Low
                                            Stock</option>
                                        <option value="out" <?php echo ($stock_status == 'out') ? 'selected' : ''; ?>>Out
                                            of Stock</option>
                                        <option value="normal" <?php echo ($stock_status == 'normal') ? 'selected' : ''; ?>>Normal</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Products</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $inventory->getTotalProducts(); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Stock Value</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($inventory->getTotalStockValue(), 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-cash-stack fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Low Stock Items</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $inventory->getLowStockCount(); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Out of Stock Items</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $inventory->getOutOfStockCount(); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-x-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Inventory List</h6>
                </div>
                <div class="card-body">
                    <?php if (count($inventory_items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="inventoryTable" width="100%"
                                cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Product Name</th>
                                        <th>Category</th>
                                        <th>SKU</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Unit Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($inventory_items as $item): ?>
                                        <tr
                                            class="<?php echo ($item['current_stock'] <= $item['reorder_level']) ? ($item['current_stock'] == 0 ? 'table-danger' : 'table-warning') : ''; ?>">
                                            <td><?php echo $item['id']; ?></td>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                            <td>
                                                <?php if (checkPermission('update_stock')): ?>
                                                    <form method="post" class="d-inline stock-update-form">
                                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="update_stock" value="1">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" name="quantity" class="form-control"
                                                                value="<?php echo $item['current_stock']; ?>" min="0">
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-primary">Update</button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <?php echo $item['current_stock']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (checkPermission('update_stock')): ?>
                                                    <form method="post" class="d-inline reorder-update-form">
                                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                                        <input type="hidden" name="update_reorder" value="1">
                                                        <div class="input-group input-group-sm">
                                                            <input type="number" name="reorder_level" class="form-control"
                                                                value="<?php echo $item['reorder_level']; ?>" min="0">
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-primary">Update</button>
                                                        </div>
                                                    </form>
                                                <?php else: ?>
                                                    <?php echo $item['reorder_level']; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>$<?php echo number_format($item['unit_price'], 2); ?></td>
                                            <td>
                                                <?php if ($item['current_stock'] == 0): ?>
                                                    <span class="badge bg-danger">Out of Stock</span>
                                                <?php elseif ($item['current_stock'] <= $item['reorder_level']): ?>
                                                    <span class="badge bg-warning text-dark">Low Stock</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">In Stock</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (checkPermission('view_product_details')): ?>
                                                    <a href="product_details.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-info">
                                                        <i data-feather="eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (checkPermission('edit_product')): ?>
                                                    <a href="edit_product.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-primary">
                                                        <i data-feather="edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if (checkPermission('delete_product')): ?>
                                                    <a href="delete_product.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this product?');">
                                                        <i data-feather="trash-2"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="row">
                            <div class="col-md-6">
                                <p>Showing <?php echo min(($page - 1) * $records_per_page + 1, $total_records); ?> to
                                    <?php echo min($page * $records_per_page, $total_records); ?> of
                                    <?php echo $total_records; ?> records</p>
                            </div>
                            <div class="col-md-6">
                                <nav aria-label="Page navigation">
                                    <ul class="pagination justify-content-end">
                                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                            <a class="page-link"
                                                href="<?php echo 'inventory.php?page=' . ($page - 1) . '&search=' . $search . '&category=' . $category . '&stock_status=' . $stock_status; ?>"
                                                aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                            <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                                <a class="page-link"
                                                    href="<?php echo 'inventory.php?page=' . $i . '&search=' . $search . '&category=' . $category . '&stock_status=' . $stock_status; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                            <a class="page-link"
                                                href="<?php echo 'inventory.php?page=' . ($page + 1) . '&search=' . $search . '&category=' . $category . '&stock_status=' . $stock_status; ?>"
                                                aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No inventory items found.</div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function () {
        feather.replace();

        // Add event listeners for form submissions
        const stockForms = document.querySelectorAll('.stock-update-form');
        stockForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                // You can add validation here if needed
            });
        });

        const reorderForms = document.querySelectorAll('.reorder-update-form');
        reorderForms.forEach(form => {
            form.addEventListener('submit', function (e) {
                // You can add validation here if needed
            });
        });
    });
</script>

<?php include "views/footer.php"; ?>