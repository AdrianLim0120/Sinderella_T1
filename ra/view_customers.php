<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$search_phno = isset($_GET['search_phno']) ? $_GET['search_phno'] : '';
$search_area = isset($_GET['search_area']) ? $_GET['search_area'] : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';
$search_label = isset($_GET['search_label']) ? $_GET['search_label'] : '';

// Fetch active labels
$labels_result = $conn->query("SELECT clbl_id, clbl_name FROM cust_label WHERE clbl_status = 'active'");

$query = "SELECT c.cust_id, c.cust_name, c.cust_phno, c.cust_status, 
                 GROUP_CONCAT(DISTINCT CONCAT(ca.cust_area, ', ', ca.cust_state) SEPARATOR '\n') AS area_state,
                 GROUP_CONCAT(DISTINCT CONCAT(cl.clbl_name, '|', cl.clbl_color_code) SEPARATOR '\n') AS labels
            FROM customers c
            LEFT JOIN cust_id_label cil ON c.cust_id = cil.cust_id
            LEFT JOIN cust_label cl ON cil.clbl_id = cl.clbl_id AND cl.clbl_status = 'active'
            LEFT JOIN cust_addresses ca ON c.cust_id = ca.cust_id
            WHERE 1=1";

if ($search_name) {
    $query .= " AND c.cust_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

if ($search_phno) {
    $query .= " AND c.cust_phno LIKE '%" . $conn->real_escape_string($search_phno) . "%'";
}

if ($search_area) {
    $query .= " AND (ca.cust_area LIKE '%" . $conn->real_escape_string($search_area) . "%' OR ca.cust_state LIKE '%" . $conn->real_escape_string($search_area) . "%')";
}

if ($search_status) {
    $query .= " AND c.cust_status = '" . $conn->real_escape_string($search_status) . "'";
}

if ($search_label) {
    $query .= " AND cl.clbl_name LIKE '%" . $conn->real_escape_string($search_label) . "%'";
}

$query .= " GROUP BY c.cust_id ORDER BY c.cust_name ASC";

$result = $conn->query($query);

// $customer_ratings = [];
// $rating_result = $conn->query("SELECT cust_id, ROUND(AVG(rate),1) AS avg_rating FROM cust_ratings GROUP BY cust_id");
// while ($r = $rating_result->fetch_assoc()) {
//     $customer_ratings[$r['cust_id']] = $r['avg_rating'];
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customers - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
        .search-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 20px;
            background-color: #f9f9f9;
        }
        .search-container div {
            min-width: 200px;
        }
        .search-container button {
            margin-top: 0;
        }
        .button-container {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        #clear-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
        }
        #clear-button:hover {
            background-color: #d32f2f;
        }
        .dataTables_wrapper .dataTables_length {
            float: left;
        }
        .dataTables_wrapper .dataTables_filter {
            display: none;
        }
        .dataTables_wrapper .dataTables_info {
            float: left;
            margin-top: 10px;
        }
        .dataTables_wrapper .dataTables_paginate {
            float: right;
            margin-top: 10px;
        }
        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.5em 1em;
        }
        #customerTable td {
            text-align: center;
        }
        #customerTable tbody tr {
            cursor: pointer;
        }
        #add-customer-button, #manage-labels-button {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .label-badge {
            display: inline-block;
            padding: 5px 10px;
            color: white;
            margin: 2px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>View Customers</h2>
                <form method="GET" action="" class="search-container">
                    <div>
                        <label for="search_name">Name:</label>
                        <input type="text" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
                    </div>
                    <div>
                        <label for="search_phno">Phone Number:</label>
                        <input type="text" id="search_phno" name="search_phno" value="<?php echo htmlspecialchars($search_phno); ?>">
                    </div>
                    <div>
                        <label for="search_area">Service Area:</label>
                        <input type="text" id="search_area" name="search_area" value="<?php echo htmlspecialchars($search_area); ?>">
                    </div>
                    <div>
                        <label for="search_status">Status:</label>
                        <select id="search_status" name="search_status">
                            <option value="">All</option>
                            <option value="active" <?php if ($search_status == 'active') echo 'selected'; ?>>Active</option>
                            <option value="inactive" <?php if ($search_status == 'inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label for="search_label">Label:</label>
                        <select id="search_label" name="search_label">
                            <option value="">All</option>
                            <?php while ($label = $labels_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($label['clbl_name']); ?>" <?php if ($search_label == $label['clbl_name']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($label['clbl_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="button-container">
                        <button type="submit">Search</button>
                        <button type="button" id="clear-button" onclick="clearSearch()">Clear Search</button>
                    </div>
                </form>
                <button type="button" id="add-customer-button" onclick="window.location.href='add_customer.php'">Add New Customer</button>
                <button type="button" id="manage-labels-button" onclick="window.location.href='manage_cust_labels.php'">Manage Labels</button>
                <table id="customerTable" class="display">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <!-- <th>Rating</th> -->
                            <th>Phone Number</th>
                            <th>Service Area</th>
                            <th>Status</th>
                            <th>Label</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick="window.location.href='edit_customer.php?cust_id=<?php echo $row['cust_id']; ?>'">
                                <td><?php echo htmlspecialchars($row['cust_name']); ?></td>

                                <!-- <td>
                                    <?php
                                    if (isset($customer_ratings[$row['cust_id']])) {
                                        echo '<span style="color:#F09E0B;font-size:16px;">&#11088;' . $customer_ratings[$row['cust_id']] . '</span>';
                                    } else {
                                        echo '<span style="color:#888;font-size:14px;">No ratings</span>';
                                    }
                                    ?>
                                </td> -->

                                <td><?php echo htmlspecialchars($row['cust_phno']); ?></td>
                                <td>
                                    <?php
                                    if (!empty($row['area_state'])) {
                                        $areas = explode("\n", $row['area_state']);
                                        foreach ($areas as $area) {
                                            echo htmlspecialchars($area) . '<br>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['cust_status']); ?></td>
                                <td>
                                    <?php
                                    if (!empty($row['labels'])) {
                                        $labels = explode("\n", $row['labels']);
                                        foreach ($labels as $label) {
                                            list($label_name, $label_color) = explode('|', $label);
                                            echo '<span class="label-badge" style="background-color: ' . htmlspecialchars($label_color) . ';">' . htmlspecialchars($label_name) . '</span>';
                                        }
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#customerTable').DataTable({
                "dom": '<"top"l>rt<"bottom"ip><"clear">',
                "order": [[0, "asc"]],
                "searching": false
            });
        });

        function clearSearch() {
            document.getElementById('search_name').value = '';
            document.getElementById('search_phno').value = '';
            document.getElementById('search_area').value = '';
            document.getElementById('search_status').value = '';
            document.getElementById('search_label').value = '';
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>

<?php
// // $conn->close();
?>