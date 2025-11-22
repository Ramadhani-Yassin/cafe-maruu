<?php
session_start(); // Ensure session is started
require_once '../posBackend/checkIfLoggedIn.php';
require_once '../../error_handler.php'; // Include custom error handler
?>
<?php 
include '../inc/dashHeader.php'; 
require_once '../config.php';
$currentMonthStart = date('Y-m-01');
$currentMonthEnd = date('Y-m-t');

// Get the current month and year in the format 'YYYY-MM'
$currentMonth = date('Y-m');


?>

<style>
    .wrapper{ width: 100%; max-width: 1300px; padding-left: 200px; padding-top: 20px }
    .table-responsive { width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; }
    #mostPurchased, #mostPurchasedMain, #mostPurchasedSide { width: 100% !important; max-width: 100%; height: 500px; }
    @media (max-width: 767.98px) {
        .wrapper{ padding-left: 16px; padding-right: 16px; }
        .container-fluid{ padding-left: 0; padding-right: 0; }
        .col-md-10{ margin-left: 0 !important; }
        #mostPurchased, #mostPurchasedMain, #mostPurchasedSide { height: 320px !important; }
    }
    .glow-brand { text-shadow: 0 0 6px rgba(255,255,255,0.7); }
    @media (max-width: 767.98px){ .glow-brand { text-shadow: 0 0 10px rgba(255,255,255,0.85); } }
</style>

<div class="wrapper">
<div class="row">
        <div class="col-md-10 order-md-2" style="margin-top: 3rem; margin-left: 14rem;">
            <div class="container-fluid pt-5  row">
            <h3>Most Purchased Items</h3>
            <h3>(<?php echo $currentMonth; ?>)</h3>

            <!-- Sorting form and button -->
             <div class="col d-flex justify-content-end">
                 
                 <!-- 
                <a href="?sortOrder=desc" class=" btn btn-primary">Most</a>

                <a href="?sortOrder=asc" class=" btn btn-primary">Least</a>
                Ascending sort button -->
            </div>
            <div>
                <?php
                // Get the sorting order from the form or use default (ascending)
                $sortOrder = isset($_GET['sortOrder']) ? $_GET['sortOrder'] : 'desc';

                // Get the first and last day of the current month
                

                // Modify the SQL query for menu item sales to consider the current month
                $menuItemSalesQuery = "SELECT menu.item_name, SUM(bill_items.quantity) AS total_quantity
                                       FROM bill_items
                                       INNER JOIN menu ON bill_items.item_id = menu.item_id
                                       INNER JOIN bills ON bill_items.bill_id = bills.bill_id
                                       WHERE bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                       GROUP BY menu.item_name
                                       ORDER BY total_quantity $sortOrder";

                $menuItemSalesResult = mysqli_query($link, $menuItemSalesQuery);
                if (!$menuItemSalesResult) {
                    handleDatabaseError("Failed to fetch menu item sales: " . mysqli_error($link));
                }

                echo '<div class="table-responsive">';
                echo '<table class="table mb-0" >';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Item Name</th>';
                echo '<th>Units</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';

                while ($row = mysqli_fetch_assoc($menuItemSalesResult)) {
                    echo '<tr>';
                    echo '<td>' . $row['item_name'] . '</td>';
                    echo '<td>' . $row['total_quantity'] . '</td>';
                    echo '</tr>';
                }

                echo '</tbody>';
                echo '</table>';
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 order-md-1 col" style="margin-top: 3rem; margin-left: 5rem;">
            <div class="container pt-3 row">
                <!-- Add a div for Google Charts -->
                <div id="mostPurchased" style="width: 113%; max-width: 1000px; height: 500px;"></div>
            </div>
            <div class="container pt-3 row">
                <!-- Add a div for Google Charts -->
                <div id="mostPurchasedMain" style="width: 113%; max-width: 1000px; height: 500px;"></div>
            </div>
            <div class="container pt-3 row">
                <!-- Add a div for Google Charts -->
                <!--<div id="mostPurchasedDrinks" style="width: 113%; max-width: 1000px; height: 500px;"></div>-->
            </div>
            <div class="container pt-3 row">
                <!-- Add a div for Google Charts -->
                <div id="mostPurchasedSide" style="width: 113%; max-width: 1000px; height: 500px;"></div>
            </div>
        </div>
    </div>
    </div>
</div>

</div>



