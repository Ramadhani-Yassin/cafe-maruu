
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title>Dashboard - SB Admin</title>
        <link href="../css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script> 
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <style>
            html, body { overflow-x: hidden; width: 100%; }
            .container, .container-fluid { max-width: 100%; }
            /* Ensure the top nav isn't clipping content and has a slightly taller height */
            .sb-topnav { min-height: 64px; }
            #layoutSidenav_content { padding-top: 64px !important; }
            /* Prevent brand from forcing horizontal scroll; scale down on very small screens */
            .navbar-brand { white-space: nowrap; }
            @media (max-width: 400px) { .navbar-brand { font-size: 0.95rem; } }
            /* Remove unintended left gaps on small and medium screens */
            @media (max-width: 991.98px) {
                #layoutSidenav_content { margin-left: 0 !important; }
                .container-fluid { padding-left: 16px !important; padding-right: 16px !important; }
                .wrapper { padding-left: 16px !important; padding-right: 16px !important; }
                .pl-600 { padding-left: 0 !important; }
                .m-50 { margin: 0 !important; }
            }
            /* Further reduce left spacing on tablets/smaller laptops */
            @media (max-width: 1199.98px) {
                #layoutSidenav_content { margin-left: 0 !important; }
                .container-fluid { padding-left: 20px !important; padding-right: 20px !important; }
                .wrapper { padding-left: 20px !important; padding-right: 20px !important; }
                .pl-600 { padding-left: 0 !important; }
                .m-50 { margin-left: 0 !important; }
            }
            /* Mobile navbar toggle - larger, glowing, always visible */
            #sidebarToggle.mobile-nav-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 44px;
                height: 44px;
                padding: 0;
                border-radius: 999px;
                border: 1px solid rgba(255,255,255,0.35);
                color: #fff !important;
                background: rgba(255,255,255,0.08);
                box-shadow: 0 0 8px rgba(255,255,255,0.35), 0 0 16px rgba(52,211,153,0.25);
                backdrop-filter: blur(2px);
                z-index: 1051;
            }
            #sidebarToggle.mobile-nav-toggle i { font-size: 1.35rem; }
            @media (max-width: 360px) { #sidebarToggle.mobile-nav-toggle { width: 40px; height: 40px; } #sidebarToggle.mobile-nav-toggle i { font-size: 1.2rem; } }
            @media (min-width: 360px) { #sidebarToggle.mobile-nav-toggle { width: 48px; height: 48px; } #sidebarToggle.mobile-nav-toggle i { font-size: 1.5rem; } }
            @media (min-width: 480px) { #sidebarToggle.mobile-nav-toggle { width: 52px; height: 52px; } #sidebarToggle.mobile-nav-toggle i { font-size: 1.6rem; } }
            #sidebarToggle.mobile-nav-toggle:hover {
                box-shadow: 0 0 10px rgba(255,255,255,0.55), 0 0 22px rgba(16,185,129,0.45);
                background: rgba(255,255,255,0.12);
                border-color: rgba(255,255,255,0.55);
            }
            #sidebarToggle.mobile-nav-toggle:focus { outline: 2px solid rgba(255,255,255,0.6); outline-offset: 2px; }
            /* Ensure the brand text glows and stays visible */
            .glow-brand { text-shadow: 0 0 6px rgba(255,255,255,0.7); }
            @media (max-width: 767.98px) {
                .glow-brand { text-shadow: 0 0 10px rgba(255,255,255,0.85); }
            }
        </style>

    </head>
    <body class="sb-nav-fixed">
        <nav class="sb-topnav navbar navbar-expand-lg navbar-dark bg-dark">
            <!-- Navbar Brand-->
            <a class="navbar-brand ps-3 glow-brand" href="../posBackend/orderItem.php">Migude Restaurant Staff Panel</a>
            <!-- Single Toggle aligned right on small screens -->
            <button class="btn btn-link btn-sm ms-auto me-2 d-lg-none mobile-nav-toggle" id="sidebarToggle" href="#!" aria-label="Toggle sidebar"><i class="fas fa-bars"></i></button>
            
        </nav>
        <div id="layoutSidenav">
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <div class="sb-sidenav-menu">
                        <div class="nav">
                            <div class="sb-sidenav-menu-heading">Main</div>
                            <a class="nav-link" href="../panel/menu-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-utensils"></i></div>
                                Menu
                            </a>

                           <!--<a class="nav-link" href="../panel/reservation-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-book"></i></div>
                                Reservations
                            </a>-->

                            <a class="nav-link" href="../posBackend/orderItem.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                                Pay Bills
                            </a>

                            <a class="nav-link" href="../panel/pendingOrders.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-person-shelter"></i></div>
                                Orders | Tables
                            </a>

                              <!--<a class="nav-link" href="../panel/pos-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-cash-register"></i></div>
                                Tables
                            </a>-->

                            <a class="nav-link" href="../panel/kitchen-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-kitchen-set"></i></div>
                                Kitchen
                            </a>


                            <a class="nav-link" href="../panel/creditors-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-person-shelter"></i></div>
                                Creditors
                            </a>

                            

                            <a class="nav-link" href="../panel/stock-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-receipt"></i></div>
                                Stock
                            </a>

                            <a class="nav-link" href="../panel/bill-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-receipt"></i></div>
                                All Receipts
                            </a>

                            <a class="nav-link" href="../panel/staff-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-people-group"></i></div>
                                Staff Details
                            </a>
                            

                            
                            <a class="nav-link" href="../panel/table-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-table-cells"></i></div>
                                Tables Management
                            </a>
                    
                            <!--
                            <a class="nav-link" href="../panel/account-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-eye"></i></div>
                                View All Accounts
                            </a>
                            -->
                         
                            <div class="sb-sidenav-menu-heading">Report & Analytics</div>
                            <a class="nav-link" href="../panel/charts-Sales.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-fire"></i></div>
                                Items Sales
                            </a>
                            <a class="nav-link" href="../panel/salesReport.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>
                                Revenue Statistics
                            </a>
                            <a class="nav-link" href="../panel/profiles-panel.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                Creditors Statistics
                            </a>
                            <a class="nav-link" href="../StaffLogin/logout.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-key"></i></div>
                                Log out
                            </a>
                            
                            
                            
                        </div>
                    </div>
                        <div class="sb-sidenav-footer">
                            <div class="small">Logged in as:</div>
                                <?php
                                // Check if the session variables are set
                                if (isset($_SESSION['logged_account_id']) && isset($_SESSION['logged_staff_name'])) {
                                    // Display the logged-in staff ID and name
                                    echo "Staff ID: " . $_SESSION['logged_account_id'] . "<br>";
                                    echo "Staff Name: " . $_SESSION['logged_staff_name'];
                                    
                                } else {
                                    // If session variables are not set, display a default message or handle as needed
                                    echo "Not logged in";
                                }
                                ?>
                        </div>
                </nav>
            </div>
        </<div>
            <div id="content-for-template">Content</div> 
        
        <script src="../js/scripts.js" type="text/javascript"></script>
