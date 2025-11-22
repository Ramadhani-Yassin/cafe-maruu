<?php
// Check if setup has already been completed
if (file_exists('setup_completed.flag')) {
    // Setup is complete, redirect to home page
    header("Location: customerSide/home/home.php");
    exit();
} else {
    // Setup not completed, show setup page or redirect to admin
    header("Location: adminSide/StaffLogin/login.php");
    exit();
}
?>