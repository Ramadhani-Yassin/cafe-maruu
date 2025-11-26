<?php
session_start(); // Ensure session is started
require_once '../posBackend/checkIfLoggedIn.php';
?>
<?php include '../inc/dashHeader.php'; 
require_once '../config.php';

// Set default timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

// Function to get sales data
function getSalesData($link, $period) {
$query = "SELECT 
                DATE(p.payment_time) as sale_date,
                SUM(p.payment_amount) as total_sales,
                SUM(p.tax_amount) as total_tax,
                SUM(p.tip_amount) as total_tip,
                SUM(p.room_service_fee) as total_room_service,
                SUM(p.delivery_fee) as total_delivery,
                COUNT(*) as transaction_count,
                p.payment_method,
                SUM(COALESCE(m.expense_amount * bi.quantity, 0)) + SUM(COALESCE(s.expense_per_unit * bi.quantity, 0)) as total_expenses
              FROM payment_records p
              LEFT JOIN bills b ON p.bill_id = b.bill_id
              LEFT JOIN bill_items bi ON b.bill_id = bi.bill_id
              LEFT JOIN menu m ON bi.item_id = m.item_id AND bi.source = 'menu'
              LEFT JOIN stock s ON bi.item_id = s.ItemID AND bi.source = 'stock'
              WHERE ";
    
    $currentDate = date('Y-m-d');
    $params = [];
    
    switch ($period) {
        case 'today':
            $query .= "DATE(p.payment_time) = ?";
            $params[] = $currentDate;
            break;
        case 'yesterday':
            $query .= "DATE(p.payment_time) = DATE_SUB(?, INTERVAL 1 DAY)";
            $params[] = $currentDate;
            break;
        case 'week':
            $query .= "YEARWEEK(p.payment_time, 1) = YEARWEEK(?, 1)";
            $params[] = $currentDate;
            break;
        case 'month':
            $query .= "YEAR(p.payment_time) = YEAR(?) AND MONTH(p.payment_time) = MONTH(?)";
            $params[] = $currentDate;
            $params[] = $currentDate;
            break;
        case 'year':
            $query .= "YEAR(p.payment_time) = YEAR(?)";
            $params[] = $currentDate;
            break;
        case 'custom':
            $startDate = $_POST['start_date'] ?? $currentDate;
            $endDate = $_POST['end_date'] ?? $currentDate;
            $query .= "DATE(p.payment_time) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            break;
    }
    
    $query .= " GROUP BY DATE(p.payment_time), p.payment_method ORDER BY sale_date DESC";
    
    $stmt = $link->prepare($query);
    if (count($params) > 0) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    $grandTotal = 0;
    $grandTax = 0;
    $grandTip = 0;
    $grandRoom = 0;
    $grandDelivery = 0;
    $totalTransactions = 0;
    $grandExpenses = 0;
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $grandTotal += $row['total_sales'];
        $grandTax += $row['total_tax'];
        $grandTip += $row['total_tip'];
        $grandRoom += $row['total_room_service'];
        $grandDelivery += $row['total_delivery'];
        $totalTransactions += $row['transaction_count'];
        $grandExpenses += $row['total_expenses'];
    }
    
    $grandProfit = $grandTotal - $grandExpenses;
    
    return [
        'data' => $data,
        'grandTotal' => $grandTotal,
        'grandTax' => $grandTax,
        'grandTip' => $grandTip,
        'grandRoom' => $grandRoom,
        'grandDelivery' => $grandDelivery,
        'totalTransactions' => $totalTransactions,
        'grandExpenses' => $grandExpenses,
        'grandProfit' => $grandProfit
    ];
}

// Get period from request or default to today
$period = $_GET['period'] ?? 'today';
$salesData = getSalesData($link, $period);

// Handle custom date range form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['custom_range'])) {
    $period = 'custom';
    $salesData = getSalesData($link, $period);
}
?>

