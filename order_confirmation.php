<?php
include 'models/authentication.php';
// Include required files
require_once "config/database.php";
require_once "models/order.php";
require_once "helpers/permissions.php";
include "views/header.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to products page if no order ID
    header("Location: product.php");
    exit;
}

$order_id = $_GET['id'];

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Order object
$order = new Order($db);
$order->order_id = $order_id;

// Get order details
$order_details = $order->readOne();

// If order not found, redirect
if (!$order_details) {
    $_SESSION['error'] = "Order not found.";
    header("Location: product.php");
    exit;
}

// Get order items
$order_items = $order->getOrderItems($order_id);
?>

<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Order Confirmation</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="product.php" class="btn btn-sm btn-outline-primary">
                            <i data-feather="shopping-bag"></i> Continue Shopping
                        </a>
                        <a href="orders.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="list"></i> My Orders
                        </a>
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
            
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i data-feather="check-circle"></i> Thank You for Your Order!</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                            <p><strong>Date:</strong> <?php echo isset($order_details['order_date']) ? date('F d, Y h:i A', strtotime($order_details['order_date'])) : 'N/A'; ?></p>
                            <p><strong>Payment Method:</strong> <?php echo isset($order_details['payment_method']) ? $order_details['payment_method'] : 'N/A'; ?></p>
                            <p><strong>Total Amount:</strong> $<?php echo number_format($order_details['total_amount'] ?? 0, 2); ?></p>
                            <?php if (isset($order_details['notes']) && !empty($order_details['notes'])): ?>
                            <p><strong>Notes:</strong> <?php echo $order_details['notes']; ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h5>Order Details</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_quantity = 0;
                                foreach ($order_items as $item): 
                                    $quantity = intval($item['quantity'] ?? 0);
                                    $total_quantity += $quantity;
                                ?>
                                <tr>
                                    <td><?php echo $item['product_name'] ?? 'Unknown Product'; ?></td>
                                    <td>$<?php echo number_format($item['unit_price'] ?? 0, 2); ?></td>
                                    <td><?php echo $quantity; ?></td>
                                    <td>$<?php echo number_format(($item['unit_price'] ?? 0) * $quantity, 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="2" class="text-end">Total Items:</th>
                                    <th><?php echo $total_quantity; ?></th>
                                    <th>$<?php echo number_format($order_details['total_amount'] ?? 0, 2); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <div class="text-center mt-4">
                        <p>A confirmation email has been sent to your registered email address.</p>
                        <a href="product.php" class="btn btn-primary">Continue Shopping</a>
                    </div>
                </div>
            </div>
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