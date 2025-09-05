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
$search_acc_approved = isset($_GET['search_acc_approved']) ? $_GET['search_acc_approved'] : '';

// Fetch active labels
$labels_result = $conn->query("SELECT slbl_id, slbl_name FROM sind_label WHERE slbl_status = 'active'");

$query = "SELECT s.sind_id, s.sind_name, s.sind_phno, s.sind_status, s.acc_approved,
                 GROUP_CONCAT(DISTINCT CONCAT(sa.area, ', ', sa.state) SEPARATOR '\n') AS service_areas,
                 GROUP_CONCAT(DISTINCT CONCAT(sl.slbl_name, '|', sl.slbl_color_code) SEPARATOR '\n') AS labels
          FROM sinderellas s
          LEFT JOIN sind_service_area sa ON s.sind_id = sa.sind_id
          LEFT JOIN sind_id_label sil ON s.sind_id = sil.sind_id
          LEFT JOIN sind_label sl ON sil.slbl_id = sl.slbl_id AND sl.slbl_status = 'active'
          WHERE 1=1";

if ($search_name) {
    $query .= " AND s.sind_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

if ($search_phno) {
    $query .= " AND s.sind_phno LIKE '%" . $conn->real_escape_string($search_phno) . "%'";
}

if ($search_area) {
    $query .= " AND (sa.area LIKE '%" . $conn->real_escape_string($search_area) . "%' OR sa.state LIKE '%" . $conn->real_escape_string($search_area) . "%')";
}

if ($search_status) {
    $query .= " AND s.sind_status = '" . $conn->real_escape_string($search_status) . "'";
}

if ($search_acc_approved) {
    $query .= " AND s.acc_approved = '" . $conn->real_escape_string($search_acc_approved) . "'";
}

if ($search_label) {
    $query .= " AND sl.slbl_name LIKE '%" . $conn->real_escape_string($search_label) . "%'";
}

$query .= " GROUP BY s.sind_id ORDER BY s.sind_name ASC";

$result = $conn->query($query);

$ratings = [];
$rating_result = $conn->query("SELECT sind_id, ROUND(AVG(rate),1) AS avg_rating FROM booking_ratings GROUP BY sind_id");
while ($r = $rating_result->fetch_assoc()) {
    $ratings[$r['sind_id']] = $r['avg_rating'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Sinderellas - Admin - Sinderella</title>
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
        #sinderellaTable td {
            text-align: center;
        }
        #sinderellaTable tbody tr {
            cursor: pointer;
        }
        #add-sinderella-button, #manage-labels-button, #register-downline-button, #view-affiliate-button {
            margin-top: 0;
            margin-bottom: 10px;
        }
        .label-badge {
            display: inline-block;
            padding: 5px 10px;
            /* border-radius: 5px; */
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
                <h2>View Sinderellas</h2>
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
                            <option value="pending" <?php if ($search_status == 'pending') echo 'selected'; ?>>Pending</option>
                        </select>
                    </div>
                    <div>
                        <label for="search_acc_approved">Account Approval:</label>
                        <select id="search_acc_approved" name="search_acc_approved">
                            <option value="">All</option>
                            <option value="pending" <?php if (isset($_GET['search_acc_approved']) && $_GET['search_acc_approved'] == 'pending') echo 'selected'; ?>>Pending</option>
                            <option value="approve" <?php if (isset($_GET['search_acc_approved']) && $_GET['search_acc_approved'] == 'approve') echo 'selected'; ?>>Approved</option>
                            <option value="reject" <?php if (isset($_GET['search_acc_approved']) && $_GET['search_acc_approved'] == 'reject') echo 'selected'; ?>>Reject</option>
                        </select>
                    </div>
                    <div>
                        <label for="search_label">Label:</label>
                        <select id="search_label" name="search_label">
                            <option value="">All</option>
                            <?php while ($label = $labels_result->fetch_assoc()): ?>
                                <option value="<?php echo htmlspecialchars($label['slbl_name']); ?>" <?php if ($search_label == $label['slbl_name']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($label['slbl_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="button-container">
                        <button type="submit">Search</button>
                        <button type="button" id="clear-button" onclick="clearSearch()">Clear Search</button>
                    </div>
                </form>
                <button type="button" id="register-downline-button" onclick="window.location.href='manage_downline.php'">Register Downline</button>
                <button type="button" id="add-sinderella-button" onclick="window.location.href='add_sinderella.php'">Add New Sinderella</button>
                <button type="button" id="manage-labels-button" onclick="window.location.href='manage_sind_labels.php'">Manage Labels</button>
                <!-- <button type="button" id="view-affiliate-button" onclick="window.location.href='view_affiliate.php'">View Affiliate</button> -->
                <table id="sinderellaTable" class="display">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Rating</th>
                            <th>Phone Number</th>
                            <th>Service Area</th>
                            <th>Label</th>
                            <th>Status</th>
                            <th>Acc. Approval</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick="window.location.href='edit_sinderella.php?sind_id=<?php echo $row['sind_id']; ?>'">
                                <td><?php echo htmlspecialchars($row['sind_name']); ?></td>
                                <td>
                                    <?php
                                    if (isset($ratings[$row['sind_id']])) {
                                        echo '<span style="color:#F09E0B;font-size:16px;">&#11088;' . $ratings[$row['sind_id']] . '</span>';
                                    } else {
                                        echo '<span style="color:#888;font-size:14px;">No ratings</span>';
                                    }
                                    ?>
                                <td><?php echo htmlspecialchars($row['sind_phno']); ?></td>
                                <td><?php echo nl2br(htmlspecialchars($row['service_areas'] ?? 'N/A')); ?></td>
                                <td>
                                    <?php
                                    if (!empty($row['labels'])) {
                                        $labels = explode("\n", $row['labels']);
                                        foreach ($labels as $label) {
                                            list($label_name, $label_color) = explode('|', $label);
                                            echo '<span class="label-badge" style="background-color: ' . htmlspecialchars($label_color) . ';">' . htmlspecialchars($label_name) . '</span>';
                                        }
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['sind_status']); ?></td>
                                <td>
                                    <?php
                                    if ($row['acc_approved'] == 'approve') {
                                        echo '<span style="color:green;font-weight:bold;">Approved</span>';
                                    } elseif ($row['acc_approved'] == 'reject') {
                                        echo '<span style="color:red;font-weight:bold;">Rejected</span>';
                                    } elseif ($row['acc_approved'] == 'pending') {
                                        echo '<span style="color:orange;font-weight:bold;">Pending</span>';
                                    } else {
                                        echo htmlspecialchars($row['acc_approved']);
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
            $('#sinderellaTable').DataTable({
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
            document.getElementById('search_acc_approved').value = '';
            document.getElementById('search_label').value = '';
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>

<?php
// // $conn->close();
?>