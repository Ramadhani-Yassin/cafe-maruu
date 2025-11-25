<?php
session_start(); // Ensure session is started
require_once '../config.php';
include '../inc/dashHeader.php';

// Check if bill_id is passed via GET, otherwise use the session
if (isset($_GET['bill_id'])) {
    $bill_id = $_GET['bill_id'];
    $_SESSION['current_bill_id'] = $bill_id; // Store bill_id in session
} else if (isset($_SESSION['current_bill_id'])) {
    $bill_id = $_SESSION['current_bill_id']; // Use bill_id from session
} else {
    // If no bill_id is provided and no session exists, create a new bill
    $bill_id = createNewBillRecord(null); // Pass null for table_id
    $_SESSION['current_bill_id'] = $bill_id; // Store the new bill_id in session
}

$table_id = isset($_GET['table_id']) ? $_GET['table_id'] : null; // Table ID is optional

function createNewBillRecord($table_id) {
    global $link; // Assuming $link is your database connection
    
    $bill_time = date('Y-m-d H:i:s');
    
    $insert_query = "INSERT INTO bills (table_id, bill_time) VALUES (" . ($table_id !== null ? "'$table_id'" : "NULL") . ", '$bill_time')";

    if ($link->query($insert_query) === TRUE) {
        return $link->insert_id; // Return the newly inserted bill_id
    } else {
        return false;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="../css/pos.css" />
    <!-- Add Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <!-- Add jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            margin-right: 20px;
            
        }
        #paymentOptionsSection a { margin-right: 12px; margin-top: 6px; }
        @media (max-width: 576px) { #paymentOptionsSection .btn { width: 100%; } }
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
        .cart-table {
            width: 100% !important;
            max-width: 100% !important;
            table-layout: fixed;
            word-wrap: break-word;
            margin: 0 auto;
            border-collapse: collapse;
            min-width: 720px; /* ensure horizontal scroll instead of cramping */
        }
        .cart-table th, .cart-table td { white-space: normal; overflow-wrap: anywhere; }
        .cart-table th.action-col, .cart-table td.action-col { width: 52px; text-align: center; }
        .btn-icon { display: inline-flex; align-items: center; justify-content: center; padding: 6px 8px; line-height: 1; }
        .btn-icon svg { width: 16px; height: 16px; fill: currentColor; }
        .addon-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            background-color: #fafafa;
        }
    
        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .container-fluid { padding-left: 16px; padding-right: 16px; }
            .half-section { margin-right: 0; }
        }
        @media (max-width: 767.98px) {
            .row > .half-section { width: 100%; }
            .scrollable-table { max-height: 320px; }
            /* On small screens, keep fixed layout and avoid wrapping; enable ellipsis for item names */
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
                <h2>Food & Drinks</h2>
                <div class="mb-3">
                    <form method="POST" action="#">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" required="" id="search" name="search" class="form-control" placeholder="Search Food & Drinks">
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-dark">Search</button>
                            </div>
                            <div class="col-md-3">
                                <button type="button" id="showAllButton" class="btn btn-light" onclick="toggleShowAll()">Show All</button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Display search results -->
                <div class="table-responsive scrollable-table">
                    <table class="table table-bordered mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Item Name</th>
                                <th>Price</th>
                                <th>Add</th>
                            </tr>
                        </thead>
                        <tbody id="searchResults">
                            <?php
                            $show_all = isset($_GET['show_all']) ? $_GET['show_all'] : "false";
                            $result = null; // Default: No query executed

                            if ($show_all === "true") {
                                // Show all menu and stock items when "Show All" is clicked
                                $query = "SELECT item_id, item_name, item_price, 'menu' AS source, NULL AS unit FROM menu 
                                          UNION 
                                          SELECT ItemID AS item_id, CONCAT(ItemName, ' (', BaseUnitName, ')') AS item_name, PricePerBaseUnit AS item_price, 'stock' AS source, 'base' AS unit FROM stock 
                                          UNION 
                                          SELECT ItemID AS item_id, CONCAT(ItemName, ' (', AggregateUnitName, ')') AS item_name, PricePerSubUnit AS item_price, 'stock' AS source, 'aggregate' AS unit FROM stock WHERE ConversionRatio > 1 
                                          ORDER BY item_id;";
                                $result = mysqli_query($link, $query);
                            } elseif (isset($_POST['search']) && !empty($_POST['search'])) {
                                // Perform search when a keyword is typed
                                $search = $_POST['search'];

                                $query = "SELECT item_id, item_name, item_price, 'menu' AS source, NULL AS unit FROM menu 
                                          WHERE item_type LIKE '%$search%' 
                                          OR item_category LIKE '%$search%' 
                                          OR item_name LIKE '%$search%' 
                                          OR item_id LIKE '%$search%' 
                                          UNION 
                                          SELECT ItemID AS item_id, CONCAT(ItemName, ' (', BaseUnitName, ')') AS item_name, PricePerBaseUnit AS item_price, 'stock' AS source, 'base' AS unit FROM stock 
                                          WHERE ItemName LIKE '%$search%' 
                                          UNION 
                                          SELECT ItemID AS item_id, CONCAT(ItemName, ' (', AggregateUnitName, ')') AS item_name, PricePerSubUnit AS item_price, 'stock' AS source, 'aggregate' AS unit FROM stock 
                                          WHERE ItemName LIKE '%$search%' AND ConversionRatio > 1 
                                          ORDER BY item_id;";
                                $result = mysqli_query($link, $query);
                            }

                            if ($result) {
                                if (mysqli_num_rows($result) > 0) {
                                    while ($row = mysqli_fetch_array($result)) {
                                        echo "<tr>";
                                        echo "<td>" . $row['item_id'] . "</td>";
                                        echo "<td>" . $row['item_name'] . "</td>";
                                        echo "<td>" . number_format($row['item_price'], 2) . "</td>";

                                        // Check if the bill has been paid
                                        $payment_time_query = "SELECT payment_time FROM bills WHERE bill_id = '$bill_id'";
                                        $payment_time_result = mysqli_query($link, $payment_time_query);
                                        $has_payment_time = false;

                                        if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
                                            $payment_time_row = mysqli_fetch_assoc($payment_time_result);
                                            if (!empty($payment_time_row['payment_time'])) {
                                                $has_payment_time = true;
                                            }
                                        }

                                        // Display the "Add to Cart" button if the bill hasn't been paid
                                        if (!$has_payment_time) {
                                            echo '<td><form method="get" action="addItem.php">'
                                                . ($table_id ? '<input type="text" hidden name="table_id" value="' . $table_id . '">' : '')
                                                . '<input type="text" name="item_id" value=' . $row['item_id'] . ' hidden>'
                                                . '<input type="text" name="source" value=' . $row['source'] . ' hidden>'
                                                . '<input type="text" name="unit" value=' . ($row['unit'] ?? 'base') . ' hidden>'
                                                . '<input type="number" name="bill_id" value=' . $bill_id . ' hidden>'
                                                . '<input type="number" name="quantity" style="width:120px" placeholder="1 to 1000" required min="1" max="1000">'
                                                . '<input type="hidden" name="addToCart" value="1">'
                                                . '<button type="submit" class="btn btn-primary">Add to Cart</button>';
                                            echo "</form></td>";
                                        } else {
                                            echo '<td>Bill Paid</td>';
                                        }

                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='4'>No items found.</td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='4'>Use search to find items.</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Right Half: Display Cart Items -->
            <div class="col-md-5 half-section">
                <h2>Cart</h2>
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
                            // Query to fetch cart items for the given bill_id
                            $cart_query = "SELECT bi.*, 
                                                  COALESCE(m.item_name, s.ItemName) AS item_name, 
                                                  COALESCE(m.item_price, 
                                                           CASE WHEN bi.unit = 'base' THEN s.PricePerBaseUnit ELSE s.PricePerSubUnit END) AS item_price
                                           FROM bill_items bi
                                           LEFT JOIN menu m ON bi.item_id = m.item_id AND bi.source = 'menu'
                                           LEFT JOIN stock s ON bi.item_id = s.ItemID AND bi.source = 'stock'
                                           WHERE bi.bill_id = '$bill_id'";
                            $cart_result = mysqli_query($link, $cart_query);

                            if ($cart_result && mysqli_num_rows($cart_result) > 0) {
                                while ($cart_row = mysqli_fetch_assoc($cart_result)) {
                                    $item_id = $cart_row['item_id'];
                                    $item_name = $cart_row['item_name'];
                                    $item_price = $cart_row['item_price'];
                                    $quantity = $cart_row['quantity'];
                                    $total = $item_price * $quantity;
                                    $bill_item_id = $cart_row['bill_item_id'];
                                    $cart_total += $total;
                                    echo '<tr>';
                                    echo '<td>' . $item_id . '</td>';
                                    echo '<td>' . $item_name . ' <span class="unit-badge">' . ($cart_row['unit'] ?? 'base') . '</span></td>';
                                    echo '<td>' . number_format($item_price, 2) . '</td>';
                                    echo '<td>' . $quantity . '</td>';
                                    echo '<td>' . number_format($total, 2) . '</td>';

                                    // Check if the bill has been paid
                                    $payment_time_query = "SELECT payment_time FROM bills WHERE bill_id = '$bill_id'";
                                    $payment_time_result = mysqli_query($link, $payment_time_query);
                                    $has_payment_time = false;

                                    if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
                                        $payment_time_row = mysqli_fetch_assoc($payment_time_result);
                                        if (!empty($payment_time_row['payment_time'])) {
                                            $has_payment_time = true;
                                        }
                                    }

                                    // Display the "Delete" button if the bill hasn't been paid
                                    if (!$has_payment_time) {
                                        echo '<td class="action-col">'
                                            . '<a class="btn btn-danger btn-icon" aria-label="Delete item" title="Delete item" href="deleteItem.php?bill_id=' . $bill_id . '&bill_item_id=' . $bill_item_id . '&table_id=' . $table_id . '&item_id=' . $item_id . '">'
                                            . '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 3h6a1 1 0 0 1 1 1v2h4a1 1 0 1 1 0 2h-1.1l-1.2 12.1A3 3 0 0 1 14.7 23H9.3a3 3 0 0 1-2.99-2.9L5.1 8H4a1 1 0 1 1 0-2h4V4a1 1 0 0 1 1-1Zm1 3h4V5h-4v1ZM7.1 8l1.1 11.1A1 1 0 0 0 9.3 20h5.4a1 1 0 0 0 1-.9L16.9 8H7.1ZM10 10a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Zm4 0a1 1 0 0 1 1 1v6a1 1 0 1 1-2 0v-6a1 1 0 0 1 1-1Z"/></svg>'
                                            . '</a>'
                                            . '</td>';
                                    } else {
                                        echo '<td>Bill Paid</td>';
                                    }
                                    echo '</tr>';
                                }
                            } else {
                                echo '<tr><td colspan="6">No Items in Cart.</td></tr>';
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
                            <th>Tip (10% auto)</th>
                            <td>TZS <?= number_format($cart_total * 0.10, 2) ?></td>
                        </tr>
                        <tr>
                            <th>Tax (18% auto)</th>
                            <td>TZS <?= number_format($cart_total * 0.18, 2) ?></td>
                        </tr>
                    </table>
                </div>

                <div class="addon-card">
                    <h5 class="mb-3">Optional Charges</h5>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="roomServiceAmount">Room Services (TZS)</label>
                            <input type="number" min="0" step="0.01" id="roomServiceAmount" class="form-control" placeholder="0.00" value="0">
                        </div>
                        <div class="form-group col-md-6">
                            <label for="deliveryAmount">Delivery Service (TZS)</label>
                            <input type="number" min="0" step="0.01" id="deliveryAmount" class="form-control" placeholder="0.00" value="0">
                        </div>
                    </div>
                    <small class="text-muted d-block">Tip (10%) and Tax (18%) are added automatically to every receipt.</small>
                </div>

                <?php
                // Check if the payment time record exists for the bill
                $payment_time_query = "SELECT payment_time FROM bills WHERE bill_id = '$bill_id'";
                $payment_time_result = mysqli_query($link, $payment_time_query);
                $has_payment_time = false;

                if ($payment_time_result && mysqli_num_rows($payment_time_result) > 0) {
                    $payment_time_row = mysqli_fetch_assoc($payment_time_result);
                    if (!empty($payment_time_row['payment_time'])) {
                        $has_payment_time = true;
                    }
                }

                // If payment time record exists, show the "Print Receipt" button
                if ($has_payment_time) {
                    unset($_SESSION['current_bill_id']); // Clear the session after payment
                    echo '<div>';
                    echo '<a href="receipt.php?bill_id=' . $bill_id . '" class="btn btn-light">Print Receipt <span class="fa fa-receipt text-black"></span></a></div>';
                } elseif ($cart_total > 0) {
                    // Display the "Pay Bill" and "Order Note" buttons
                    echo '<div class="d-flex justify-content-between flex-wrap" style="margin-top: 20px;">';
                    echo '<button type="button" class="btn btn-success" id="payBillButton" onclick="payBill()">Pay Bill</button>';
                    echo '<a href="orderNote.php?bill_id=' . $bill_id . '" class="btn btn-info" id="orderNoteButton" target="_blank">Order Note</a>';
                    echo '</div>';
                } else {
                    echo '<h3>Add Item To Cart to Proceed</h3>';
                }
                ?>

                <!-- Payment Options Section -->
                <div id="paymentOptionsSection" style="display: none; margin-top: 20px;">
                    <div class="mt-3 d-flex flex-wrap">
                        <button type="button" class="btn btn-success mr-2 mb-2" onclick="launchPayment('cash')">Cash</button>
                        <button type="button" class="btn btn-primary mr-2 mb-2" onclick="launchPayment('card')">Card | Mobile</button>
                        <button type="button" class="btn btn-creditors mr-2 mb-2" onclick="launchPayment('creditor')">Creditors</button>
                        <button type="button" class="btn btn-compo mb-2" onclick="launchPayment('compo')">Compo</button>
                    </div>
                </div>

                <form class="mt-3" action="newCustomer.php" method="get">
                    <input type="hidden" name="bill_id" value="<?= $bill_id ?>">
                    <button type="submit" name="new_customer" value="true" class="btn btn-warning">New Customer</button>
                </form>
            </div>
        </div>
    </div>

    <?php include '../inc/dashFooter.php'; ?>

    <script>
    function toggleShowAll() {
        let currentUrl = new URL(window.location.href);
        let showAll = currentUrl.searchParams.get("show_all");

        if (showAll === "true") {
            // Hide items (remove 'show_all' from URL)
            currentUrl.searchParams.set("show_all", "false");
        } else {
            // Show all items
            currentUrl.searchParams.set("show_all", "true");
        }

        // Redirect with updated URL
        window.location.href = currentUrl.toString();
    }

    function payBill() {
        // Hide the "Pay Bill" and "Order Note" buttons
        document.getElementById('payBillButton').style.display = 'none';
        document.getElementById('orderNoteButton').style.display = 'none';

        // Show the payment options section
        document.getElementById('paymentOptionsSection').style.display = 'block';
    }

    // Live search functionality
    $(document).ready(function() {
        $('#search').on('input', function() {
            var searchTerm = $(this).val();
            
            if (searchTerm.length >= 1) { // Only search if at least 1 character is entered
                $.ajax({
                    url: 'liveSearch.php',
                    type: 'POST',
                    data: { search: searchTerm, bill_id: '<?= $bill_id ?>' },
                    success: function(response) {
                        $('#searchResults').html(response);
                    }
                });
            } else if (searchTerm.length === 0) {
                // Clear results if search term is empty
                $('#searchResults').html('<tr><td colspan="4">Use search to find items.</td></tr>');
            }
        });
    });
        const paymentContext = {
            billId: '<?= $bill_id ?>',
            staffId: '<?= $_SESSION['logged_account_id'] ?? 1 ?>',
            memberId: '1',
            reservationId: '1120251'
        };

        const endpoints = {
            cash: 'posCashPayment.php',
            card: 'posCardPayment.php',
            creditor: 'posCreditors.php',
            compo: 'posCompo.php'
        };

        function launchPayment(type) {
            const roomInput = document.getElementById('roomServiceAmount');
            const deliveryInput = document.getElementById('deliveryAmount');
            const roomValue = parseFloat(roomInput.value) || 0;
            const deliveryValue = parseFloat(deliveryInput.value) || 0;

            if (roomValue < 0 || deliveryValue < 0) {
                alert('Additional charges cannot be negative.');
                return;
            }

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
                room_service_fee: roomValue.toFixed(2),
                delivery_fee: deliveryValue.toFixed(2)
            });

            window.location.href = `${endpoint}?${params.toString()}`;
        }
    </script>
</body>
</html>