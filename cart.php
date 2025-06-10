<?php
include 'models/authentication.php';
// Include required files
require_once "config/database.php";
require_once "models/product.php";
require_once "helpers/permissions.php";
include "views/header.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Process quantity updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['quantity'] as $product_id => $quantity) {
        if ($quantity <= 0) {
            // Remove item if quantity is zero or negative
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['product_id'] == $product_id) {
                    unset($_SESSION['cart'][$key]);
                    break;
                }
            }
        } else {
            // Update quantity
            foreach ($_SESSION['cart'] as $key => $item) {
                if ($item['product_id'] == $product_id) {
                    // Initialize database and product to check stock level
                    $database = new Database();
                    $db = $database->getConnection();
                    $product = new Product($db);
                    $stock_level = $product->getStockLevel($product_id);
                    
                    // Make sure we don't exceed stock level
                    if ($quantity > $stock_level) {
                        $_SESSION['error'] = "Cannot update quantity for {$item['name']}. Maximum stock available is {$stock_level}.";
                        $quantity = $stock_level;
                    }
                    
                    $_SESSION['cart'][$key]['quantity'] = $quantity;
                    $_SESSION['cart'][$key]['subtotal'] = $quantity * $item['price'];
                    break;
                }
            }
        }
    }
    
    // Reindex the cart array after possible deletions
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    $_SESSION['success'] = "Cart updated successfully!";
    header("Location: cart.php");
    exit;
}

// Remove item from cart
if (isset($_GET['remove']) && !empty($_GET['remove'])) {
    $remove_id = $_GET['remove'];
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['product_id'] == $remove_id) {
            unset($_SESSION['cart'][$key]);
            break;
        }
    }
    // Reindex the cart array
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    $_SESSION['success'] = "Item removed from cart!";
    header("Location: cart.php");
    exit;
}

// Clear entire cart
if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    $_SESSION['success'] = "Cart has been cleared!";
    header("Location: cart.php");
    exit;
}

// Calculate cart totals
$total_items = 0;
$total_amount = 0;

foreach ($_SESSION['cart'] as $item) {
    $total_items += $item['quantity'];
    $total_amount += $item['subtotal'];
}
?>

<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Shopping Cart</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="product.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Continue Shopping
                        </a>
                        <?php if (!empty($_SESSION['cart'])): ?>
                        <a href="cart.php?clear=1" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to clear your cart?')">
                            <i data-feather="trash-2"></i> Clear Cart
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <?php if (empty($_SESSION['cart'])): ?>
            <div class="text-center my-5">
                <i data-feather="shopping-cart" style="width: 64px; height: 64px; color: #ccc;"></i>
                <h3 class="mt-3">Your cart is empty</h3>
                <p class="lead">Looks like you haven't added any products to your cart yet.</p>
                <a href="product.php" class="btn btn-primary mt-3">Browse Products</a>
            </div>
            <?php else: ?>
            <form action="cart.php" method="post">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($_SESSION['cart'] as $item): ?>
                            <tr>
                                <td><?php echo $item['name']; ?></td>
                                <td><?php echo $item['sku']; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <input type="number" name="quantity[<?php echo $item['product_id']; ?>]" 
                                           value="<?php echo $item['quantity']; ?>" 
                                           min="0" max="100" class="form-control form-control-sm" style="width: 80px;">
                                </td>
                                <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                <td>
                                    <a href="cart.php?remove=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-danger">
                                        <i data-feather="trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total:</th>
                                <th>$<?php echo number_format($total_amount, 2); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" name="update_cart" class="btn btn-secondary">
                        <i data-feather="refresh-cw"></i> Update Cart
                    </button>
                    <a href="checkout.php" class="btn btn-success">
                        <i data-feather="credit-card"></i> Proceed to Checkout
                    </a>
                </div>
            </form>
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