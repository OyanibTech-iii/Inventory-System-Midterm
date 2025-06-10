<?php
// helpers/dashboard_helper.php

/**
 * Get a list of products with low stock
 * 
 * @param int $limit
 * @return array
 */
function getLowStockProducts($limit = 5) {
    global $conn;
    
    $query = "SELECT p.product_id, p.name, p.sku, s.current_quantity as stock_level, p.minimum_stock_level
              FROM products p
              JOIN stock s ON p.product_id = s.product_id
              WHERE s.current_quantity <= p.minimum_stock_level
              ORDER BY s.current_quantity ASC
              LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get recent sales
 * 
 * @param int $limit
 * @return array
 */
function getRecentSales($limit = 5) {
    global $conn;
    
    $query = "SELECT s.sale_id, s.invoice_number, s.sale_date, c.name as customer_name, s.total_amount
              FROM sales s
              LEFT JOIN customers c ON s.customer_id = c.customer_id
              ORDER BY s.sale_date DESC
              LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get top selling products
 * 
 * @param int $limit
 * @return array
 */
function getTopSellingProducts($limit = 5) {
    global $conn;
    
    $query = "SELECT p.product_id, p.name, SUM(si.quantity_sold) as quantity_sold
              FROM sale_items si
              JOIN products p ON si.product_id = p.product_id
              GROUP BY p.product_id
              ORDER BY quantity_sold DESC
              LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get monthly sales data
 * 
 * @param int $months_count
 * @return array
 */
function getMonthlySales($months_count = 6) {
    global $conn;
    
    $query = "SELECT 
                DATE_FORMAT(sale_date, '%b %Y') as month,
                SUM(total_amount) as total
              FROM sales
              WHERE sale_date >= DATE_SUB(CURRENT_DATE, INTERVAL :months_count MONTH)
              GROUP BY YEAR(sale_date), MONTH(sale_date)
              ORDER BY YEAR(sale_date), MONTH(sale_date)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':months_count', $months_count, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get total number of products
 * 
 * @return int
 */
function getTotalProducts() {
    global $conn;
    
    $query = "SELECT COUNT(*) as total FROM products";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row['total'];
}

/**
 * Get total sales amount for today
 * 
 * @return float
 */
function getTodaySales() {
    global $conn;
    
    $query = "SELECT SUM(total_amount) as total 
              FROM sales 
              WHERE DATE(sale_date) = CURRENT_DATE()";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row['total'] ? number_format($row['total'], 2) : "0.00";
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                </div>
            </div>
            
            <!-- Sales Overview -->
            <div class="row">
                <div class="col-md-3">
                    <div class="card text-white bg-primary mb-3">
                        <div class="card-header">Total Products</div>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo getTotalProducts(); ?></h5>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-white bg-success mb-3">
                        <div class="card-header">Today's Sales</div>
                        <div class="card-body">
                            <h5 class="card-title">$<?php echo getTodaySales(); ?></h5>
                        </div>
                    </div>
                </div>
                <!-- More stat cards -->
            </div>
            
            <!-- Charts and Reports -->
            <div class="row mt-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">Monthly Sales</div>
                        <div class="card-body">
                            <canvas id="salesChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">Low Stock Alerts</div>
                        <div class="card-body">
                            <ul class="list-group">
                                <?php foreach($low_stock_products as $product): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo $product['name']; ?>
                                    <span class="badge bg-danger rounded-pill"><?php echo $product['stock_level']; ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activities Section -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">Recent Sales</div>
                        <div class="card-body">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Date</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_sales as $sale): ?>
                                    <tr>
                                        <td><?php echo $sale['invoice_number']; ?></td>
                                        <td><?php echo $sale['sale_date']; ?></td>
                                        <td><?php echo $sale['customer_name']; ?></td>
                                        <td>$<?php echo $sale['total_amount']; ?></td>
                                        <td><span class="badge bg-success">Completed</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
// Initialize charts
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    var ctx = document.getElementById('salesChart').getContext('2d');
    var salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_sales, 'month')); ?>,
            datasets: [{
                label: 'Sales',
                data: <?php echo json_encode(array_column($monthly_sales, 'total')); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

<?php include "views/footer.php"; ?>