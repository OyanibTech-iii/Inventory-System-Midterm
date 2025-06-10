<?php
// process_purchase.php - Process product purchase and update inventory

// Include required files
require_once "config/database.php";
require_once "models/product.php";
require_once "models/authentication.php";
require_once "models/order.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if(!isLoggedIn()) {
    // Redirect to login page with return URL
    header("Location: login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty. Please add products to proceed with purchase.";
    header("Location: product.php");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Product object
$product = new Product($db);

// Initialize Order object
$order = new Order($db);

// Check if form was submitted
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate payment method
    if(empty($_POST['payment_method'])) {
        $_SESSION['error'] = "Please select a payment method.";
        header("Location: checkout.php");
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        // Store order details
        $total_amount = 0;
        $order_notes = isset($_POST['notes']) ? $_POST['notes'] : '';
        $payment_method = $_POST['payment_method'];
        
        // Calculate total
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += $item['subtotal'];
        }
        
        // Create main order record
        $order->user_id = $_SESSION['user_id'];
        $order->order_date = date('Y-m-d H:i:s');
        $order->total_amount = $total_amount;
        $order->payment_method = $payment_method;
        $order->notes = $order_notes;
        // Get customer_id if available
        $order->customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;
        
        // Create the order
        if ($order->create()) {
            $sale_id = $order->order_id; // This corresponds to sale_id in the database
            
            // Process each cart item
            foreach ($_SESSION['cart'] as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];
                $unit_price = $item['price'];
                $subtotal = $item['subtotal'];
                
                // Verify current stock level
                $currentStock = $product->getStockLevel($product_id);
                if ($currentStock === false) {
                    throw new Exception("Could not retrieve stock level for product.");
                }
                
                if ($quantity > $currentStock) {
                    throw new Exception("Not enough stock for product '{$item['name']}'. Only {$currentStock} units available.");
                }
                
                // Add order item 
                if (!$order->addOrderItem($sale_id, $product_id, $quantity, $unit_price, $subtotal)) {
                    // Get the specific error that occurred
                    $errorInfo = $db->errorInfo();
                    throw new Exception("Failed to add order item for product '{$item['name']}'. Database error: " . $errorInfo[2]);
                }
                
                // Update product stock
                $new_quantity = $currentStock - $quantity;
                $result = $product->updateStockLevel($product_id, $new_quantity);
                
                if ($result === false) {
                    $errorInfo = $db->errorInfo();
                    throw new Exception("Failed to update inventory for product '{$item['name']}'. Database error: " . $errorInfo[2]);
                }
            }
            
            // If everything went well, commit the transaction
            $db->commit();
            
            // Clear the cart
            $_SESSION['cart'] = [];
            
            // Set success message
            $_SESSION['success'] = "Your order has been placed successfully! Order #{$sale_id}";
            
            // Redirect to order confirmation
            header("Location: order_confirmation.php?id={$sale_id}");
            exit;
        } else {
            $errorInfo = $db->errorInfo();
            throw new Exception("Failed to create order: " . $errorInfo[2]);
        }
    } catch (PDOException $e) {
        // Roll back the transaction
        $db->rollBack();
        
        // Set error message with more specific information
        $_SESSION['error'] = "Database error processing your order: " . $e->getMessage();
        
        // Redirect back to checkout
        header("Location: checkout.php");
        exit;
    } catch (Exception $e) {
        // Roll back the transaction
        $db->rollBack();
        
        // Set error message
        $_SESSION['error'] = "Error processing your order: " . $e->getMessage();
        
        // Redirect back to checkout
        header("Location: checkout.php");
        exit;
    }
} else {
    // Not a POST request, redirect to cart
    header("Location: cart.php");
    exit;
}
?>