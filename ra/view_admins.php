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
$search_role = isset($_GET['search_role']) ? $_GET['search_role'] : '';
$search_status = isset($_GET['search_status']) ? $_GET['search_status'] : '';

$query = "SELECT adm_id, adm_name, adm_role, adm_phno, adm_status FROM admins WHERE 1=1";

if ($search_name) {
    $query .= " AND adm_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

if ($search_phno) {
    $query .= " AND adm_phno LIKE '%" . $conn->real_escape_string($search_phno) . "%'";
}

if ($search_role) {
    $query .= " AND adm_role = '" . $conn->real_escape_string($search_role) . "'";
}

if ($search_status) {
    $query .= " AND adm_status = '" . $conn->real_escape_string($search_status) . "'";
}

$query .= " ORDER BY adm_name ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Admins - Admin - Sinderella</title>
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
        #adminTable td {
            text-align: center;
        }
        #adminTable tbody tr {
            cursor: pointer;
        }
        #add-admin-button {
            margin-top: 0;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>View Admins</h2>
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
                        <label for="search_role">Role:</label>
                        <select id="search_role" name="search_role">
                            <option value="">All</option>
                            <option value="Junior Admin" <?php if ($search_role == 'Junior Admin') echo 'selected'; ?>>Junior Admin</option>
                            <option value="Senior Admin" <?php if ($search_role == 'Senior Admin') echo 'selected'; ?>>Senior Admin</option>
                        </select>
                    </div>
                    <div>
                        <label for="search_status">Status:</label>
                        <select id="search_status" name="search_status">
                            <option value="">All</option>
                            <option value="active" <?php if ($search_status == 'active') echo 'selected'; ?>>Active</option>
                            <option value="inactive" <?php if ($search_status == 'inactive') echo 'selected'; ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="button-container">
                        <button type="submit">Search</button>
                        <button type="button" id="clear-button" onclick="clearSearch()">Clear Search</button>
                    </div>
                </form>
                <button type="button" id="add-admin-button" onclick="window.location.href='add_admin.php'">Add New Admin</button>
                <table id="adminTable" class="display">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Phone Number</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick="window.location.href='edit_admin.php?adm_id=<?php echo $row['adm_id']; ?>'">
                                <td><?php echo htmlspecialchars($row['adm_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['adm_role']); ?></td>
                                <td><?php echo htmlspecialchars($row['adm_phno']); ?></td>
                                <td><?php echo htmlspecialchars($row['adm_status']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#adminTable').DataTable({
                "dom": '<"top"l>rt<"bottom"ip><"clear">',
                "order": [[0, "asc"]],
                "searching": false
            });
        });

        function clearSearch() {
            document.getElementById('search_name').value = '';
            document.getElementById('search_phno').value = '';
            document.getElementById('search_role').value = '';
            document.getElementById('search_status').value = '';
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>

<?php
// // $conn->close();
?>