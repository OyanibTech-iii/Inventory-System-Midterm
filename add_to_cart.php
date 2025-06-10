<?php
// add_to_cart.php - Add product to shopping cart

// Include required files
require_once "config/database.php";
require_once "models/product.php";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Check if product ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $product_id = $_GET['id'];
    
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Initialize Product object
    $product = new Product($db);
    
    // Get product details
    $product->product_id = $product_id;
    if ($product->readOne()) {
        // Check if product is in stock
        if ($product->stock_level <= 0) {
            $_SESSION['error'] = "Sorry, {$product->name} is out of stock.";
            header("Location: product.php");
            exit;
        }
        
        // Check if product is already in cart
        $item_exists = false;
        foreach ($_SESSION['cart'] as $key => $item) {
            if ($item['product_id'] == $product_id) {
                // Product exists in cart, update quantity
                $new_quantity = $item['quantity'] + 1;
                
                // Make sure we don't exceed stock level
                if ($new_quantity > $product->stock_level) {
                    $_SESSION['error'] = "Cannot add more {$product->name}. Maximum stock available is {$product->stock_level}.";
                    header("Location: cart.php");
                    exit;
                }
                
                $_SESSION['cart'][$key]['quantity'] = $new_quantity;
                $_SESSION['cart'][$key]['subtotal'] = $new_quantity * $product->price;
                $item_exists = true;
                break;
            }
        }
        
        // If product doesn't exist in cart, add it
        if (!$item_exists) {
            $_SESSION['cart'][] = [
                'product_id' => $product->product_id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => 1,
                'subtotal' => $product->price,
                'stock_level' => $product->stock_level
            ];
        }
        
        $_SESSION['success'] = "{$product->name} added to cart!";
        
        // Redirect based on continue shopping flag
        if (isset($_GET['continue']) && $_GET['continue'] == '1') {
            header("Location: product.php");
        } else {
            header("Location: cart.php");
        }
        exit;
    } else {
        $_SESSION['error'] = "Product not found.";
        header("Location: product.php");
        exit;
    }
} else {
    $_SESSION['error'] = "No product selected.";
    header("Location: product.php");
    exit;
}
?>