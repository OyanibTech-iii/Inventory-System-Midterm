<?php
require_once("models/authentication.php");
require_once "config/database.php";
require_once "config/connect.php";
require_once "helpers/permissions.php";
require_once "models/Stock.php";
require_once "models/Product.php";
require_once "models/Supplier.php";
require_once "models/User.php"; // Include User model for checking admin status

// Check if user is logged in
if (!isset($_SESSION["user_id"])) {
    header("location: login.php");
    exit;
}

// Check permissions
if (!checkPermission('add_stock')) {
    header("location: access_denied.php");
    exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize objects
$stock = new Stock($db);
$product = new Product($db);
$supplier = new Supplier($db);
$user = new User($db);

// Get current user info
$current_user_id = $_SESSION["user_id"];
$current_username = $_SESSION["username"] ?? "Unknown";
$is_admin = $user->isAdmin($current_user_id);

// Process form submission
$errors = [];
$success_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate inputs
    if (empty($_POST["product_id"])) {
        $errors[] = "Product is required";
    }
    
    if (empty($_POST["supplier_id"])) {
        $errors[] = "Supplier is required";
    }
    
    if (!isset($_POST["quantity_added"]) || !is_numeric($_POST["quantity_added"]) || $_POST["quantity_added"] <= 0) {
        $errors[] = "Quantity must be a positive number";
    }
    
    if (!isset($_POST["unit_cost"]) || !is_numeric($_POST["unit_cost"]) || $_POST["unit_cost"] < 0) {
        $errors[] = "Unit cost must be a valid number";
    }
    
    if (empty($_POST["location_code"])) {
        $errors[] = "Location is required";
    }
    
    // Process if no errors
    if (empty($errors)) {
        $stock->product_id = $_POST["product_id"];
        $stock->supplier_id = $_POST["supplier_id"];
        $stock->quantity_added = $_POST["quantity_added"];
        $stock->current_quantity = $_POST["quantity_added"]; // Initial current quantity equals quantity added
        $stock->unit_cost = $_POST["unit_cost"];
        $stock->batch_number = $_POST["batch_number"];
        $stock->date_added = date('Y-m-d H:i:s');
        $stock->location_code = $_POST["location_code"];
        $stock->added_by = $current_username;
        $stock->is_admin = $is_admin ? 1 : 0;
        
        // Handle expiry date
        if (!empty($_POST["expiry_date"])) {
            $stock->expiry_date = $_POST["expiry_date"];
        } else {
            $stock->expiry_date = null;
        }
        
        // Add stock
        if ($stock->addStock()) {
            $success_message = "Stock added successfully!";
            // Reset form
            unset($_POST);
        } else {
            $errors[] = "Unable to add stock. Please try again.";
        }
    }
}

// Get all products for dropdown
$product_stmt = $db->prepare("SELECT product_id, name, sku FROM products WHERE is_active = 1 ORDER BY name");
$product_stmt->execute();
$products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all suppliers for dropdown
$supplier_stmt = $db->prepare("SELECT supplier_id, name FROM suppliers WHERE is_active = 1 ORDER BY name");
$supplier_stmt->execute();
$suppliers = $supplier_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all existing locations for dropdown
$locations = $stock->getLocations();

// Include header
include "views/header.php";
?>
<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Add New Stock</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="stock.php" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i> Back to Stock
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="needs-validation" novalidate>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="product_id" class="form-label">Product*</label>
                        <select name="product_id" id="product_id" class="form-select" required>
                            <option value="">Select Product</option>
                            <?php foreach ($products as $product_item): ?>
                                <option value="<?php echo $product_item['product_id']; ?>" <?php echo isset($_POST['product_id']) && $_POST['product_id'] == $product_item['product_id'] ? 'selected' : ''; ?>>
                                    <?php echo $product_item['name'] . ' (' . $product_item['sku'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a product.
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="supplier_id" class="form-label">Supplier*</label>
                        <select name="supplier_id" id="supplier_id" class="form-select" required>
                            <option value="">Select Supplier</option>
                            <?php foreach ($suppliers as $supplier_item): ?>
                                <option value="<?php echo $supplier_item['supplier_id']; ?>" <?php echo isset($_POST['supplier_id']) && $_POST['supplier_id'] == $supplier_item['supplier_id'] ? 'selected' : ''; ?>>
                                    <?php echo $supplier_item['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">
                            Please select a supplier.
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="quantity_added" class="form-label">Quantity*</label>
                        <input type="number" class="form-control" id="quantity_added" name="quantity_added" min="1" value="<?php echo isset($_POST['quantity_added']) ? $_POST['quantity_added'] : ''; ?>" required>
                        <div class="invalid-feedback">
                            Please enter a valid quantity.
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="unit_cost" class="form-label">Unit Cost*</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" id="unit_cost" name="unit_cost" min="0" step="0.01" value="<?php echo isset($_POST['unit_cost']) ? $_POST['unit_cost'] : ''; ?>" required>
                            <div class="invalid-feedback">
                                Please enter a valid unit cost.
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="batch_number" class="form-label">Batch Number</label>
                        <input type="text" class="form-control" id="batch_number" name="batch_number" value="<?php echo isset($_POST['batch_number']) ? $_POST['batch_number'] : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="location_code" class="form-label">Location*</label>
                        <div class="input-group">
                            <select name="location_code" id="location_code" class="form-select" required>
                                <option value="">Select Location</option>
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_code']; ?>" <?php echo isset($_POST['location_code']) && $_POST['location_code'] == $location['location_code'] ? 'selected' : ''; ?>>
                                        <?php echo $location['location_code']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#newLocationModal">New</button>
                        </div>
                        <div class="invalid-feedback">
                            Please select or enter a location.
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <label for="expiry_date" class="form-label">Expiry Date</label>
                        <input type="date" class="form-control" id="expiry_date" name="expiry_date" value="<?php echo isset($_POST['expiry_date']) ? $_POST['expiry_date'] : ''; ?>">
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="alert alert-info">
                            You are adding stock as: 
                            <?php if($is_admin): ?>
                            <span class="text-danger fw-bold"><?php echo htmlspecialchars($current_username); ?></span> (Administrator)
                            <?php else: ?>
                            <span><?php echo htmlspecialchars($current_username); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Add Stock</button>
                        <a href="stock.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<!-- New Location Modal -->
<div class="modal fade" id="newLocationModal" tabindex="-1" aria-labelledby="newLocationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="newLocationModalLabel">Add New Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="new_location_code" class="form-label">Location Code*</label>
                    <input type="text" class="form-control" id="new_location_code" placeholder="e.g. WAREHOUSE-A">
                    <div class="form-text">Enter a unique location code (e.g. WAREHOUSE-A, SHELF-B12)</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="addLocationBtn">Add Location</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Form validation
    (function() {
        'use strict';
        
        // Fetch all forms we want to apply custom validation styles to
        var forms = document.querySelectorAll('.needs-validation');
        
        // Loop over them and prevent submission
        Array.prototype.slice.call(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                form.classList.add('was-validated');
            }, false);
        });
    })();
    
    // Handle new location addition
    document.getElementById('addLocationBtn').addEventListener('click', function() {
        const newLocationCode = document.getElementById('new_location_code').value.trim();
        
        if (newLocationCode) {
            // Create new option
            const newOption = new Option(newLocationCode, newLocationCode, true, true);
            
            // Add to select box
            document.getElementById('location_code').add(newOption);
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('newLocationModal'));
            modal.hide();
        } else {
            alert('Please enter a valid location code');
        }
    });
</script>

<?php include "views/footer.php"; ?>