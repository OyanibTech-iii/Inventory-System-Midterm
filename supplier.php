<?php

require_once("models/authentication.php");
require_once "config/database.php";
require_once "config/connect.php";
require_once "models/Supplier.php";
require_once "helpers/pagination.php";
require_once "helpers/permissions.php";

// Check permissions
if (!checkPermission('view_suppliers')) {
    header("location: access_denied.php");
    exit;
}
// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize supplier object
$supplier = new Supplier($db);

// Pagination variables
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$records_per_page = 10;
$from_record_num = ($records_per_page * $page) - $records_per_page;

// Query suppliers
$stmt = $supplier->readAll($from_record_num, $records_per_page);
$num = $stmt->rowCount();

// Get total count for pagination
$total_rows = $supplier->countAll();
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
                <h1 class="h2">Suppliers</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <?php if (checkPermission('add_supplier')): ?>
                    <a href="supplier_create.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add New Supplier
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Search Form -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <form action="suppliers.php" method="GET" class="d-flex">
                        <input type="text" name="search" class="form-control me-2" placeholder="Search suppliers..." autocomplete="off">
                        <button class="btn btn-outline-success" type="submit">Search</button>
                    </form>
                </div>
            </div>
            
            <!-- Suppliers Table -->
            <?php if ($num > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td><?php echo $row['supplier_id']; ?></td>
                            <td><?php echo $row['name']; ?></td>
                            <td><?php echo $row['contact_person']; ?></td>
                            <td><?php echo $row['email']; ?></td>
                            <td><?php echo $row['phone']; ?></td>
                            <td>
                                <?php echo $row['is_active'] ? 
                                    '<span class="badge bg-success">Active</span>' : 
                                    '<span class="badge bg-secondary">Inactive</span>'; ?>
                            </td>
                            <td>
                                <?php if (checkPermission('view_supplier_details')): ?>
                                <a href="supplier_read.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('edit_supplier')): ?>
                                <a href="supplier_update.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (checkPermission('delete_supplier')): ?>
                                <a href="supplier_delete.php?id=<?php echo $row['supplier_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
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
                            "<li class='page-item'><a class='page-link' href='suppliers.php?page={$i}'>{$i}</a></li>";
                    }
                    ?>
                </ul>
            </nav>
            
            <?php else: ?>
            <div class="alert alert-info">No suppliers found.</div>
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