<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = isset($_GET['sind_id']) ? $_GET['sind_id'] : 0;

$query = "SELECT s.sind_name, q.attempt_date, q.attempt_score
          FROM qt_attempt_hist q
          JOIN sinderellas s ON q.sind_id = s.sind_id
          WHERE q.sind_id = ?
          ORDER BY q.attempt_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$result = $stmt->get_result();
$sind_name = '';
if ($row = $result->fetch_assoc()) {
    $sind_name = $row['sind_name'];
    $result->data_seek(0); // Reset result pointer
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($sind_name); ?>'s Attempt History - Admin - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <style>
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
        #attemptHistoryTable td:nth-child(3) {
            text-align: center;
        } */
        #attemptHistoryTable td {
            text-align: center;
        }
        .back-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 5px;
            margin-top: 20px;
        }
        .back-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2><?php echo htmlspecialchars($sind_name); ?>'s Attempt History</h2>
                <table id="attemptHistoryTable" class="display">
                    <thead>
                        <tr>
                            <th>Attempt Date</th>
                            <th>Score</th>
                            <th>Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['attempt_date']); ?></td>
                                <td><?php echo htmlspecialchars($row['attempt_score']); ?></td>
                                <td><?php echo $row['attempt_score'] >= 18 ? 'Pass' : 'Fail'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <button class="back-button" onclick="window.location.href='view_attempt_history.php'">Back</button>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#attemptHistoryTable').DataTable({
                "dom": '<"top"l>rt<"bottom"ip><"clear">',
                "order": [[0, "desc"]],
                "searching": false
            });
        });
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