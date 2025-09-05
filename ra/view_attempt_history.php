<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$search_name = isset($_GET['search_name']) ? $_GET['search_name'] : '';
$search_result = isset($_GET['search_result']) ? $_GET['search_result'] : '';

$query = "SELECT s.sind_id, s.sind_name, COUNT(q.attempt_id) AS attempt_count, MAX(q.attempt_score) AS highest_score
          FROM qt_attempt_hist q
          JOIN sinderellas s ON q.sind_id = s.sind_id
          WHERE 1=1";

if ($search_name) {
    $query .= " AND s.sind_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

if ($search_result) {
    if ($search_result == 'pass') {
        $query .= " AND s.sind_id IN (SELECT sind_id FROM qt_attempt_hist WHERE attempt_score >= 18)";
    } elseif ($search_result == 'fail') {
        $query .= " AND s.sind_id NOT IN (SELECT sind_id FROM qt_attempt_hist WHERE attempt_score >= 18)";
    }
}

$query .= " GROUP BY s.sind_id, s.sind_name ORDER BY s.sind_name ASC";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qualifier Attempt History - Admin - Sinderella</title>
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
            flex: 1;
            min-width: 200px;
        }
        .search-container button {
            margin-top: 0;
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
        /* #attemptHistoryTable td:nth-child(2),
        #attemptHistoryTable td:nth-child(3),
        #attemptHistoryTable td:nth-child(4) {
            text-align: center;
        } */
        #attemptHistoryTable td{
            text-align: center;
        }
        #attemptHistoryTable tbody tr {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Qualifier Attempt History</h2>
                <form method="GET" action="" class="search-container">
                    <div>
                        <label for="search_name">Name:</label>
                        <input type="text" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>">
                    </div>
                    <div>
                        <label for="search_result">Result:</label>
                        <select id="search_result" name="search_result">
                            <option value="">All</option>
                            <option value="pass" <?php if ($search_result == 'pass') echo 'selected'; ?>>Pass</option>
                            <option value="fail" <?php if ($search_result == 'fail') echo 'selected'; ?>>Fail</option>
                        </select>
                    </div>
                    <button type="submit">Search</button>
                    <button type="button" id="clear-button" onclick="clearSearch()">Clear Search</button>
                </form>
                <table id="attemptHistoryTable" class="display">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Attempt Count</th>
                            <th>Highest Score</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr onclick="window.location.href='view_user_attempts.php?sind_id=<?php echo $row['sind_id']; ?>'">
                                <td><?php echo htmlspecialchars($row['sind_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['attempt_count']); ?></td>
                                <td><?php echo htmlspecialchars($row['highest_score']); ?></td>
                                <td><?php echo $row['highest_score'] >= 18 ? 'Pass' : 'Fail'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#attemptHistoryTable').DataTable({
                "dom": '<"top"l>rt<"bottom"ip><"clear">',
                "order": [[0, "asc"]],
                "searching": false
            });
        });

        function clearSearch() {
            document.getElementById('search_name').value = '';
            document.getElementById('search_result').value = '';
            window.location.href = window.location.pathname;
        }
    </script>
</body>
</html>

<?php
// if ($result instanceof mysqli_result) {
//     $result->free();
// }
// if ($conn instanceof mysqli) {
//     // $conn->close();
// }
?>