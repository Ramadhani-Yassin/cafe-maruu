<?php
session_start(); // Ensure session is started
require_once '../config.php';
include '../inc/dashHeader.php'; 

$bill_id = $_GET['bill_id'];
$staff_id = $_GET['staff_id'];
$member_id = intval($_GET['member_id']);
$reservation_id = $_GET['reservation_id'];
// Strict validation: tip_percentage must be between 0 and 10, never exceed 10
$tip_percentage_input = isset($_GET['tip_percentage']) ? max(0, min(10, floatval($_GET['tip_percentage']))) : 0;
$room_service_fee = isset($_GET['room_service_fee']) ? max(0, floatval($_GET['room_service_fee'])) : 0;
$delivery_fee = isset($_GET['delivery_fee']) ? max(0, floatval($_GET['delivery_fee'])) : 0;
$tax_rate = 0.18;

// Fetch creditors from the database
$creditors_query = "SELECT ID, Name FROM creditors";
$creditors_result = mysqli_query($link, $creditors_query);

?>

<style>
    .top-spacer {
        margin-top: 12rem;
        padding-top: 2rem;
    }
</style>

<div class="container-fluid top-spacer">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Creditors</h3>
                </div>
                <div class="card-body">
                    <h5>Bill ID: <?php echo $bill_id; ?></h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Item ID</th>
                                    <th>Item Name</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Query to fetch cart items for the given bill_id from both bill_items and PendingOrderItems
                                $cart_query = "
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
                                $cart_result = mysqli_query($link, $cart_query);
                                $cart_total = 0;

                                if ($cart_result && mysqli_num_rows($cart_result) > 0) {
                                    while ($cart_row = mysqli_fetch_assoc($cart_result)) {
                                        $item_id = $cart_row['item_id'];
                                        $item_name = $cart_row['item_name'];
                                        $item_price = $cart_row['item_price'];
                                        $quantity = $cart_row['quantity'];
                                        $total = $item_price * $quantity;
                                        $cart_total += $total;
                                        echo '<tr>';
                                        echo '<td>' . $item_id . '</td>';
                                        echo '<td>' . $item_name . '</td>';
                                        echo '<td>TZS ' . number_format($item_price, 2) . '</td>';
                                        echo '<td>' . $quantity . '</td>';
                                        echo '<td>TZS ' . number_format($total, 2) . '</td>';
                                        echo '</tr>';
                                    }
                                } else {
                                    echo '<tr><td colspan="6">No Items in Cart.</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    <hr>
                    <?php
                        $tax_amount = $cart_total * $tax_rate;
                        // ALWAYS calculate tip_amount from tip_percentage * cart_total
                        // Formula: tip_amount = (tip_percentage / 100) * cart_total
                        // tip_percentage is already validated to be between 0 and 10
                        $tip_amount = ($tip_percentage_input / 100) * $cart_total;
                        $GRANDTOTAL = $cart_total + $tax_amount + $tip_amount + $room_service_fee + $delivery_fee;
                    ?>
                    <div class="text-right">
                        <?php 
                        echo "<strong>Subtotal:</strong> TZS " . number_format($cart_total, 2) . "<br>";
                        echo "<strong>Tax (18%):</strong> TZS " . number_format($tax_amount, 2) . "<br>";
                        echo "<strong>Tip (10%):</strong> TZS " . number_format($tip_amount, 2) . "<br>";
                        echo "<strong>Room Services:</strong> TZS " . number_format($room_service_fee, 2) . "<br>";
                        echo "<strong>Delivery Service:</strong> TZS " . number_format($delivery_fee, 2) . "<br>";
                        echo "<strong>Grand Total:</strong> TZS " . number_format($GRANDTOTAL, 2);
                        ?>
                    </div>
                    <!-- Creditor Selection Form -->
                    <div class="text-center mt-4">
                        <form id="creditorForm" method="post">
                            <div class="form-group">
                                <label for="creditor">Select Creditor:</label>
                                <select class="form-control" id="creditor" name="creditor" required>
                                    <option value="">-- Select Creditor --</option>
                                    <?php while ($creditor = mysqli_fetch_assoc($creditors_result)): ?>
                                        <option value="<?= $creditor['ID'] ?>"><?= $creditor['Name'] ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div><br>
                            <input type="hidden" name="room_service_fee" value="<?php echo $room_service_fee; ?>">
                            <input type="hidden" name="delivery_fee" value="<?php echo $delivery_fee; ?>">
                            <button type="submit" name="pay_done" class="btn btn-dark">Print Receipt 🧾</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_done'])) {
    $creditor_id = $_POST['creditor'];
    $room_service_fee = isset($_POST['room_service_fee']) ? max(0, floatval($_POST['room_service_fee'])) : $room_service_fee;
    $delivery_fee = isset($_POST['delivery_fee']) ? max(0, floatval($_POST['delivery_fee'])) : $delivery_fee;
    $currentTime = date('Y-m-d H:i:s');

    // Update the bill with creditor information
    $updateQuery = "UPDATE bills SET payment_method = 'creditor', payment_time = '$currentTime',
                    staff_id = $staff_id, member_id = $member_id, reservation_id = $reservation_id,
                    creditor_id = $creditor_id WHERE bill_id = $bill_id;";

    if ($link->query($updateQuery) === TRUE) {
        $tax_amount = $cart_total * $tax_rate;
        // ALWAYS calculate tip_amount from tip_percentage * cart_total
        // Formula: tip_amount = (tip_percentage / 100) * cart_total
        // Never accept tip_amount directly - always calculate from percentage
        $tip_amount = ($tip_percentage_input / 100) * $cart_total;
        $GRANDTOTAL = $cart_total + $tax_amount + $tip_amount + $room_service_fee + $delivery_fee;
        // Update the creditor's due amount
        $updateCreditorQuery = "UPDATE creditors SET Due_Amount = Due_Amount + $GRANDTOTAL WHERE ID = $creditor_id;";
        $link->query($updateCreditorQuery);

        // Record the payment for reporting purposes
        $recordPaymentQuery = "INSERT INTO payment_records 
                              (bill_id, payment_method, payment_amount, payment_time, staff_id, member_id, tax_amount, tip_amount, delivery_fee, room_service_fee, tax_rate)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $link->prepare($recordPaymentQuery);
        $payment_method = 'creditor';
        $stmt->bind_param(
            "isdsiiddddd",
            $bill_id,
            $payment_method,
            $GRANDTOTAL,
            $currentTime,
            $staff_id,
            $member_id,
            $tax_amount,
            $tip_amount,
            $delivery_fee,
            $room_service_fee,
            $tax_rate
        );
        $stmt->execute();

        // JavaScript for automatic receipt download
        echo '<script>
                setTimeout(function() {
                    window.location.href = "receipt.php?bill_id=' . $bill_id . '";
                }, 1000);
              </script>';
    } else {
        echo "Error updating bill: " . $link->error;
    }
}
?>

<?php include '../inc/dashFooter.php'; ?>