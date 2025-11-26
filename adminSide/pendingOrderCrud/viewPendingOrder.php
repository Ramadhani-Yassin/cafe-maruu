<?php
session_start();
require_once '../config.php';
include '../inc/dashHeader.php';

if (!isset($_GET['order_id'])) {
    die("Order ID is required.");
}

$order_id = $_GET['order_id'];

// Fetch pending order details
$order_query = "SELECT * FROM pendingorders WHERE order_id = '$order_id'";
$order_result = mysqli_query($link, $order_query);
$order = mysqli_fetch_assoc($order_result);

// Fetch items in the pending order
$items_query = "SELECT * FROM pendingorderitems WHERE order_id = '$order_id'";
$items_result = mysqli_query($link, $items_query);

// Fetch items from Menu and Stock for the search functionality
$search_query = "SELECT item_id, item_name, item_price, 'menu' AS source, NULL AS unit 
                 FROM menu 
                 UNION 
                 SELECT ItemID AS item_id, CONCAT(ItemName, ' (', BaseUnitName, ')') AS item_name, 
                        PricePerBaseUnit AS item_price, 'stock' AS source, 'base' AS unit 
                 FROM stock 
                 UNION 
                 SELECT ItemID AS item_id, CONCAT(ItemName, ' (', AggregateUnitName, ')') AS item_name, 
                        PricePerSubUnit AS item_price, 'stock' AS source, 'aggregate' AS unit 
                 FROM stock 
                 WHERE ConversionRatio > 1";

// Check if a search term is submitted
if (isset($_GET['search'])) {
    $search = $_GET['search'];
    $search_query = "SELECT item_id, item_name, item_price, 'menu' AS source, NULL AS unit 
                     FROM menu 
                     WHERE item_name LIKE '%$search%' 
                     OR item_id LIKE '%$search%' 
                     UNION 
                     SELECT ItemID AS item_id, CONCAT(ItemName, ' (', BaseUnitName, ')') AS item_name, 
                            PricePerBaseUnit AS item_price, 'stock' AS source, 'base' AS unit 
                     FROM stock 
                     WHERE ItemName LIKE '%$search%' 
                     UNION 
                     SELECT ItemID AS item_id, CONCAT(ItemName, ' (', AggregateUnitName, ')') AS item_name, 
                            PricePerSubUnit AS item_price, 'stock' AS source, 'aggregate' AS unit 
                     FROM stock 
                     WHERE ItemName LIKE '%$search%' AND ConversionRatio > 1";
}

// Check if "Show All" is clicked
$show_all = isset($_GET['show_all']) ? $_GET['show_all'] : "false";
if ($show_all === "true") {
    // Show all items
    $search_query = "SELECT item_id, item_name, item_price, 'menu' AS source, NULL AS unit 
                     FROM menu 
                     UNION 
                     SELECT ItemID AS item_id, CONCAT(ItemName, ' (', BaseUnitName, ')') AS item_name, 
                            PricePerBaseUnit AS item_price, 'stock' AS source, 'base' AS unit 
                     FROM stock 
                     UNION 
                     SELECT ItemID AS item_id, CONCAT(ItemName, ' (', AggregateUnitName, ')') AS item_name, 
                            PricePerSubUnit AS item_price, 'stock' AS source, 'aggregate' AS unit 
                     FROM stock 
                     WHERE ConversionRatio > 1";
}

$search_result = mysqli_query($link, $search_query);

// Check if the bill has been paid
$bill_id = $order['bill_id'];
$payment_time_query = "SELECT payment_time FROM bills WHERE bill_id = '$bill_id'";
$payment_time_result = mysqli_query($link, $payment_time_query);
$has_payment_time = false;

if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
    $payment_time_row = mysqli_fetch_assoc($payment_time_result);
    if (!empty($payment_time_row['payment_time'])) {
        $has_payment_time = true;
    }
}

