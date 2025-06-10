<?php
include 'models/authentication.php';
require_once "helpers/permissions.php";
require_once "config/database.php";
require_once "config/connect.php";

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Function to get total products count
function getTotalProducts($db) {
    $query = "SELECT COUNT(*) as total FROM products WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Function to get total sales amount
function getTotalSales($db) {
    $query = "SELECT SUM(total_amount) as total FROM sales";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Function to get low stock items count
function getLowStockCount($db) {
    $query = "SELECT COUNT(*) as total FROM products p 
              INNER JOIN stock s ON p.product_id = s.product_id 
              WHERE s.current_quantity <= p.minimum_stock_level 
              AND s.current_quantity > 0 
              AND p.is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['total'] ?? 0;
}

// Function to get recent sales
function getRecentSales($db, $limit = 5) {
    $query = "SELECT s.sale_id, s.invoice_number, s.sale_date, s.total_amount, c.name as customer_name, u.username 
              FROM sales s 
              LEFT JOIN customers c ON s.customer_id = c.customer_id
              INNER JOIN users u ON s.user_id = u.user_id
              ORDER BY s.sale_date DESC 
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get low stock items
function getLowStockItems($db, $limit = 5) {
    $query = "SELECT p.product_id, p.name, p.sku, s.current_quantity, p.minimum_stock_level 
              FROM products p 
              INNER JOIN stock s ON p.product_id = s.product_id 
              WHERE s.current_quantity <= p.minimum_stock_level 
              AND p.is_active = 1 
              ORDER BY (p.minimum_stock_level - s.current_quantity) DESC 
              LIMIT :limit";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get data for dashboard
$totalProducts = getTotalProducts($db);
$totalSales = getTotalSales($db);
$lowStockCount = getLowStockCount($db);
$recentSales = getRecentSales($db);
$lowStockItems = getLowStockItems($db);

include "views/header.php";
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
*,body{
    font-family: 'Poppins', sans-serif;
}
</style>
<link rel="stylesheet" href="sidebarhover.css">
<div class="container-fluid">
    <div class="row">
        <?php include "views/sidebar.php"; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Products</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $totalProducts; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i data-feather="package" class="text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Total Sales</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        $<?php echo number_format($totalSales, 2); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i data-feather="dollar-sign" class="text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Low Stock Items</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $lowStockCount; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i data-feather="alert-triangle" class="text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Recent Sales</h6>
                            <div class="dropdown no-arrow">
                                <a href="#" class="dropdown-toggle" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-feather="more-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink">
                                    <li><a class="dropdown-item" href="sales.php">View All</a></li>
                                    <li><a class="dropdown-item" href="reports.php?type=sales">Export</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentSales)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Invoice #</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentSales as $sale): ?>
                                                <tr>
                                                    <td><a href="sale_details.php?id=<?php echo $sale['sale_id']; ?>"><?php echo $sale['invoice_number']; ?></a></td>
                                                    <td><?php echo date('M d, Y', strtotime($sale['sale_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer'); ?></td>
                                                    <td>$<?php echo number_format($sale['total_amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No recent sales to display.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Low Stock Alerts</h6>
                            <div class="dropdown no-arrow">
                                <a href="#" class="dropdown-toggle" role="button" id="dropdownMenuLink2" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-feather="more-vertical"></i>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuLink2">
                                    <li><a class="dropdown-item" href="inventory.php?stock_status=low">View All</a></li>
                                    <li><a class="dropdown-item" href="reports.php?type=low_stock">Export</a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($lowStockItems)): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>SKU</th>
                                                <th>Current Stock</th>
                                                <th>Minimum</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($lowStockItems as $item): ?>
                                                <tr>
                                                    <td><a href="product_details.php?id=<?php echo $item['product_id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a></td>
                                                    <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                                    <td class="<?php echo ($item['current_quantity'] == 0) ? 'text-danger' : 'text-warning'; ?> fw-bold">
                                                        <?php echo $item['current_quantity']; ?>
                                                    </td>
                                                    <td><?php echo $item['minimum_stock_level']; ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p>No low stock alerts to display.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Add padding at the bottom to ensure content doesn't get hidden by footer -->
            <div class="mb-5 pb-4"></div>
        </main>
    </div>
</div>

<!-- AI Assistant Button and Chat Container -->
<div class="ai-assistant-btn" id="ai-assistant-btn">
    <i data-feather="message-circle"></i>
</div>

<div class="ai-assistant-container" id="ai-assistant-container">
    <div class="ai-assistant-header">
        <div class="ai-assistant-title">
            <div class="ai-assistant-avatar">
                <i data-feather="cpu" size="18"></i>
            </div>
            <span>AI Assistant</span>
        </div>
        <button class="ai-close-btn" id="ai-close-btn">
            <i data-feather="x" size="20"></i>
        </button>
    </div>
    
    <div class="ai-chat-messages" id="ai-chat-messages">
        <div class="ai-message ai-message-bot">
            Hello! I'm your dashboard assistant. How can I help you today?
        </div>
        
        <div class="ai-typing-indicator" id="ai-typing-indicator">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    
    <div class="ai-suggestions">
        <div class="ai-suggestion">How to add products?</div>
        <div class="ai-suggestion">Run stock report</div>
        <div class="ai-suggestion">Low stock items</div>
    </div>
    
    <div class="ai-chat-input">
        <textarea class="ai-input-field" id="ai-input-field" placeholder="Type a message..." rows="1"></textarea>
        <button class="ai-send-btn" id="ai-send-btn">
            <i data-feather="send" size="18"></i>
        </button>
    </div>
</div>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<!-- Feather Icons -->
<script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
<script>
    // Initialize Feather icons
    document.addEventListener('DOMContentLoaded', function() {
        feather.replace();
    });

    document.addEventListener('DOMContentLoaded', function() {
            // Elements
            const assistantBtn = document.getElementById('ai-assistant-btn');
            const assistantContainer = document.getElementById('ai-assistant-container');
            const closeBtn = document.getElementById('ai-close-btn');
            const chatMessages = document.getElementById('ai-chat-messages');
            const inputField = document.getElementById('ai-input-field');
            const sendBtn = document.getElementById('ai-send-btn');
            const typingIndicator = document.getElementById('ai-typing-indicator');
            const suggestions = document.querySelectorAll('.ai-suggestion');
            
            // Toggle chat
            assistantBtn.addEventListener('click', function() {
                assistantContainer.classList.toggle('show');
                if (assistantContainer.classList.contains('show')) {
                    inputField.focus();
                }
            });
            
            closeBtn.addEventListener('click', function() {
                assistantContainer.classList.remove('show');
            });
            
            // Auto-resize textarea
            inputField.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            
            // Send message on Enter (without Shift)
            inputField.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });
            
            // Send button click
            sendBtn.addEventListener('click', sendMessage);
            
            // Suggestion clicks
            suggestions.forEach(suggestion => {
                suggestion.addEventListener('click', function() {
                    const text = this.textContent;
                    addMessage(text, 'user');
                    processUserMessage(text);
                });
            });
            
            function sendMessage() {
                const message = inputField.value.trim();
                if (!message) return;
                
                addMessage(message, 'user');
                inputField.value = '';
                inputField.style.height = 'auto';
                
                processUserMessage(message);
            }
            
            function addMessage(content, sender) {
                const messageDiv = document.createElement('div');
                messageDiv.classList.add('ai-message');
                messageDiv.classList.add(sender === 'user' ? 'ai-message-user' : 'ai-message-bot');
                messageDiv.textContent = content;
                
                // Add message before typing indicator
                chatMessages.insertBefore(messageDiv, typingIndicator);
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
            
            function processUserMessage(message) {
                // Show typing indicator
                typingIndicator.style.display = 'block';
                chatMessages.scrollTop = chatMessages.scrollHeight;
                
                // Disable input while "thinking"
                inputField.disabled = true;
                sendBtn.disabled = true;
                
                // Simulate AI response after delay
                setTimeout(() => {
                    // Generate response based on message
                    const response = generateResponse(message);
                    
                    // Hide typing indicator and add response
                    typingIndicator.style.display = 'none';
                    addMessage(response, 'bot');
                    
                    // Re-enable input
                    inputField.disabled = false;
                    sendBtn.disabled = false;
                    inputField.focus();
                }, 1500); // Simulate thinking time
            }
            
            function generateResponse(message) {
                // Simple response logic - can be replaced with API calls
                message = message.toLowerCase();
                
                if (message.includes('add product') || message.includes('how to add product')) {
                    return "To add a new product, go to 'Products' in the sidebar menu and click the 'Add New' button. Fill in the required fields and save.";
                }
                else if (message.includes('stock report') || message.includes('run report')) {
                    return "You can generate stock reports by going to 'Reports' and selecting 'Inventory Reports'. Choose the date range and click 'Generate'.";
                }
                else if (message.includes('low stock') || message.includes('out of stock')) {
                    return "Currently you have " + document.querySelector('.text-warning').textContent.trim() + " items with low stock. View all details in the Low Stock Alerts table.";
                }
                else if (message.includes('sales') || message.includes('revenue')) {
                    return "Your total sales are currently " + document.querySelector('.text-success').nextElementSibling.textContent.trim() + ". You can view detailed sales reports in the Reports section.";
                }
                else if (message.includes('hello') || message.includes('hi') || message.includes('hey')) {
                    return "Hello! How can I help you with your inventory management today?";
                }
                else if (message.includes('thank')) {
                    return "You're welcome! Is there anything else you need help with?";
                }
                else {
                    return "I'm not sure I understand. Would you like help with inventory, sales, or reports?";
                }
            }
        });
</script>
<!-- Custom scripts -->
<script src="assets/js/scripts.js"></script>
</body>
</html>