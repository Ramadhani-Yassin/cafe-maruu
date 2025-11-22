<?php
/**
 * Performance Monitor
 * Use this to monitor page load times and database performance
 */

session_start();
require_once 'config.php';

// Start timing
$start_time = microtime(true);

// Monitor database queries
$query_count = 0;
$query_time = 0;

// Function to monitor queries
function monitorQuery($sql, $start_time) {
    global $query_count, $query_time;
    
    $query_start = microtime(true);
    $result = mysqli_query($GLOBALS['link'], $sql);
    $query_end = microtime(true);
    
    $query_count++;
    $query_time += ($query_end - $query_start);
    
    return $result;
}

// Test database performance
echo "<h2>Performance Monitor</h2>";
echo "<p>Testing database connection and query performance...</p>";

// Test connection
$conn_start = microtime(true);
$link = getDBConnection();
$conn_end = microtime(true);
$conn_time = $conn_end - $conn_start;

echo "<p><strong>Database Connection Time:</strong> " . number_format($conn_time * 1000, 2) . " ms</p>";

// Test menu queries
$menu_start = microtime(true);
$sqlmainDishes = "SELECT * FROM menu WHERE item_category = 'Main Dishes' ORDER BY item_type LIMIT 5";
$resultmainDishes = monitorQuery($sqlmainDishes, $start_time);
$menu_end = microtime(true);
$menu_time = $menu_end - $menu_start;

echo "<p><strong>Menu Query Time:</strong> " . number_format($menu_time * 1000, 2) . " ms</p>";

// Test user data query
$user_start = microtime(true);
$test_account_id = 1; // Test with a sample account ID
$user_query = "SELECT member_name, points FROM memberships WHERE account_id = $test_account_id LIMIT 1";
$user_result = monitorQuery($user_query, $start_time);
$user_end = microtime(true);
$user_time = $user_end - $user_start;

echo "<p><strong>User Data Query Time:</strong> " . number_format($user_time * 1000, 2) . " ms</p>";

// Overall performance
$total_time = microtime(true) - $start_time;
echo "<p><strong>Total Execution Time:</strong> " . number_format($total_time * 1000, 2) . " ms</p>";
echo "<p><strong>Total Queries:</strong> $query_count</p>";
echo "<p><strong>Total Query Time:</strong> " . number_format($query_time * 1000, 2) . " ms</p>";

// Performance recommendations
echo "<h3>Performance Recommendations:</h3>";
if ($total_time > 0.1) {
    echo "<p style='color: red;'>⚠️ Page load time is slow. Consider implementing more aggressive caching.</p>";
} elseif ($total_time > 0.05) {
    echo "<p style='color: orange;'>⚠️ Page load time is moderate. Some optimization may help.</p>";
} else {
    echo "<p style='color: green;'>✅ Page load time is good!</p>";
}

if ($query_count > 3) {
    echo "<p style='color: orange;'>⚠️ Consider reducing the number of database queries.</p>";
}

echo "<br><a href='javascript:history.back()'>Go Back</a>";
?> 