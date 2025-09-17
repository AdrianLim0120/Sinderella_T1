<?php
// session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];
$stmt = $conn->prepare("SELECT sind_status, acc_approved FROM sinderellas WHERE sind_id = ?");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($sind_status, $acc_approved);
$stmt->fetch();
$stmt->close();
// $conn->close();

$current_page = basename($_SERVER['PHP_SELF']);
$submenu_pages = [
    'viewIncomeSubMenu' => ['individual_income.php', 'group_commission.php'],
];
$open_submenus = [];
foreach ($submenu_pages as $submenu_id => $pages) {
    if (in_array($current_page, $pages)) {
        $open_submenus[$submenu_id] = true;
    }
}
?>

<div class="menu" id="menu">
    <!-- <div class="close-menu" id="closeMenu">Close Menu</div> -->
    <h3>Sinderella Menu</h3>
    <ul>
        <?php if ($sind_status == 'active' && $acc_approved == 'approve') { ?>
            <!-- <li><a href="view_bookings.php">View Bookings</a></li> -->
            <li><a href="dashboard_main.php">Dashboard</a></li>
            <li>
                <a href="#" id="viewBookingsMenu">Bookings <span id="viewBookingsIcon"><?php echo in_array($current_page, ['view_bookings.php']) && isset($_GET['search_status']) ? '-' : '+'; ?></span></a>
                <ul id="viewBookingsSubMenu" style="display: <?php echo ($current_page == 'view_bookings.php') ? 'block' : 'none'; ?>;">
                    <li><a href="view_bookings.php">All Bookings</a></li>
                    <li><a href="view_bookings.php?search_date=&search_area=&search_status=paid">To Confirm</a></li>
                    <li><a href="view_bookings.php?search_date=&search_area=&search_status=confirm">Confirmed</a></li>
                    <li><a href="view_bookings.php?search_date=&search_area=&search_status=done">To Rate</a></li>
                    <li><a href="view_bookings.php?search_date=&search_area=&search_status=rated">Rated</a></li>
                    <li><a href="view_bookings.php?search_date=&search_area=&search_status=cancel">Cancelled</a></li>
                    <!-- <li><a href="view_bookings.php?search_date=&search_area=&search_status=rejected">Rejected</a></li> -->
                </ul>
            </li>

            <li><a href="manage_schedule.php">Manage Schedule</a></li>

            <li><a href="service_area.php">Service Area</a></li>

            <li><a href="manage_downline.php">Manage Downline</a></li>

            <li>
                <a href="#" id="viewIncomeMenu">View Income <span id="viewIncomeIcon"><?php echo !empty($open_submenus['viewIncomeSubMenu']) ? '-' : '+'; ?></span></a>
                <ul id="viewIncomeSubMenu" style="display: <?php echo !empty($open_submenus['viewIncomeSubMenu']) ? 'block' : 'none'; ?>;">
                    <li><a href="individual_income.php">Individual Income</a></li>
                    <li><a href="group_commission.php">Group Commission</a></li>
                </ul>
            </li>

            <li><a href="dashboard.php">Top Performance</a></li>
            <!-- <li><a href="#" style="color:grey;">Top Performance</a></li> -->

        <?php } else if ($sind_status == 'pending') { ?>
            
            <li><a href="qualifier_test.php">Qualifier Test</a></li>
        <?php } ?>

        <li><a href="manage_profile.php">Manage Profile</a></li>
    </ul>
</div>

<div class="floating-toggle" id="menuToggle"><</div>

<script>
document.getElementById('viewIncomeMenu').addEventListener('click', function() {
    var subMenu = document.getElementById('viewIncomeSubMenu');
    var icon = document.getElementById('viewIncomeIcon');
    if (subMenu.style.display === 'none') {
        subMenu.style.display = 'block';
        icon.textContent = '-';
    } else {
        subMenu.style.display = 'none';
        icon.textContent = '+';
    }
});

document.getElementById('viewBookingsMenu').addEventListener('click', function() {
    var subMenu = document.getElementById('viewBookingsSubMenu');
    var icon = document.getElementById('viewBookingsIcon');
    if (subMenu.style.display === 'none') {
        subMenu.style.display = 'block';
        icon.textContent = '-';
    } else {
        subMenu.style.display = 'none';
        icon.textContent = '+';
    }
});

document.getElementById('menuToggle').addEventListener('click', function() {
    var menu = document.getElementById('menu');
    var mainContainer = document.querySelector('.main-container');
    var header = document.querySelector('.header');
    var toggleIcon = document.getElementById('menuToggle');
    if (menu.style.transform === 'translateX(0px)') {
        menu.style.transform = 'translateX(-100%)';
        mainContainer.style.marginLeft = '0';
        header.style.width = '100%';
        toggleIcon.textContent = '>';
        toggleIcon.style.left = '10px';
    } else {
        menu.style.transform = 'translateX(0px)';
        mainContainer.style.marginLeft = '200px';
        header.style.width = 'calc(100% - 200px)';
        toggleIcon.textContent = '<';
        toggleIcon.style.left = '210px';
    }
});

document.getElementById('closeMenu').addEventListener('click', function() {
    var menu = document.getElementById('menu');
    var mainContainer = document.querySelector('.main-container');
    var header = document.querySelector('.header');
    var toggleIcon = document.getElementById('menuToggle');
    menu.style.transform = 'translateX(-100%)';
    mainContainer.style.marginLeft = '0';
    header.style.width = '100%';
    toggleIcon.textContent = '>';
    toggleIcon.style.left = '10px';
});
</script>