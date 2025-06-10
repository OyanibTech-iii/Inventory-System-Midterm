<?php
include 'models/authentication.php';
require_once "helpers/permissions.php";
require_once "config/database.php";
require_once "config/connect.php";

// Check if user has permission to view products
if (!checkPermission('view_products')) {
    header("Location: error.php?message=You don't have permission to view product details");
    exit;
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Get product ID from URL parameter
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Function to get product details
function getProductDetails($db, $product_id) {
    $query = "SELECT p.*, s.current_quantity, c.name as category_name, b.name as brand_name
              FROM products p
              LEFT JOIN stock s ON p.product_id = s.product_id
              LEFT JOIN categories c ON p.category_id = c.category_id
              LEFT JOIN brands b ON p.brand_id = b.brand_id
              WHERE p.product_id = :product_id AND p.is_active = 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Function to get product images
function getProductImages($db, $product_id) {
    $query = "SELECT * FROM product_images 
              WHERE product_id = :product_id 
              ORDER BY is_primary DESC, image_id ASC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get product variations (sizes, colors, etc.)
function getProductVariations($db, $product_id) {
    $query = "SELECT v.*, a.name as attribute_name, a.type as attribute_type
              FROM product_variations v
              INNER JOIN product_attributes a ON v.attribute_id = a.attribute_id
              WHERE v.product_id = :product_id
              ORDER BY a.name, v.value";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get related products
function getRelatedProducts($db, $product_id, $category_id, $limit = 4) {
    $query = "SELECT p.product_id, p.name, p.price, p.sku, 
                     (SELECT image_path FROM product_images 
                      WHERE product_id = p.product_id AND is_primary = 1 
                      LIMIT 1) as image_path
              FROM products p
              WHERE p.category_id = :category_id 
                AND p.product_id != :product_id
                AND p.is_active = 1
              ORDER BY RAND()
              LIMIT :limit";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get product data
if ($product_id > 0) {
    $product = getProductDetails($db, $product_id);
    
    if ($product) {
        $productImages = getProductImages($db, $product_id);
        $productVariations = getProductVariations($db, $product_id);
        $relatedProducts = getRelatedProducts($db, $product_id, $product['category_id']);
    } else {
        // Product not found, redirect to products page
        header("Location: product.php?error=Product not found");
        exit;
    }
} else {
    // No product ID provided, redirect to products page
    header("Location: product.php");
    exit;
}

// Include header
include "views/header.php";
?>

<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Product Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <a href="product.php" class="btn btn-sm btn-outline-secondary">
                            <i data-feather="arrow-left"></i> Back to Products
                        </a>
                        <?php if (checkPermission('edit_products')): ?>
                        <a href="edit_product.php?id=<?php echo $product_id; ?>" class="btn btn-sm btn-outline-primary">
                            <i data-feather="edit"></i> Edit
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <!-- Product Images Section -->
                <div class="col-md-5">
                    <div class="card shadow">
                        <div class="card-body p-0">
                            <?php if (!empty($productImages)): ?>
                                <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                                    <div class="carousel-inner">
                                        <?php 
                                        $isFirst = true;
                                        foreach ($productImages as $image): 
                                        ?>
                                            <div class="carousel-item <?php echo $isFirst ? 'active' : ''; ?>">
                                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                     class="d-block w-100" 
                                                     alt="<?php echo htmlspecialchars($product['name']); ?>">
                                            </div>
                                        <?php 
                                            $isFirst = false;
                                        endforeach; 
                                        ?>
                                    </div>
                                    <?php if (count($productImages) > 1): ?>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (count($productImages) > 1): ?>
                                <div class="row mt-3 px-2">
                                    <?php foreach ($productImages as $index => $image): ?>
                                        <div class="col-3 mb-2">
                                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                 class="img-thumbnail cursor-pointer" 
                                                 onclick="$('#productCarousel').carousel(<?php echo $index; ?>)">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="text-center p-5">
                                    <i data-feather="image" style="width: 100px; height: 100px;"></i>
                                    <p class="mt-3">No images available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Product Details Section -->
                <div class="col-md-7">
                    <div class="card shadow h-100">
                        <div class="card-body">
                            <h3 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="d-flex mb-3">
                                <span class="badge bg-primary me-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <span class="badge bg-secondary me-2"><?php echo htmlspecialchars($product['brand_name']); ?></span>
                                <span class="badge bg-info me-2">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                            </div>
                            
                            <div class="mb-3">
                                <h4 class="text-primary">$<?php echo number_format($product['price'], 2); ?></h4>
                            </div>
                            
                            <div class="mb-3">
                                <p><?php echo htmlspecialchars($product['description']); ?></p>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Current Stock</h6>
                                            <h4 class="<?php echo ($product['current_quantity'] <= $product['minimum_stock_level']) ? 'text-danger' : 'text-success'; ?>">
                                                <?php echo $product['current_quantity']; ?>
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Min. Stock</h6>
                                            <h4><?php echo $product['minimum_stock_level']; ?></h4>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card bg-light">
                                        <div class="card-body text-center">
                                            <h6>Status</h6>
                                            <h4>
                                                <span class="badge <?php echo $product['current_quantity'] > 0 ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $product['current_quantity'] > 0 ? 'In Stock' : 'Out of Stock'; ?>
                                                </span>
                                            </h4>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (!empty($productVariations)): ?>
                                <div class="mb-3">
                                    <h5>Product Variations</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Attribute</th>
                                                    <th>Value</th>
                                                    <th>Additional Price</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($productVariations as $variation): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($variation['attribute_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($variation['value']); ?></td>
                                                        <td>
                                                            <?php if ($variation['additional_price'] > 0): ?>
                                                                +$<?php echo number_format($variation['additional_price'], 2); ?>
                                                            <?php else: ?>
                                                                -
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (checkPermission('manage_stock')): ?>
                                <div class="mt-4">
                                    <a href="stock_adjustment.php?product_id=<?php echo $product_id; ?>" class="btn btn-success">
                                        <i data-feather="plus-square"></i> Adjust Stock
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($relatedProducts)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="mb-3">Related Products</h4>
                    <div class="row">
                        <?php foreach ($relatedProducts as $related): ?>
                            <div class="col-md-3 mb-3">
                                <div class="card h-100 shadow-sm">
                                    <img src="<?php echo !empty($related['image_path']) ? htmlspecialchars($related['image_path']) : 'assets/images/no-image.jpg'; ?>" 
                                         class="card-img-top" alt="<?php echo htmlspecialchars($related['name']); ?>"
                                         style="height: 200px; object-fit: contain;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($related['name']); ?></h5>
                                        <p class="card-text">$<?php echo number_format($related['price'], 2); ?></p>
                                        <a href="product_details.php?id=<?php echo $related['product_id']; ?>" class="btn btn-sm btn-outline-primary">View Details</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Add padding at the bottom to ensure content doesn't get hidden by footer -->
            <div class="mb-5 pb-4"></div>
        </main>
    </div>
</div>

<!-- Modified footer markup to align with the main content area -->
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
<!-- jQuery (needed for carousel functionality) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>
<!-- Custom scripts -->
<script src="assets/js/scripts.js"></script>
</body>
</html>