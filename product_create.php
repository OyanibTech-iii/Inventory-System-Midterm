<?php
// Include authentication model
include 'models/authentication.php';

// Include required files
require_once "config/database.php";
require_once "config/connect.php";
require_once "models/product.php";
require_once "helpers/permissions.php";
include "views/header.php";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Product object
$product = new Product($db);

// Process form submission
if($_POST) {
    // Set product property values
    $product->name = $_POST['name'];
    $product->sku = $_POST['sku'];
    $product->category = $_POST['category'];
    $product->price = $_POST['price'];
    $product->description = $_POST['description'];
    $product->minimum_stock_level = $_POST['minimum_stock_level'];
    $product->is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Extract stock-related values
    $stock_level = isset($_POST['stock_level']) ? (int)$_POST['stock_level'] : 0;
    $supplier_id = isset($_POST['supplier_id']) ? $_POST['supplier_id'] : null;
    $unit_cost = isset($_POST['unit_cost']) ? $_POST['unit_cost'] : null;
    $batch_number = isset($_POST['batch_number']) ? $_POST['batch_number'] : null;
    $location_code = isset($_POST['location_code']) ? $_POST['location_code'] : null;
    
    // Create the product
    if($product->create()) {
        // If stock information was provided, add it
        if ($stock_level > 0 && $supplier_id) {
            // Get the newly created product's ID - using PDO's lastInsertId() instead of a custom method
            $product_id = $db->lastInsertId();
            
            // Initialize stock object
            require_once "models/Stock.php";
            $stock = new Stock($db);
            
            // Set stock properties
            $stock->product_id = $product_id;
            $stock->supplier_id = $supplier_id;
            $stock->quantity_added = $stock_level;
            $stock->current_quantity = $stock_level;
            $stock->unit_cost = $unit_cost;
            $stock->batch_number = $batch_number;
            $stock->date_added = date('Y-m-d H:i:s');
            $stock->location_code = $location_code;
            
            // Add stock record
            $stock->addStock();
        }
        
        $success_message = "Product was created successfully.";
    } else {
        $error_message = "Unable to create product.";
    }
}

// Get all categories for dropdown
$categories = $product->getCategories();

// Get all suppliers for dropdown
require_once "models/supplier.php";
$supplier = new Supplier($db);
$stmt = $supplier->readAll(0, 1000); // Assuming we want to fetch all suppliers
$suppliers = array();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $suppliers[] = $row;
}
?>

<link rel="stylesheet" href="sidebarhover.css">
<link rel="stylesheet" href="assets/css/custom.css">