// Handle "Pay Bill" action
if (isset($_POST['pay_bill'])) {
    // Fetch all items in the pending order
    $items_query = "SELECT * FROM pendingorderitems WHERE order_id = '$order_id'";
    $items_result = mysqli_query($link, $items_query);

    if ($items_result && mysqli_num_rows($items_result) > 0) {
        while ($item = mysqli_fetch_assoc($items_result)) {
            $item_id = $item['item_id'];
            $item_name = $item['item_name'];
            $quantity = $item['quantity'];
            $item_price = $item['item_price'];
            $source = $item['source'];
            $unit = isset($item['unit']) ? $item['unit'] : 'base';

            // Insert the item into the bill_items table with correct unit
            $insert_query = "INSERT INTO bill_items (bill_id, item_id, item_name, quantity, source, unit, item_price) 
                             VALUES ('$bill_id', '$item_id', '$item_name', '$quantity', '$source', '$unit', '$item_price')";
            mysqli_query($link, $insert_query);
        }

        // Mark the pending order as completed
        $update_query = "UPDATE pendingorders SET status = 'Completed' WHERE order_id = '$order_id'";
        mysqli_query($link, $update_query);

        // Redirect to the payment page
        header("Location: viewPendingOrder.php?order_id=$order_id");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../css/pos.css" />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>View Pending Order</title>
    <style>
        .scrollable-table {
            max-height: 400px;
            overflow-y: auto;
            overflow-x: auto; /* allow horizontal scroll on small screens */
            display: block;
        }
        .scrollable-table table {
            width: 100%;
            table-layout: fixed;
        }
        .scrollable-table th,
        .scrollable-table td {
            width: auto;
            text-align: left;
            padding: 8px;
        }
        .cart-table { width: 100% !important; max-width: 100% !important; table-layout: fixed; word-wrap: break-word; margin: 0 auto; border-collapse: collapse; min-width: 720px; }
        .cart-table th, .cart-table td { white-space: normal; overflow-wrap: anywhere; }
        .cart-table th.action-col, .cart-table td.action-col { width: 52px; text-align: center; }
        .btn-icon { display: inline-flex; align-items: center; justify-content: center; padding: 6px 8px; line-height: 1; }
        .btn-icon svg { width: 16px; height: 16px; fill: currentColor; }
        .scrollable-table th {
            background-color: white;
            color: black;
            font-weight: bold;
        }
        .scrollable-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .container-fluid {
            padding-top: 64px;
            padding-left: 200px;
            padding-right: 20px;
        }
        .half-section {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
           
        }
        #paymentOptionsSection a { margin-right: 12px; margin-top: 6px; }
        .btn-creditors {
            background-color: #cd29be;
            color: white;
            border: none;
        }
        .btn-creditors:hover, .btn-pink:focus {
            background-color: #cd29be;
            color: white;
        }
        .btn-compo {
            background-color: #873e23;
            color: white;
            border: none;
        }
        .btn-compo:hover, .btn-orange:focus {
            background-color: #873e23;
            color: white;
        }
        .unit-badge {
            font-size: 0.8em;
            padding: 2px 5px;
            border-radius: 3px;
            background-color: #f0f0f0;
            color: #333;
        }
        .addon-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            background-color: #fafafa;
        }
        
        @media (max-width: 991.98px) {
            .container-fluid { padding-left: 16px; padding-right: 16px; }
            .half-section { margin-right: 0; }
        }
        @media (max-width: 767.98px) {
            .row > .half-section { width: 100%; }
            .scrollable-table { max-height: 320px; }
            #paymentOptionsSection .btn { width: 100%; }
            /* On small screens, keep fixed layout; prevent wrapping and use ellipsis for names */
            .cart-table { table-layout: fixed; }
            .cart-table th, .cart-table td { white-space: nowrap; overflow-wrap: normal; }
            .cart-table th:nth-child(2), .cart-table td:nth-child(2) { max-width: 240px; overflow: hidden; text-overflow: ellipsis; }
        }
        @media (max-width: 575.98px) {
            /* Tighten container padding on very small screens to center content */
            .container-fluid { padding-left: 8px; padding-right: 8px; }
            .half-section { margin-left: 0; margin-right: 0; }
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            <!-- Left Half: Search and Add Items -->
            <div class="col-md-6 half-section">
                <h2><?= $order['customer_name'] ?>'s Order</h2>
                <div class="mb-3">
                    <form id="searchForm" method="GET" action="viewPendingOrder.php">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search Food & Drinks" value="<?= isset($_GET['search']) ? $_GET['search'] : '' ?>">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-dark">Search</button>
                            </div>
                            <div class="col-md-3">
                                <!--<button type="button" id="showAllButton" class="btn btn-light">Show All</button>-->
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Display search results -->
                <div id="searchResults" class="table-responsive scrollable-table">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Price</th>
                                <th>Add</th>
                            </tr>
                        </thead>
                        <tbody id="searchResultsBody">
                            <?php
                            if ($search_result && mysqli_num_rows($search_result) > 0) {
                                while ($row = mysqli_fetch_assoc($search_result)) {
                                    echo "<tr>";
                                    echo "<td>" . $row['item_id'] . "</td>";
                                    echo "<td>" . $row['item_name'] . "</td>";
                                    echo "<td>" . number_format($row['item_price'], 2) . "</td>";
                                    echo "<td>";
                                    if (!$has_payment_time) {
                                        echo "<form method='POST' action='../pendingOrderCrud/addPendingItem.php' style='display:inline;'>
                                                <input type='hidden' name='order_id' value='$order_id'>
                                                <input type='hidden' name='item_id' value='{$row['item_id']}'>
                                                <input type='hidden' name='source' value='{$row['source']}'>
                                                <input type='hidden' name='unit' value='" . ($row['unit'] ?? 'base') . "'>
                                                <input type='number' name='quantity' placeholder='1 to 1000' required min='1' max='1000' style='width: 100px;'>
                                                <button type='submit' class='btn btn-primary'>Add</button>
                                              </form>";
                                    } else {
                                        echo "Bill Paid";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>No items found.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Half: Display Pending Order Items -->
            <div class="col-md-6 half-section">
                <br><br><br>
                <h2>Items Added For <?= $order['customer_name'] ?></h2>
                <div class="table-responsive scrollable-table">
                    <table class="table table-bordered mb-0 cart-table">
                <div class="table-responsive scrollable-table">
                    <table class="table table-bordered mb-0 cart-table">
                        <thead>
                            <tr>
                                <th>Item ID</th>
                                <th>Item Name</th>
                                <th>Price (TZS)</th>
                                <th>Quantity</th>
                                <th>Total (TZS)</th>
                                <th class="action-col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $cart_total = 0;
                            if ($items_result && mysqli_num_rows($items_result) > 0) {
                                while ($item = mysqli_fetch_assoc($items_result)) {
                                    $total = $item['item_price'] * $item['quantity'];
                                    $cart_total += $total;
                                    echo "<tr>";
                                    echo "<td>" . $item['item_id'] . "</td>";
                                    echo "<td>" . $item['item_name'] . " <span class='unit-badge'>" . ($item['unit'] ?? 'base') . "</span></td>";
                                    echo "<td>" . number_format($item['item_price'], 2) . "</td>";
                                    echo "<td>" . $item['quantity'] . "</td>";
                                    echo "<td>" . number_format($total, 2) . "</td>";
                                    echo "<td>";
                                    if (!$has_payment_time) {
                                        echo "<a href='../pendingOrderCrud/deletePendingItem.php?order_id=$order_id&item_id={$item['item_id']}&source={$item['source']}&unit={$item['unit']}' class='btn btn-danger btn-icon' aria-label='Delete item' title='Delete item'>"
                                            . "<svg viewBox='0 0 24 24' aria-hidden='true'><path d='M9 3h6a1 1 0 0 1 1 1v2h4a1 1 0 1 1 0 2h-1.1l-1.2 12.1A3 3 0 0 1 14.7 23H9.3a3 3 0 0 1-2.99-2.9L5.1 8H4a1 1 0 1 1 0-2h4V4a1 1 0 0 1 1-1Zm1 3h4V5h-4v1ZM7.1 8l1.1 11.1A1 1 0 0 0 9.3 20h5.4a1 1 0 0 0 1-.9L16.9 8H7.1ZM10 10a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm4 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z'/></svg>"
                                            . "</a>";
                                    } else {
                                        echo "Bill Paid";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No items found in this pending order.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <!-- Display Cart Total -->
                <div style="margin-top: 20px;">
                    <table class="table table-bordered mb-0">
                        <tr>
                            <th>Cart Total</th>
                            <td>TZS <?= number_format($cart_total, 2) ?></td>
                        </tr>
                        <tr>
                            <th>Tax (18% auto)</th>
                            <td>TZS <?= number_format($cart_total * 0.18, 2) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="addon-card mt-3">
                    <h5 class="mb-3">Optional Charges</h5>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="tipPercentage">Tip (%)</label>
                            <input type="number" min="0" max="10" step="0.01" id="tipPercentage" class="form-control" placeholder="0.00" value="0" oninput="validateTip()">
                            <small class="text-danger" id="tipError" style="display:none;">Maximum tip is 10%</small>
                            <small class="text-muted" id="tipAmountDisplay">Tip Amount: TZS 0.00</small>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="roomServiceAmount">Room Services (TZS)</label>
                            <input type="number" min="0" step="0.01" id="roomServiceAmount" class="form-control" placeholder="0.00" value="0">
                        </div>
                        <div class="form-group col-md-4">
                            <label for="deliveryAmount">Delivery Service (TZS)</label>
                            <input type="number" min="0" step="0.01" id="deliveryAmount" class="form-control" placeholder="0.00" value="0">
                        </div>
                    </div>
                    <small class="text-muted d-block">Tax (18%) is added automatically. Tip is optional (max 10% of cart total).</small>
                </div>

                <script>
                const cartTotal = <?= $cart_total ?>;
                
                function validateTip() {
                    const tipInput = document.getElementById('tipPercentage');
                    const tipError = document.getElementById('tipError');
                    const tipAmountDisplay = document.getElementById('tipAmountDisplay');
                    let tipPercentage = parseFloat(tipInput.value) || 0;
                    
                    // Strict validation: deny values > 10
                    if (tipPercentage > 10) {
                        tipInput.value = 10;
                        tipPercentage = 10;
                        tipError.style.display = 'block';
                        tipError.textContent = 'Maximum tip is 10%. Value has been capped at 10%.';
                        setTimeout(() => { tipError.style.display = 'none'; }, 5000);
                        return false;
                    }
                    
                    if (tipPercentage < 0) {
                        tipInput.value = 0;
                        tipPercentage = 0;
                        return false;
                    }
                    
                    tipError.style.display = 'none';
                    // Calculate tip amount: tip_percentage * cart_total / 100
                    const tipAmount = (tipPercentage / 100) * cartTotal;
                    tipAmountDisplay.textContent = 'Tip Amount: TZS ' + tipAmount.toFixed(2);
                    return true;
                }
                
                // Prevent typing values > 10
                document.getElementById('tipPercentage').addEventListener('input', function(e) {
                    const value = parseFloat(this.value);
                    if (value > 10) {
                        this.value = 10;
                        validateTip();
                    } else {
                        validateTip();
                    }
                });
                
                // Validate on blur as well
                document.getElementById('tipPercentage').addEventListener('blur', function(e) {
                    validateTip();
                });
                
                // Initialize tip amount display
                validateTip();
                </script>

                <!-- Pay Bill Button and Payment Options -->
                <?php if (!$has_payment_time && mysqli_num_rows($items_result) > 0): ?>
                    <div style="margin-top: 20px;" class="d-flex justify-content-between flex-wrap">
                        <button type="button" class="btn btn-success" id="payBillButton" onclick="payBill()">Pay Bill</button>
                        <?php if ($bill_id > 0): ?>
                            <button type="button" class="btn btn-info" id="orderNoteButton" onclick="launchOrderNote()">Order Note</button>
                        <?php endif; ?>
                    </div>

                    <div id="paymentOptionsSection" style="display: none; margin-top: 20px;">
                        <div class="mt-3 d-flex flex-wrap">
                            <button type="button" class="btn btn-success mr-2 mb-2" onclick="launchPayment('cash')">Cash</button>
                            <button type="button" class="btn btn-primary mr-2 mb-2" onclick="launchPayment('card')">Card | Mobile</button>
                            <button type="button" class="btn btn-creditors mr-2 mb-2" onclick="launchPayment('creditor')">Creditors</button>
                            <a href="../posBackend/posCompo.php?bill_id=<?= $bill_id ?>&staff_id=<?= $_SESSION['logged_account_id'] ?? 1 ?>&member_id=1&reservation_id=1120251" class="btn btn-compo mb-2">Compo</a>
                        </div>
                    </div>
                <?php elseif ($has_payment_time): ?>
                    <div style="margin-top: 20px;">
                        <a href="../posBackend/receipt.php?bill_id=<?= $bill_id ?>" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../inc/dashFooter.php'; ?>

    <script>
    // Show all items when "Show All" is clicked
    document.getElementById('showAllButton')?.addEventListener('click', function () {
        const searchResults = document.getElementById('searchResults');
        searchResults.style.display = 'block';
        window.location.href = "viewPendingOrder.php?order_id=<?= $order_id ?>&show_all=true";
    });

    // Live search functionality
    document.getElementById('searchInput').addEventListener('input', function () {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#searchResultsBody tr');

        rows.forEach(row => {
            const itemName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            if (itemName.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Pay Bill functionality
    function payBill() {
        document.getElementById('payBillButton').style.display = 'none';
        const orderNoteBtn = document.getElementById('orderNoteButton');
        if (orderNoteBtn) orderNoteBtn.style.display = 'none';
        document.getElementById('paymentOptionsSection').style.display = 'block';
    }

    const paymentContext = {
        billId: '<?= $bill_id ?>',
        staffId: '<?= $_SESSION['logged_account_id'] ?? 1 ?>',
        memberId: '1',
        reservationId: '1120251'
    };

    const endpoints = {
        cash: '../posBackend/posCashPayment.php',
        card: '../posBackend/posCardPayment.php',
        creditor: '../posBackend/posCreditors.php'
    };

    function launchPayment(type) {
        const tipInput = document.getElementById('tipPercentage');
        const roomInput = document.getElementById('roomServiceAmount');
        const deliveryInput = document.getElementById('deliveryAmount');
        let tipPercentage = parseFloat(tipInput.value) || 0;
        const roomValue = parseFloat(roomInput.value) || 0;
        const deliveryValue = parseFloat(deliveryInput.value) || 0;

        // Strict validation: deny process if tip > 10%
        if (tipPercentage > 10) {
            alert('Tip percentage cannot exceed 10%. Please enter a value between 0% and 10%.');
            tipInput.focus();
            tipInput.value = 10;
            validateTip();
            return;
        }

        if (tipPercentage < 0) {
            alert('Tip percentage cannot be negative.');
            tipInput.focus();
            tipInput.value = 0;
            validateTip();
            return;
        }

        if (roomValue < 0 || deliveryValue < 0) {
            alert('Additional charges cannot be negative.');
            return;
        }

        if (!paymentContext.billId || paymentContext.billId === '0') {
            alert('Please close this pending order to generate a bill before proceeding to payment.');
            return;
        }

        // Ensure tip percentage is capped at 10
        tipPercentage = Math.min(10, Math.max(0, tipPercentage));

        const endpoint = endpoints[type];
        if (!endpoint) {
            console.error('Unsupported payment type selected:', type);
            return;
        }

        const params = new URLSearchParams({
            bill_id: paymentContext.billId,
            staff_id: paymentContext.staffId,
            member_id: paymentContext.memberId,
            reservation_id: paymentContext.reservationId,
            tip_percentage: tipPercentage.toFixed(2),
            room_service_fee: roomValue.toFixed(2),
            delivery_fee: deliveryValue.toFixed(2)
        });

        window.location.href = `${endpoint}?${params.toString()}`;
    }

    function launchOrderNote() {
        const tipInput = document.getElementById('tipPercentage');
        const roomInput = document.getElementById('roomServiceAmount');
        const deliveryInput = document.getElementById('deliveryAmount');
        let tipPercentage = parseFloat(tipInput.value) || 0;
        const roomValue = parseFloat(roomInput.value) || 0;
        const deliveryValue = parseFloat(deliveryInput.value) || 0;

        // Strict validation: deny process if tip > 10%
        if (tipPercentage > 10) {
            alert('Tip percentage cannot exceed 10%. Please enter a value between 0% and 10%.');
            tipInput.focus();
            tipInput.value = 10;
            validateTip();
            return;
        }

        if (tipPercentage < 0) {
            alert('Tip percentage cannot be negative.');
            tipInput.focus();
            tipInput.value = 0;
            validateTip();
            return;
        }

        if (roomValue < 0 || deliveryValue < 0) {
            alert('Additional charges cannot be negative.');
            return;
        }

        if (!paymentContext.billId || paymentContext.billId === '0') {
            alert('Please close this pending order to generate a bill before creating an order note.');
            return;
        }

        // Ensure tip percentage is capped at 10
        tipPercentage = Math.min(10, Math.max(0, tipPercentage));

        const params = new URLSearchParams({
            bill_id: paymentContext.billId,
            tip_percentage: tipPercentage.toFixed(2),
            room_service_fee: roomValue.toFixed(2),
            delivery_fee: deliveryValue.toFixed(2)
        });

        window.open(`../posBackend/orderNote.php?${params.toString()}`, '_blank');
    }
    </script>
</body>
</html>