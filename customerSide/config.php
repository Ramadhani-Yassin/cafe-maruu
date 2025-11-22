<?php 
// Remember to change the username, password and database name to actual values
define('DB_HOST','localhost');
define('DB_USER','root'); 
define('DB_PASS','');
define('DB_NAME','restaurantdb');

// Database connection optimization
$link = null;

function getDBConnection() {
    global $link;
    
    if ($link === null || $link->ping() === false) {
        // Close existing connection if it exists and is not responding
        if ($link !== null) {
            $link->close();
        }
        
        // Create new connection with optimized settings
        $link = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        // Check connection
        if ($link->connect_error) {
            die('Connection Failed: ' . $link->connect_error);
        }
        
        // Optimize connection settings
        $link->query("SET SESSION sql_mode = ''"); // Remove strict mode for better compatibility
        $link->query("SET SESSION wait_timeout = 28800"); // 8 hours timeout
        $link->query("SET SESSION interactive_timeout = 28800"); // 8 hours interactive timeout
        
        // Note: Query cache was deprecated in MySQL 5.7 and removed in MySQL 8.0
        // Using connection pooling and other optimization methods instead
    }
    
    return $link;
}

// Initialize connection
$link = getDBConnection();

// Function to close connection when needed
function closeDBConnection() {
    global $link;
    if ($link !== null) {
        $link->close();
        $link = null;
    }
}

// Register shutdown function to close connection
register_shutdown_function('closeDBConnection');
?> 