<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2"><i data-feather="package" class="feather-icon-header me-2"></i> Add New Product</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="product.php" class="btn btn-sm btn-outline-primary">
                            <i data-feather="arrow-left" class="me-1"></i> Back to Products
                        </a>
                    </div>
                </div>
            </div>
            
            <?php if(isset($success_message)): ?>
                <div class="alert alert-success d-flex align-items-center fade show" role="alert">
                    <i data-feather="check-circle" class="me-2"></i>
                    <div>
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_message)): ?>
                <div class="alert alert-danger d-flex align-items-center fade show" role="alert">
                    <i data-feather="alert-circle" class="me-2"></i>
                    <div>
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i data-feather="plus-circle" class="me-2"></i>
                    <h5 class="mb-0">Product Information</h5>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="productForm" class="needs-validation" novalidate>
                        <!-- Product Information Section -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating mb-3">
                                    <input type="text" class="form-control" id="name" name="name" placeholder="Product Name" required>
                                    <label for="name"><i data-feather="tag" class="feather-sm me-1"></i> Product Name</label>
                                    <div class="invalid-feedback">Please provide a product name.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating mb-2">
                                    <input type="text" class="form-control" id="sku" name="sku" placeholder="SKU" required>
                                    <label for="sku"><i data-feather="hash" class="feather-sm me-1"></i> SKU</label>
                                    <div class="invalid-feedback">Please provide a SKU.</div>
                                </div>
                                <div class="mt-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateSKU()">
                                        <i data-feather="refresh-cw" class="feather-sm me-1"></i> Generate SKU
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <select class="form-select" id="category" name="category" required>
                                        <option value="">Select a category</option>
                                        <?php foreach($categories as $category): ?>
                                            <option value="<?php echo $category['category']; ?>"><?php echo $category['category']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="category"><i data-feather="layers" class="feather-sm me-1"></i> Category</label>
                                    <div class="invalid-feedback">Please select a category.</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="Price" required>
                                    <label for="price"><i data-feather="dollar-sign" class="feather-sm me-1"></i> Price ($)</label>
                                    <div class="invalid-feedback">Please provide a valid price.</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <div class="form-floating">
                                <textarea class="form-control" id="description" name="description" rows="4" style="height: 100px" placeholder="Description"></textarea>
                                <label for="description"><i data-feather="align-left" class="feather-sm me-1"></i> Description</label>
                            </div>
                        </div>
                        
                        <div class="card bg-light mb-4">
                            <div class="card-header bg-light d-flex align-items-center">
                                <i data-feather="box" class="text-primary me-2"></i>
                                <h5 class="mb-0 text-primary">Initial Stock Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="stock_level" name="stock_level" min="0" value="0" placeholder="Initial Stock">
                                            <label for="stock_level"><i data-feather="package" class="feather-sm me-1"></i> Initial Stock Quantity</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="minimum_stock_level" name="minimum_stock_level" min="0" value="10" required placeholder="Minimum Stock">
                                            <label for="minimum_stock_level"><i data-feather="alert-triangle" class="feather-sm me-1"></i> Minimum Stock Level</label>
                                            <div class="invalid-feedback">Please provide a minimum stock level.</div>
                                        </div>
                                        <small class="text-muted"><i data-feather="info" class="feather-xs me-1"></i> System will alert when stock falls below this level</small>
                                    </div>
                                </div>
                                
                                <div class="row mb-3" id="supplier-info-row">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <select class="form-select" id="supplier_id" name="supplier_id">
                                                <option value="">Select a supplier</option>
                                                <?php foreach($suppliers as $sup): ?>
                                                    <option value="<?php echo $sup['supplier_id']; ?>"><?php echo $sup['name']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label for="supplier_id"><i data-feather="truck" class="feather-sm me-1"></i> Supplier</label>
                                            <div class="invalid-feedback">Please select a supplier when adding initial stock.</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="number" class="form-control" id="unit_cost" name="unit_cost" step="0.01" min="0" value="0" placeholder="Unit Cost">
                                            <label for="unit_cost"><i data-feather="shopping-bag" class="feather-sm me-1"></i> Unit Cost ($)</label>
                                            <div class="invalid-feedback">Please provide a unit cost when adding initial stock.</div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="batch_number" name="batch_number" placeholder="Batch Number">
                                            <label for="batch_number"><i data-feather="grid" class="feather-sm me-1"></i> Batch Number</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="location_code" name="location_code" placeholder="Storage Location" placeholder="e.g., WHSE-A1-S3">
                                            <label for="location_code"><i data-feather="map-pin" class="feather-sm me-1"></i> Storage Location</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-check form-switch mb-4">
                            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" checked>
                            <label for="is_active"> Set product as active</label>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <button type="reset" class="btn btn-outline-secondary me-md-2">
                                <i data-feather="refresh-cw" class="me-1"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i data-feather="save" class="me-1"></i> Create Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Add padding at the bottom -->
            <div class="mb-5 pb-4"></div>
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
                    <span class="text-light"><i data-feather="database" class="feather-sm me-2"></i> Inventory Management System &copy; <?php echo date('Y'); ?></span>
                    <span class="text-light"><i data-feather="info" class="feather-sm me-2"></i> Version 1.0</span>
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
        feather.replace({ 'class': 'feather-sm' });
        
        // Form validation
        const form = document.getElementById('productForm');
        form.addEventListener('submit', function(event) {
            const stockLevel = parseInt(document.getElementById('stock_level').value || 0);
            const supplierId = document.getElementById('supplier_id').value;
            
            if (stockLevel > 0 && !supplierId) {
                event.preventDefault();
                document.getElementById('supplier_id').classList.add('is-invalid');
                return;
            }
            
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
        
        // Show/hide supplier fields based on stock level
        document.getElementById('stock_level').addEventListener('input', function() {
            const supplierInfoRow = document.getElementById('supplier-info-row');
            const stockLevel = parseInt(this.value) || 0;
            const supplierId = document.getElementById('supplier_id');
            const unitCost = document.getElementById('unit_cost');
            
            if (stockLevel > 0) {
                supplierInfoRow.style.opacity = 1;
                supplierId.setAttribute('required', 'required');
                unitCost.setAttribute('required', 'required');
            } else {
                supplierInfoRow.style.opacity = 0.6;
                supplierId.removeAttribute('required');
                unitCost.removeAttribute('required');
            }
        });
        
        // Initialize supplier fields opacity based on initial stock level
        const initialStockLevel = parseInt(document.getElementById('stock_level').value) || 0;
        document.getElementById('supplier-info-row').style.opacity = initialStockLevel > 0 ? 1 : 0.6;
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
    
    // Generate random SKU
    function generateSKU() {
        const category = document.getElementById('category').value;
        const prefix = category ? category.substring(0, 3).toUpperCase() : 'PRD';
        const random = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
        document.getElementById('sku').value = prefix + '-' + random;
    }
</script>
<!-- Custom CSS for Feather icons -->
<style>
    .feather-icon-header {
        width: 24px;
        height: 24px;
    }
    .feather-sm {
        width: 16px;
        height: 16px;
    }
    .feather-xs {
        width: 14px;
        height: 14px;
    }
    .form-floating > label {
        display: flex;
        align-items: center;
    }
</style>
<!-- Custom scripts -->
<script src="assets/js/scripts.js"></script>
</body>
</html>