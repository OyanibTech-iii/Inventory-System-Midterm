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
require_once "models/Report.php";
require_once "helpers/permissions.php";

// Check permissions
if (!checkPermission('view_reports')) {
    header("location: access_denied.php");
    exit;
}
$database = new Database();
$db = $database->getConnection();

// Initialize report object
$report = new Report($db);

// Set default date range to current month if not provided
$current_month_start = date('Y-m-01');
$current_month_end = date('Y-m-t');

$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : $current_month_start;
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : $current_month_end;

// Set the date range for reporting
$report->date_from = $date_from;
$report->date_to = $date_to;

// Get report data
$sales_summary = $report->getSalesSummary();
$top_products = $report->getTopSellingProducts(5);
$sales_by_category = $report->getSalesByCategory();
$sales_trend = $report->getSalesTrend();
$low_stock_items = $report->getLowStockItems();

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
                <h1 class="h2">Reports</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print();">
                            <i data-feather="printer"></i> Print
                        </button>
                        <?php if (checkPermission('export_reports')): ?>
                            <a href="report_export.php?type=pdf&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>"
                                class="btn btn-sm btn-outline-secondary">
                                <i data-feather="file"></i> Export PDF
                            </a>
                            <a href="report_export.php?type=excel&from=<?php echo $date_from; ?>&to=<?php echo $date_to; ?>"
                                class="btn btn-sm btn-outline-secondary">
                                <i data-feather="file-text"></i> Export Excel
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Date Range Selector -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <form action="reports.php" method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="date_from" class="form-label">From Date</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control"
                                        value="<?php echo $date_from; ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="date_to" class="form-label">To Date</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control"
                                        value="<?php echo $date_to; ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary">Generate Report</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Sales</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($sales_summary['total_sales'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-cash-stack fa-2x text-gray-300"></i>
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
                                        Total Orders</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $sales_summary['total_orders']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-cart-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Average Order Value</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($sales_summary['avg_order_value'], 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-bar-chart fa-2x text-gray-300"></i>
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
                                        Items Sold</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $sales_summary['total_items']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-box-seam fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart and Tables Row -->
            <div class="row">
                <!-- Sales Trend Chart -->
                <div class="col-md-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Sales Trend</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="salesTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Selling Products -->
                <div class="col-md-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Selling Products</h6>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($top_products)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($top_products as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo $product['name']; ?>
                                            <span class="badge bg-primary rounded-pill"><?php echo $product['quantity']; ?>
                                                sold</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-center text-muted">No product sales data available for this period.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales by Category and Low Stock Items Row -->
            <div class="row">
                <!-- Sales by Category -->
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Sales by Category</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie">
                                <canvas id="categoryChart"></canvas>
                            </div>
                            <div class="mt-4">
                                <?php if (!empty($sales_by_category)): ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Sales Amount</th>
                                                <th>Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $total = array_sum(array_column($sales_by_category, 'amount'));
                                            foreach ($sales_by_category as $category):
                                                $percentage = ($total > 0) ? ($category['amount'] / $total) * 100 : 0;
                                                ?>
                                                <tr>
                                                    <td><?php echo $category['name']; ?></td>
                                                    <td>$<?php echo number_format($category['amount'], 2); ?></td>
                                                    <td><?php echo number_format($percentage, 1); ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-center text-muted">No category sales data available for this period.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Items -->
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Low Stock Items</h6>
                            <?php if (checkPermission('inventory_management')): ?>
                                <a href="inventory.php" class="btn btn-sm btn-primary">Manage Inventory</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($low_stock_items)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Current Stock</th>
                                                <th>Reorder Level</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($low_stock_items as $item): ?>
                                                <tr>
                                                    <td><?php echo $item['name']; ?></td>
                                                    <td><?php echo $item['current_stock']; ?></td>
                                                    <td><?php echo $item['reorder_level']; ?></td>
                                                    <td>
                                                        <?php if ($item['current_stock'] == 0): ?>
                                                            <span class="badge bg-danger">Out of Stock</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning text-dark">Low Stock</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted">No low stock items at this time.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sales Trend Chart
        var salesTrendCtx = document.getElementById('salesTrendChart').getContext('2d');
        var salesTrendData = <?php echo json_encode($sales_trend); ?>;

        var salesTrendChart = new Chart(salesTrendCtx, {
            type: 'line',
            data: {
                labels: salesTrendData.map(item => item.date),
                datasets: [{
                    label: 'Sales Amount',
                    data: salesTrendData.map(item => item.amount),
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHitRadius: 10,
                    pointBorderWidth: 2,
                    lineTension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        }
                    },
                    y: {
                        ticks: {
                            maxTicksLimit: 5,
                            padding: 10,
                            callback: function (value) {
                                return '$' + value;
                            }
                        },
                        grid: {
                            color: "rgb(234, 236, 244)",
                            zeroLineColor: "rgb(234, 236, 244)",
                            drawBorder: false,
                            borderDash: [2],
                            zeroLineBorderDash: [2]
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return '$' + context.raw.toFixed(2);
                            }
                        }
                    }
                }
            }
        });

        // Category Sales Chart
        var categoryData = <?php echo json_encode($sales_by_category); ?>;
        if (categoryData.length > 0) {
            var categoryCtx = document.getElementById('categoryChart').getContext('2d');
            var categoryChart = new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryData.map(item => item.name),
                    datasets: [{
                        data: categoryData.map(item => item.amount),
                        backgroundColor: [
                            '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b',
                            '#5a5c69', '#858796', '#f8f9fc', '#d1d3e2', '#b7b9cc'
                        ],
                        hoverBackgroundColor: [
                            '#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#be2617',
                            '#484a54', '#717384', '#e3e6f0', '#b2b5c7', '#9597a9'
                        ],
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return context.label + ': $' + context.raw.toFixed(2);
                                }
                            }
                        }
                    },
                    cutout: '70%'
                }
            });
        }
    });
    
    </script>

<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });
</script>
<?php include "views/footer.php"; ?>