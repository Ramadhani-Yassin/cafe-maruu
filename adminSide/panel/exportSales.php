<?php
session_start();
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../config.php';

// Get period from request
$period = $_GET['period'] ?? 'today';

// Include FPDF library only once
require_once('../posBackend/fpdf186/fpdf.php');

// Function to get sales data
function getSalesData($link, $period) {
$query = "SELECT 
                DATE(payment_time) as sale_date,
                SUM(payment_amount) as total_sales,
                SUM(tax_amount) as total_tax,
                SUM(tip_amount) as total_tip,
                SUM(room_service_fee) as total_room_service,
                SUM(delivery_fee) as total_delivery,
                COUNT(*) as transaction_count,
                payment_method
              FROM payment_records
              WHERE ";
    
    $currentDate = date('Y-m-d');
    $params = [];
    
    switch ($period) {
        case 'today':
            $query .= "DATE(payment_time) = ?";
            $params[] = $currentDate;
            break;
        case 'yesterday':
            $query .= "DATE(payment_time) = DATE_SUB(?, INTERVAL 1 DAY)";
            $params[] = $currentDate;
            break;
        case 'week':
            $query .= "YEARWEEK(payment_time, 1) = YEARWEEK(?, 1)";
            $params[] = $currentDate;
            break;
        case 'month':
            $query .= "YEAR(payment_time) = YEAR(?) AND MONTH(payment_time) = MONTH(?)";
            $params[] = $currentDate;
            $params[] = $currentDate;
            break;
        case 'year':
            $query .= "YEAR(payment_time) = YEAR(?)";
            $params[] = $currentDate;
            break;
        case 'custom':
            $startDate = $_POST['start_date'] ?? $currentDate;
            $endDate = $_POST['end_date'] ?? $currentDate;
            $query .= "DATE(payment_time) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
            break;
    }
    
    $query .= " GROUP BY DATE(payment_time), payment_method ORDER BY sale_date DESC";
    
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
    
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        $grandTotal += $row['total_sales'];
        $grandTax += $row['total_tax'];
        $grandTip += $row['total_tip'];
        $grandRoom += $row['total_room_service'];
        $grandDelivery += $row['total_delivery'];
        $totalTransactions += $row['transaction_count'];
    }
    
    return [
        'data' => $data,
        'grandTotal' => $grandTotal,
        'grandTax' => $grandTax,
        'grandTip' => $grandTip,
        'grandRoom' => $grandRoom,
        'grandDelivery' => $grandDelivery,
        'totalTransactions' => $totalTransactions
    ];
}

$salesData = getSalesData($link, $period);

// Extend FPDF class
class SalesPDF extends FPDF {
    function Header() {
        $this->SetFont('Arial','B',15);
        $this->Cell(0,10,'Sales Report',0,1,'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial','I',8);
        $this->Cell(0,10,'Page '.$this->PageNo().'/{nb}',0,0,'C');
    }
    
    // Add a method to sanitize text for PDF output
    function sanitizeText($text) {
        return iconv('UTF-8', 'windows-1252', html_entity_decode($text));
    }
}

// Create PDF instance with PORTRAIT orientation
$pdf = new SalesPDF('P'); // Changed from 'L' to 'P' for portrait
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial','',12);

// Report title and period
$pdf->Cell(0,10,$pdf->sanitizeText('Period: '.ucfirst($period)),0,1);
$pdf->Ln(5);

// Adjusted column widths for portrait orientation
$col_width_date = 26;
$col_width_method = 24;
$col_width_transactions = 18;
$col_width_tax = 20;
$col_width_tip = 20;
$col_width_room = 20;
$col_width_delivery = 20;
$col_width_total = 32; // Total Sales column width

// Create table header
$pdf->SetFont('Arial','B',10);
$pdf->Cell($col_width_date,10,'Date',1,0,'C');
$pdf->Cell($col_width_method,10,'Method',1,0,'C');
$pdf->Cell($col_width_transactions,10,'Transactions',1,0,'C');
$pdf->Cell($col_width_tax,10,'Tax',1,0,'C');
$pdf->Cell($col_width_tip,10,'Tip',1,0,'C');
$pdf->Cell($col_width_room,10,'Room',1,0,'C');
$pdf->Cell($col_width_delivery,10,'Delivery',1,0,'C');
$pdf->Cell($col_width_total,10,'Total Sales (TZS)',1,1,'C');

// Table data
if (empty($salesData['data'])) {
    $pdf->Cell(0,10,$pdf->sanitizeText('No sales data available for this period'),0,1,'C');
} else {
    $pdf->SetFont('Arial','',9);
    foreach ($salesData['data'] as $row) {
        $pdf->Cell($col_width_date,10,$pdf->sanitizeText(date('M j, Y', strtotime($row['sale_date']))),1,0,'L');
        $pdf->Cell($col_width_method,10,$pdf->sanitizeText(ucfirst($row['payment_method'])),1,0,'L');
        $pdf->Cell($col_width_transactions,10,$pdf->sanitizeText($row['transaction_count']),1,0,'C');
        $pdf->Cell($col_width_tax,10,$pdf->sanitizeText(number_format($row['total_tax'], 2)),1,0,'R');
        $pdf->Cell($col_width_tip,10,$pdf->sanitizeText(number_format($row['total_tip'], 2)),1,0,'R');
        $pdf->Cell($col_width_room,10,$pdf->sanitizeText(number_format($row['total_room_service'], 2)),1,0,'R');
        $pdf->Cell($col_width_delivery,10,$pdf->sanitizeText(number_format($row['total_delivery'], 2)),1,0,'R');
        $pdf->Cell($col_width_total,10,$pdf->sanitizeText(number_format($row['total_sales'], 2)),1,1,'R');
    }
}

// Summary rows - align under Total column
$summary_label_width = $col_width_date + $col_width_method + $col_width_transactions + $col_width_tax + $col_width_tip + $col_width_room + $col_width_delivery;
$pdf->SetFont('Arial','B',10);
$pdf->Cell($summary_label_width,10,'Total Tax:',1,0,'R');
$pdf->Cell($col_width_total,10,$pdf->sanitizeText(number_format($salesData['grandTax'], 2)),1,1,'R');

$pdf->Cell($summary_label_width,10,'Total Tips:',1,0,'R');
$pdf->Cell($col_width_total,10,$pdf->sanitizeText(number_format($salesData['grandTip'], 2)),1,1,'R');

$pdf->Cell($summary_label_width,10,'Room Services:',1,0,'R');
$pdf->Cell($col_width_total,10,$pdf->sanitizeText(number_format($salesData['grandRoom'], 2)),1,1,'R');

$pdf->Cell($summary_label_width,10,'Delivery Services:',1,0,'R');
$pdf->Cell($col_width_total,10,$pdf->sanitizeText(number_format($salesData['grandDelivery'], 2)),1,1,'R');

$pdf->Cell($summary_label_width,10,'Grand Total:',1,0,'R');
$pdf->Cell($col_width_total,10,$pdf->sanitizeText(number_format($salesData['grandTotal'], 2)),1,1,'R');

// Output the PDF
$pdf->Output('D', 'sales_report_'.$period.'.pdf');
exit;