<?php
session_start();
require('fpdf186/fpdf.php');
require_once '../config.php';

date_default_timezone_set('Africa/Khartoum');

// Check if bill_id is provided
if (!isset($_GET['bill_id'])) {
    die("Invalid Bill ID");
}

$bill_id = mysqli_real_escape_string($link, $_GET['bill_id']);

// Get delivery, room service, and tip from URL parameters
$tip_amount_input = isset($_GET['tip_amount']) ? max(0, floatval($_GET['tip_amount'])) : 0;
$room_service_fee = isset($_GET['room_service_fee']) ? floatval($_GET['room_service_fee']) : 0;
$delivery_fee = isset($_GET['delivery_fee']) ? floatval($_GET['delivery_fee']) : 0;

// Fetch bill details
$bill_query = "SELECT * FROM bills WHERE bill_id = '$bill_id'";
$bill_result = mysqli_query($link, $bill_query);
$bill_data = mysqli_fetch_assoc($bill_result);

if (!$bill_data) {
    die("Bill not found");
}

// Fetch bill items
$items_query = "
    SELECT bi.item_id, 
           COALESCE(m.item_name, s.ItemName) AS item_name, 
           COALESCE(m.item_price, CASE WHEN bi.unit = 'base' THEN s.PricePerBaseUnit ELSE s.PricePerSubUnit END) AS item_price, 
           bi.quantity, 
           bi.source, 
           bi.unit
    FROM bill_items bi
    LEFT JOIN menu m ON bi.item_id = m.item_id AND bi.source = 'menu'
    LEFT JOIN stock s ON bi.item_id = s.ItemID AND bi.source = 'stock'
    WHERE bi.bill_id = '$bill_id'
    UNION
    SELECT poi.item_id, 
           COALESCE(m.item_name, s.ItemName) AS item_name, 
           COALESCE(m.item_price, CASE WHEN poi.unit = 'base' THEN s.PricePerBaseUnit ELSE s.PricePerSubUnit END) AS item_price, 
           poi.quantity, 
           poi.source, 
           poi.unit
    FROM pendingorderitems poi
    LEFT JOIN menu m ON poi.item_id = m.item_id AND poi.source = 'menu'
    LEFT JOIN stock s ON poi.item_id = s.ItemID AND poi.source = 'stock'
    WHERE poi.order_id = (SELECT order_id FROM pendingorders WHERE bill_id = '$bill_id')
";
$items_result = mysqli_query($link, $items_query);

// Initialize PDF for 80mm width with half A4 page height
$pdf = new FPDF('P', 'mm', array(80, 148.5));
$pdf->AddPage();
$pdf->SetMargins(3, 3, 3);
$pdf->SetAutoPageBreak(false); // Disable auto page break for single continuous page

// Convert bill_time to local time
$bill_time_cat = 'N/A';
if (!empty($bill_data['bill_time'])) {
    $bill_time_obj = DateTime::createFromFormat('Y-m-d H:i:s', $bill_data['bill_time'], new DateTimeZone('UTC'));
    if ($bill_time_obj) {
        $bill_time_obj->setTimezone(new DateTimeZone('Africa/Khartoum'));
        $bill_time_cat = $bill_time_obj->format('d/m/Y H:i');
    }
}

// Restaurant Header
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 4, "Darajani Motel", 0, 1, 'C');
$pdf->SetFont('Arial', '', 7);
$pdf->Cell(0, 4, "ORDER NOTE - PREVIEW ONLY", 0, 1, 'C');
$pdf->Ln(1);

// Order Note Info
$pdf->Cell(0, 3, 'Bill ID: ' . $bill_data['bill_id'], 0, 1, 'L');
$pdf->Cell(0, 3, 'Date: ' . $bill_time_cat, 0, 1, 'L');
if (!empty($bill_data['table_id'])) {
    $pdf->Cell(0, 3, 'Table: ' . $bill_data['table_id'], 0, 1, 'L');
}
$pdf->Cell(0, 3, 'Staff: ' . $bill_data['staff_id'], 0, 1, 'L');