<!-- Load Google Charts library -->
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script>
    google.charts.load('current', {'packages': ['corechart']});
    google.charts.setOnLoadCallback(mostPurchasedChart);
    google.charts.setOnLoadCallback(mostPurchasedDrinksChart);
    google.charts.setOnLoadCallback(mostPurchasedMainChart);
    google.charts.setOnLoadCallback(mostPurchasedSideChart);

    function mostPurchasedChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedItemsQuery = "SELECT menu.item_name, SUM(bill_items.quantity) AS total_quantity
                                        FROM bill_items
                                        INNER JOIN menu ON bill_items.item_id = menu.item_id
                                        INNER JOIN bills ON bill_items.bill_id = bills.bill_id
                                        WHERE bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                        GROUP BY menu.item_name
                                        ORDER BY total_quantity DESC
                                        LIMIT 10";
            $topPurchasedItemsResult = mysqli_query($link, $topPurchasedItemsQuery);
            if (!$topPurchasedItemsResult) {
                handleDatabaseError("Failed to fetch top purchased items: " . mysqli_error($link));
            }

            while ($row = mysqli_fetch_assoc($topPurchasedItemsResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20, // 12, 18 whatever you want (don't specify px)
                bold: true    // true or false
            },
            title: 'Top 10 Most Purchased Items - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchased'));
        chart.draw(data, options);
    }
    function mostPurchasedDrinksChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedDrinksQuery = "SELECT menu.item_name, SUM(bill_items.quantity) AS total_quantity
                                        FROM bill_items
                                        INNER JOIN menu ON bill_items.item_id = menu.item_id
                                        INNER JOIN bills ON bill_items.bill_id = bills.bill_id
                                        WHERE bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                        AND menu.item_category = 'Drinks'
                                        GROUP BY menu.item_name
                                        ORDER BY total_quantity DESC
                                        LIMIT 10";
            $topPurchasedItemsResult = mysqli_query($link, $topPurchasedItemsQuery);
            if (!$topPurchasedItemsResult) {
                handleDatabaseError("Failed to fetch top purchased drinks: " . mysqli_error($link));
            }

            while ($row = mysqli_fetch_assoc($topPurchasedItemsResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20, // 12, 18 whatever you want (don't specify px)
                bold: true    // true or false
            },
            title: 'Top 10 Most Purchased Drinks - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchasedDrinks'));
        chart.draw(data, options);
    }
    function mostPurchasedMainChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedMainDishesQuery = "SELECT menu.item_name, SUM(bill_items.quantity) AS total_quantity
                                            FROM bill_items
                                            INNER JOIN menu ON bill_items.item_id = menu.item_id
                                            INNER JOIN bills ON bill_items.bill_id = bills.bill_id
                                            WHERE bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                            AND menu.item_category = 'Main Dishes'
                                            GROUP BY menu.item_name
                                            ORDER BY total_quantity DESC
                                            LIMIT 10";
            $topPurchasedItemsResult = mysqli_query($link, $topPurchasedItemsQuery);
            if (!$topPurchasedItemsResult) {
                handleDatabaseError("Failed to fetch top purchased main dishes: " . mysqli_error($link));
            }

            while ($row = mysqli_fetch_assoc($topPurchasedItemsResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20, // 12, 18 whatever you want (don't specify px)
                bold: true    // true or false
            },
            title: 'Top 10 Most Purchased Main Dishes - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchasedMain'));
        chart.draw(data, options);
    }
    function mostPurchasedSideChart() {
        const data = google.visualization.arrayToDataTable([
            ['Item Name', 'Total Quantity'],
            <?php
            $topPurchasedSideSnacksQuery = "SELECT menu.item_name, SUM(bill_items.quantity) AS total_quantity
                                            FROM bill_items
                                            INNER JOIN menu ON bill_items.item_id = menu.item_id
                                            INNER JOIN bills ON bill_items.bill_id = bills.bill_id
                                            WHERE bills.bill_time BETWEEN '$currentMonthStart 00:00:00' AND '$currentMonthEnd 23:59:59'
                                            AND menu.item_category = 'Side Snacks'
                                            GROUP BY menu.item_name
                                            ORDER BY total_quantity DESC
                                            LIMIT 10";
            $topPurchasedItemsResult = mysqli_query($link, $topPurchasedItemsQuery);
            if (!$topPurchasedItemsResult) {
                handleDatabaseError("Failed to fetch top purchased side snacks: " . mysqli_error($link));
            }

            while ($row = mysqli_fetch_assoc($topPurchasedItemsResult)) {
                echo "['{$row['item_name']}', {$row['total_quantity']}],";
            }
            ?>
        ]);

        const options = {
            titleTextStyle: {
                fontSize: 20, // 12, 18 whatever you want (don't specify px)
                bold: true    // true or false
            },
            title: 'Top 10 Most Purchased Side Snacks - <?php echo date('F Y'); ?>',
            is3D: true
        };

        const chart = new google.visualization.PieChart(document.getElementById('mostPurchasedSide'));
        chart.draw(data, options);
    }
</script>