<style>
    .report-container {
        margin-top: 6rem;
        padding: 30px 20px;
        max-width: 1400px;
        margin-left: auto;
        margin-right: auto;
    }
    
    @media (min-width: 992px) {
        .report-container {
            padding-left: 180px;
            padding-right: 30px;
        }
    }
    
    .report-card {
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        background: #fff;
    }
    
    .card-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        border-bottom: none;
        color: #fff !important;
        padding: 20px 25px;
        border-radius: 8px 8px 0 0 !important;
    }
    
    .card-title {
        font-weight: 600;
        margin: 0;
        font-size: 1.5rem;
        color: #fff;
    }
    
    .period-selector {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 25px;
    }
    
    .period-selector .btn {
        margin: 3px;
        background-color: #fff;
        color: #495057;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }
    
    .period-selector .btn:hover {
        background-color: #667eea;
        color: #fff;
        border-color: #667eea;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
    }
    
    .period-selector .btn-primary {
        background-color: #667eea;
        color: #fff;
        border-color: #667eea;
        box-shadow: 0 2px 6px rgba(102, 126, 234, 0.3);
    }
    
    .summary-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }
    
    .summary-card {
        background: #fff;
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 6px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }
    
    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
        background: #667eea;
    }
    
    .summary-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
    }
    
    .summary-card .card-title {
        font-size: 0.85rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .summary-card .card-text {
        font-size: 1.5rem;
        font-weight: 700;
        color: #212529;
        margin: 0;
    }
    
    .summary-card.primary .card-text { color: #667eea; }
    .summary-card.success .card-text { color: #28a745; }
    .summary-card.danger .card-text { color: #dc3545; }
    .summary-card.warning .card-text { color: #ffc107; }
    .summary-card.info .card-text { color: #17a2b8; }
    
    .report-table-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
        margin-top: 25px;
    }

    .table.report-table {
        margin: 0;
        font-size: 0.9rem;
    }

    .table.report-table th {
        background-color: #f8f9fa;
        border-bottom: 2px solid #dee2e6;
        font-weight: 600;
        color: #495057;
        padding: 12px 10px;
        white-space: nowrap;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .table.report-table td {
        padding: 12px 10px;
        vertical-align: middle;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .table.report-table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .table-bordered th,
    .table-bordered td {
        border: 1px solid #e0e0e0;
    }
    
    .table-striped tbody tr:nth-of-type(odd) {
        background-color: #fafafa;
    }
    
    .btn-danger {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: #fff;
        border: none;
        border-radius: 6px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .form-control {
        border-radius: 6px;
        border: 1px solid #dee2e6;
        padding: 8px 12px;
    }
    
    .input-group-append .btn {
        border-radius: 0 6px 6px 0;
    }
    
    .payment-method {
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 4px;
        display: inline-block;
        font-size: 0.85rem;
    }
    
    .payment-method.cash { background: #d4edda; color: #155724; }
    .payment-method.card { background: #cce5ff; color: #004085; }
    .payment-method.creditor { background: #fff3cd; color: #856404; }
    .payment-method.mobile { background: #d1ecf1; color: #0c5460; }
    
    .text-success {
        color: #28a745 !important;
        font-weight: 600;
    }
    
    .text-danger {
        color: #dc3545 !important;
        font-weight: 600;
    }
    
    .custom-date-form {
        margin-top: 10px;
    }
    
    .custom-date-form .input-group {
        max-width: 500px;
    }
    
    @media (max-width: 991.98px) {
        .report-container {
            padding-left: 20px;
            padding-right: 20px;
            margin-top: 5rem;
        }
        
        .summary-cards {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
        }
        
        .summary-card .card-text {
            font-size: 1.25rem;
        }
    }
    
    @media (max-width: 767.98px) {
        .period-selector .btn {
            width: calc(50% - 6px);
            font-size: 0.85rem;
            padding: 6px 12px;
        }
        
        .custom-date-form .input-group {
            width: 100%;
            max-width: 100%;
        }
        
        .custom-date-form .input-group .form-control {
            font-size: 0.85rem;
        }
        
        .summary-cards {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .summary-card {
            padding: 15px;
        }
        
        .summary-card .card-text {
            font-size: 1.1rem;
        }
        
        .table.report-table th,
        .table.report-table td {
            font-size: 0.8rem;
            padding: 8px 6px;
        }
        
        .card-title {
            font-size: 1.25rem;
        }
    }
    
    @media (max-width: 575.98px) {
        .summary-cards {
            grid-template-columns: 1fr;
        }
        
        .period-selector .btn {
            width: 100%;
        }
    }
</style>

<div class="container report-container">
    <div class="row">
        <div class="col-md-12">
            <div class="card report-card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Darajani Motel Sales Reports</h3>
                </div>
                <div class="card-body">
                    <!-- Period Selector -->
                    <div class="period-selector mb-4">
                        <a href="?period=today" class="btn <?= $period === 'today' ? 'btn-primary' : 'btn-outline-primary' ?>">Today</a>
                        <a href="?period=yesterday" class="btn <?= $period === 'yesterday' ? 'btn-primary' : 'btn-outline-primary' ?>">Yesterday</a>
                        <a href="?period=week" class="btn <?= $period === 'week' ? 'btn-primary' : 'btn-outline-primary' ?>">This Week</a>
                        <a href="?period=month" class="btn <?= $period === 'month' ? 'btn-primary' : 'btn-outline-primary' ?>">This Month</a>
                        <a href="?period=year" class="btn <?= $period === 'year' ? 'btn-primary' : 'btn-outline-primary' ?>">This Year</a>
                        
                        <!-- Custom Date Range Form -->
                        <form method="post" class="custom-date-form">
                            <div class="input-group">
                                <input type="date" name="start_date" class="form-control" value="<?= $_POST['start_date'] ?? date('Y-m-d') ?>">
                                <input type="date" name="end_date" class="form-control" value="<?= $_POST['end_date'] ?? date('Y-m-d') ?>">
                                <div class="input-group-append">
                                    <button type="submit" name="custom_range" class="btn btn-primary">Custom Range</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Summary Cards -->
                    <div class="summary-cards">
                        <div class="summary-card primary">
                            <h5 class="card-title">Total Sales</h5>
                            <h2 class="card-text">TZS <?= number_format($salesData['grandTotal'], 2) ?></h2>
                        </div>
                        <div class="summary-card success">
                            <h5 class="card-title">Total Tax</h5>
                            <h2 class="card-text">TZS <?= number_format($salesData['grandTax'], 2) ?></h2>
                        </div>
                        <div class="summary-card danger">
                            <h5 class="card-title">Total Expenses</h5>
                            <h2 class="card-text">TZS <?= number_format($salesData['grandExpenses'], 2) ?></h2>
                        </div>
                        <div class="summary-card <?= $salesData['grandProfit'] >= 0 ? 'success' : 'danger' ?>">
                            <h5 class="card-title">Net Profit</h5>
                            <h2 class="card-text">
                                TZS <?= number_format($salesData['grandProfit'], 2) ?>
                            </h2>
                        </div>
                        <div class="summary-card info">
                            <h5 class="card-title">Tips Collected</h5>
                            <h2 class="card-text">TZS <?= number_format($salesData['grandTip'], 2) ?></h2>
                        </div>
                        <div class="summary-card warning">
                            <h5 class="card-title">Room Services</h5>
                            <h2 class="card-text">TZS <?= number_format($salesData['grandRoom'], 2) ?></h2>
                        </div>
                        <div class="summary-card info">
                            <h5 class="card-title">Delivery Services</h5>
                            <h2 class="card-text">TZS <?= number_format($salesData['grandDelivery'], 2) ?></h2>
                        </div>
                        <div class="summary-card primary">
                            <h5 class="card-title">Transactions</h5>
                            <h2 class="card-text"><?= $salesData['totalTransactions'] ?></h2>
                        </div>
                        <div class="summary-card <?= ($salesData['grandTotal'] > 0 && ($salesData['grandProfit'] / $salesData['grandTotal']) >= 0) ? 'success' : 'danger' ?>">
                            <h5 class="card-title">Profit Margin</h5>
                            <h2 class="card-text">
                                <?= $salesData['grandTotal'] > 0 ? number_format(($salesData['grandProfit'] / $salesData['grandTotal']) * 100, 1) : 0 ?>%
                            </h2>
                        </div>
                        <div class="summary-card primary">
                            <h5 class="card-title">Average Order Value</h5>
                            <h2 class="card-text">TZS <?= $salesData['totalTransactions'] > 0 ? number_format($salesData['grandTotal'] / $salesData['totalTransactions'], 2) : 0 ?></h2>
                        </div>
                    </div>
                    
                    <!-- Detailed Report Table -->
                    <div class="report-table-wrapper">
                        <table class="table table-bordered table-striped report-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th><span>Payment</span><br><span>Method</span></th>
                                    <th><span>Trans</span><br><span>actions</span></th>
                                    <th>Tax</th>
                                    <th>Tip</th>
                                    <th>Room</th>
                                    <th>Delivery</th>
                                    <th>Expenses</th>
                                    <th>Total Sales</th>
                                    <th>Net Profit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($salesData['data'])): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-4" style="color: #6c757d;">No sales data available for this period</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($salesData['data'] as $row): ?>
                                        <?php 
                                        $rowProfit = $row['total_sales'] - $row['total_expenses'];
                                        ?>
                                        <tr>
                                            <td><?= date('M j, Y', strtotime($row['sale_date'])) ?></td>
                                            <td>
                                                <span class="payment-method <?= strtolower($row['payment_method']) ?>">
                                                    <?= ucfirst($row['payment_method']) ?>
                                                </span>
                                            </td>
                                            <td><?= $row['transaction_count'] ?></td>
                                            <td>TZS <?= number_format($row['total_tax'], 2) ?></td>
                                            <td>TZS <?= number_format($row['total_tip'], 2) ?></td>
                                            <td>TZS <?= number_format($row['total_room_service'], 2) ?></td>
                                            <td>TZS <?= number_format($row['total_delivery'], 2) ?></td>
                                            <td>TZS <?= number_format($row['total_expenses'], 2) ?></td>
                                            <td>TZS <?= number_format($row['total_sales'], 2) ?></td>
                                            <td class="<?= $rowProfit >= 0 ? 'text-success' : 'text-danger' ?>">
                                                TZS <?= number_format($rowProfit, 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Export Button - PDF Only -->
                    <div class="mt-4">
                        <a href="exportSales.php?period=<?= $period ?>" class="btn btn-danger">
                            <i class="fas fa-file-pdf"></i> Export as PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../inc/dashFooter.php'; ?>