$pdf->Ln(1); // Small spacer

// Items Header
$pdf->SetFont('Arial', 'B', 7);
$pdf->Cell(32, 4, 'ITEM', 0);
$pdf->Cell(15, 4, 'PRICE', 0, 0, 'R');
$pdf->Cell(10, 4, 'QTY', 0, 0, 'R');
$pdf->Cell(15, 4, 'TOTAL', 0, 0, 'R');
$pdf->Ln();
$pdf->Cell(0, 1, str_repeat('-', 72), 0, 1);

// Items List
$pdf->SetFont('Arial', '', 7);
$cart_total = 0;
while ($item_row = mysqli_fetch_assoc($items_result)) {
    $item_name = substr($item_row['item_name'], 0, 20);
    $quantity = $item_row['quantity'];
    $item_price = $item_row['item_price'];
    $total = $item_price * $quantity;
    $cart_total += $total;

    $pdf->Cell(32, 4, $item_name, 0);
    $pdf->Cell(15, 4, number_format($item_price, 0), 0, 0, 'R');
    $pdf->Cell(10, 4, $quantity, 0, 0, 'R');
    $pdf->Cell(15, 4, number_format($total, 0), 0, 0, 'R');
    $pdf->Ln();
}

// Summary Section
$pdf->Cell(0, 1, str_repeat('-', 72), 0, 1);
$pdf->SetFont('Arial', 'B', 8);

$tax_rate = 0.18;
$tax_amount = $cart_total * $tax_rate;
// Use manually entered tip amount, cap it at 10% of cart total
$max_tip = $cart_total * 0.10;
$tip_amount = min($tip_amount_input, $max_tip);
$room_fee = $room_service_fee;
$delivery_fee_total = $delivery_fee;
$estimated_total = $cart_total + $tax_amount + $tip_amount + $room_fee + $delivery_fee_total;

$pdf->Cell(57, 5, 'SUBTOTAL:', 0);
$pdf->Cell(15, 5, number_format($cart_total, 0), 0, 0, 'R');
$pdf->Ln();

$pdf->Cell(57, 5, 'TAX (18%):', 0);
$pdf->Cell(15, 5, number_format($tax_amount, 0), 0, 0, 'R');
$pdf->Ln();

$pdf->Cell(57, 5, 'TIP:', 0);
$pdf->Cell(15, 5, number_format($tip_amount, 0), 0, 0, 'R');
$pdf->Ln();

$pdf->Cell(57, 5, 'ROOM SERVICES:', 0);
$pdf->Cell(15, 5, number_format($room_fee, 0), 0, 0, 'R');
$pdf->Ln();

$pdf->Cell(57, 5, 'DELIVERY SERVICE:', 0);
$pdf->Cell(15, 5, number_format($delivery_fee_total, 0), 0, 0, 'R');
$pdf->Ln();

$pdf->Cell(0, 1, str_repeat('-', 72), 0, 1);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(57, 5, 'ESTIMATED TOTAL:', 0);
$pdf->Cell(15, 5, number_format($estimated_total, 0), 0, 0, 'R');
$pdf->Ln();

// Footer
$pdf->Ln(2);
$pdf->SetFont('Arial', 'I', 6);
$pdf->Cell(0, 3, 'This is an order preview only.', 0, 1, 'C');
$pdf->Cell(0, 3, 'NOT A VALID RECEIPT - NO TRANSACTION RECORDED', 0, 1, 'C');
$pdf->Cell(0, 3, 'Prices shown are estimates before any additional charges.', 0, 1, 'C');
$pdf->Ln(1);
$pdf->SetFont('Arial', '', 6);
$pdf->Cell(0, 3, 'Generated: ' . date('d/m/Y H:i'), 0, 1, 'C');

// Output PDF
$pdf->Output('D', 'OrderNote-'.$bill_id.'.pdf');
?>

