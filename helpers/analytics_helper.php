<?php
// helpers/analytics_helper.php

/**
 * Get sales data for a specific time period
 *
 * @param string $period (daily, weekly, monthly, yearly)
 * @param string $start_date
 * @param string $end_date
 * @return array
 */
function getSalesData($period = 'monthly', $start_date = null, $end_date = null) {
    global $conn;
    
    // Set default dates if not provided
    if (!$start_date) {
        $start_date = date('Y-m-d', strtotime('-1 year'));
    }
    
    if (!$end_date) {
        $end_date = date('Y-m-d');
    }
    
    // Define the query based on the period
    switch ($period) {
        case 'daily':
            $group_by = "DATE(s.sale_date)";
            $date_format = "%Y-%m-%d";
            $select_date = "DATE(s.sale_date) as date";
            break;
        case 'weekly':
            $group_by = "YEARWEEK(s.sale_date)";
            $date_format = "%Y-%u";
            $select_date = "CONCAT('Week ', WEEK(s.sale_date), ', ', YEAR(s.sale_date)) as date";
            break;
        case 'monthly':
            $group_by = "MONTH(s.sale_date), YEAR(s.sale_date)";
            $date_format = "%Y-%m";
            $select_date = "DATE_FORMAT(s.sale_date, '%M %Y') as date";
            break;
        case 'yearly':
            $group_by = "YEAR(s.sale_date)";
            $date_format = "%Y";
            $select_date = "YEAR(s.sale_date) as date";
            break;
        default:
            $group_by = "MONTH(s.sale_date), YEAR(s.sale_date)";
            $date_format = "%Y-%m";
            $select_date = "DATE_FORMAT(s.sale_date, '%M %Y') as date";
    }
    
    // Build the query
    $query = "SELECT 
                {$select_date},
                SUM(s.total_amount) as total_sales,
                COUNT(DISTINCT s.sale_id) as num_transactions,
                AVG(s.total_amount) as avg_transaction_value
              FROM sales s
              WHERE s.sale_date BETWEEN :start_date AND :end_date
              GROUP BY {$group_by}
              ORDER BY s.sale_date ASC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get top selling products for a specific time period
 * 
 * @param string $start_date
 * @param string $end_date
 * @param int $limit
 * @return array
 */
function getTopSellingProducts($start_date = null, $end_date = null, $limit = 10) {
    global $conn;
    
    // Set default dates if not provided
    if (!$start_date) {
        $start_date = date('Y-m-d', strtotime('-1 month'));
    }
    
    if (!$end_date) {
        $end_date = date('Y-m-d');
    }
    
    // Build query
    $query = "SELECT 
                p.product_id,
                p.name,
                p.category,
                SUM(si.quantity_sold) as quantity_sold,
                SUM(si.subtotal) as total_revenue
              FROM sale_items si
              JOIN products p ON si.product_id = p.product_id
              JOIN sales s ON si.sale_id = s.sale_id
              WHERE s.sale_date BETWEEN :start_date AND :end_date
              GROUP BY p.product_id
              ORDER BY quantity_sold DESC
              LIMIT :limit";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get sales performance by category
 * 
 * @param string $start_date
 * @param string $end_date
 * @return array
 */
function getSalesByCategory($start_date = null, $end_date = null) {
    global $conn;
    
    // Set default dates if not provided
    if (!$start_date) {
        $start_date = date('Y-m-d', strtotime('-1 month'));
    }
    
    if (!$end_date) {
        $end_date = date('Y-m-d');
    }
    
    // Build query
    $query = "SELECT 
                p.category,
                SUM(si.quantity_sold) as quantity_sold,
                SUM(si.subtotal) as total_revenue,
                COUNT(DISTINCT s.sale_id) as num_transactions
              FROM sale_items si
              JOIN products p ON si.product_id = p.product_id
              JOIN sales s ON si.sale_id = s.sale_id
              WHERE s.sale_date BETWEEN :start_date AND :end_date
              GROUP BY p.category
              ORDER BY total_revenue DESC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get inventory value and status
 * 
 * @return array
 */
function getInventoryValue() {
    global $conn;
    
    $query = "SELECT 
                SUM(st.current_quantity * st.unit_cost) as total_inventory_value,
                COUNT(DISTINCT st.product_id) as total_products_in_stock,
                SUM(CASE WHEN p.minimum_stock_level >= st.current_quantity THEN 1 ELSE 0 END) as low_stock_count
              FROM stock st
              JOIN products p ON st.product_id = p.product_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Get monthly sales comparison (current vs previous year)
 * 
 * @return array
 */
function getMonthlySalesComparison() {
    global $conn;
    
    $current_year = date('Y');
    $previous_year = $current_year - 1;
    
    $query = "SELECT 
                MONTH(sale_date) as month,
                MONTHNAME(sale_date) as month_name,
                SUM(IF(YEAR(sale_date) = :current_year, total_amount, 0)) as current_year_sales,
                SUM(IF(YEAR(sale_date) = :previous_year, total_amount, 0)) as previous_year_sales
              FROM sales
              WHERE YEAR(sale_date) IN (:current_year, :previous_year)
              GROUP BY MONTH(sale_date), MONTHNAME(sale_date)
              ORDER BY MONTH(sale_date)";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':current_year', $current_year);
    $stmt->bindParam(':previous_year', $previous_year);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get profit margins by product
 * 
 * @param string $start_date
 * @param string $end_date
 * @param int $limit
 * @return array
 */
function getProductProfitMargins($start_date = null, $end_date = null, $limit = 20) {
    global $conn;
    
    // Set default dates if not provided
    if (!$start_date) {
        $start_date = date('Y-m-d', strtotime('-1 month'));
    }
    
    if (!$end_date) {
        $end_date = date('Y-m-d');
    }
    
    $query = "SELECT 
                p.product_id,
                p.name,
                p.category,
                AVG(si.unit_price) as avg_selling_price,
                AVG(st.unit_cost) as avg_cost_price,
                (AVG(si.unit_price) - AVG(st.unit_cost)) as avg_profit,
                ((AVG(si.unit_price) - AVG(st.unit_cost)) / AVG(si.unit_price) * 100) as profit_margin_percent,
                SUM(si.quantity_sold) as quantity_sold,
                SUM((si.unit_price - st.unit_cost) * si.quantity_sold) as total_profit
              FROM sale_items si
              JOIN sales s ON si.sale_id = s.sale_id
              JOIN products p ON si.product_id = p.product_id
              JOIN stock st ON si.product_id = st.product_id
              WHERE s.sale_date BETWEEN :start_date AND :end_date
              GROUP BY p.product_id
              ORDER BY profit_margin_percent DESC
              LIMIT :limit";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':start_date', $start_date);
    $stmt->bindParam(':end_date', $end_date);
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}