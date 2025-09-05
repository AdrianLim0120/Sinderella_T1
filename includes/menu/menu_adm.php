<?php
// session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$current_adm_id = $_SESSION['adm_id'];
$stmt = $conn->prepare("SELECT adm_role FROM admins WHERE adm_id = ?");
$stmt->bind_param("i", $current_adm_id);
$stmt->execute();
$stmt->bind_result($current_adm_role);
$stmt->fetch();
$stmt->close();
// $conn->close();

$current_page = basename($_SERVER['PHP_SELF']);

// Map submenu IDs to their related pages
$submenu_pages = [
    'manageAccountSubMenu' => ['view_admins.php', 'view_customers.php', 'view_sinderellas.php'],
    'managePaymentSubMenu' => ['personal_income.php', 'group_commission.php'],
    'manageTestSubMenu'    => ['update_test.php', 'view_attempt_history.php'],
];

// Determine which submenu should be open
$open_submenus = [];
foreach ($submenu_pages as $submenu_id => $pages) {
    if (in_array($current_page, $pages)) {
        $open_submenus[$submenu_id] = true;
    }
}
?>

<div class="menu" id="menu">
    <!-- <div class="close-menu" id="closeMenu">Close Menu</div> -->
    <h3>Admin Menu</h3>
    <ul>
        <li><a href="dashboard.php">Dashboard</a></li>
        <!-- <li><a href="#" style="color:grey;">Dashboard</a></li> -->

        <!-- <li><a href="view_bookings.php">Bookings</a></li> -->
        <li>
            <a href="#" class="menu-item" data-target="manageBookingsSubMenu">
                Bookings <span class="menu-icon"><?php echo ($current_page == 'view_bookings.php') ? '-' : '+'; ?></span>
            </a>
            <ul id="manageBookingsSubMenu" class="submenu" style="display: <?php echo ($current_page == 'view_bookings.php') ? 'block' : 'none'; ?>;">
                <li><a href="#">Add Booking</a></li>
                <li><a href="view_bookings.php">All Bookings</a></li>
                <!-- <li><a href="view_bookings.php?search_status=pending">To Pay</a></li>
                <li><a href="view_bookings.php?search_status=paid">Paid</a></li>
                <li><a href="view_bookings.php?search_status=rejected">Rejected</a></li>
                <li><a href="view_bookings.php?search_status=confirm">Confirmed</a></li>
                <li><a href="view_bookings.php?search_status=done">To Rate</a></li>
                <li><a href="view_bookings.php?search_status=rated">Rated</a></li>
                <li><a href="view_bookings.php?search_status=cancel">Cancelled</a></li> -->
            </ul>
        </li>

        <li><a href="view_schedule.php">Schedule</a></li>

        <li>
            <a href="#" class="menu-item" data-target="manageAccountSubMenu">
                Accounts <span class="menu-icon"><?php echo !empty($open_submenus['manageAccountSubMenu']) ? '-' : '+'; ?></span>
            </a>
            <ul id="manageAccountSubMenu" class="submenu" style="display: <?php echo !empty($open_submenus['manageAccountSubMenu']) ? 'block' : 'none'; ?>;">
                <?php if ($current_adm_role == 'Senior Admin') { ?>
                    <li><a href="view_admins.php">Admin</a></li>
                <?php } ?>
                <li><a href="view_customers.php">Customer</a></li>
                <li><a href="view_sinderellas.php">Sinderella</a></li>
            </ul>
        </li>

        <?php if ($current_adm_role == 'Senior Admin') { ?>
        <li>
            <a href="#" class="menu-item" data-target="managePaymentSubMenu">
                Payment <span class="menu-icon"><?php echo !empty($open_submenus['managePaymentSubMenu']) ? '-' : '+'; ?></span>
            </a>
            <ul id="managePaymentSubMenu" class="submenu" style="display: <?php echo !empty($open_submenus['managePaymentSubMenu']) ? 'block' : 'none'; ?>;">
                <li><a href="personal_income.php">Personal Income</a></li>
                <li><a href="group_commission.php">Group Commission</a></li>
            </ul>
        </li>
        <?php } ?>

        <li>
            <a href="#" class="menu-item" data-target="manageTestSubMenu">
                Qualifier Test <span class="menu-icon"><?php echo !empty($open_submenus['manageTestSubMenu']) ? '-' : '+'; ?></span>
            </a>
            <ul id="manageTestSubMenu" class="submenu" style="display: <?php echo !empty($open_submenus['manageTestSubMenu']) ? 'block' : 'none'; ?>;">
                <li><a href="update_test.php">Update Questions</a></li>
                <li><a href="view_attempt_history.php">View Attempt History</a></li>
            </ul>
        </li>

        <?php if ($current_adm_role == 'Senior Admin') { ?>
        <li><a href="manage_pricing.php">Pricing</a></li>
        <?php } ?>

        <!-- <li><a href="top_performance.php">Top Performance</a></li> -->
        <!-- <li><a href="#" style="color:grey;">Top Performance</a></li> -->

        <li><a href="manage_profile.php">Manage Profile</a></li>
    </ul>
</div>

<div class="floating-toggle" id="menuToggle"><</div>

<script>
document.querySelectorAll('.menu-item').forEach(function(item) {
    item.addEventListener('click', function() {
        var target = this.getAttribute('data-target');
        var subMenu = document.getElementById(target);
        var icon = this.querySelector('.menu-icon');

        // Collapse all other submenus
        document.querySelectorAll('.submenu').forEach(function(menu) {
            if (menu.id !== target) {
                menu.style.display = 'none';
                menu.previousElementSibling.querySelector('.menu-icon').textContent = '+';
            }
        });

        // Toggle the selected submenu
        if (subMenu.style.display === 'none') {
            subMenu.style.display = 'block';
            icon.textContent = '-';
        } else {
            subMenu.style.display = 'none';
            icon.textContent = '+';
        }
    });
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