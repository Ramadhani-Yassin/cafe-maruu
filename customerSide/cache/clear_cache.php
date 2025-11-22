<?php
/**
 * Cache Management Script
 * Use this to clear cached data when menu items are updated
 */

// Clear menu cache
$cache_file = 'menu_cache.json';
if (file_exists($cache_file)) {
    unlink($cache_file);
    echo "Menu cache cleared successfully!<br>";
} else {
    echo "No menu cache found.<br>";
}

// Clear user session cache (this will be cleared on next page load)
echo "User session cache will be refreshed on next page load.<br>";

echo "<br><a href='javascript:history.back()'>Go Back</a>";
?> 