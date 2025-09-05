<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Retrieve attempt history
$attempts = [];
$stmt = $conn->prepare("SELECT attempt_date, attempt_score FROM qt_attempt_hist WHERE sind_id = ? ORDER BY attempt_date DESC");
$stmt->bind_param("i", $sind_id);
$stmt->execute();
$stmt->bind_result($attempt_date, $attempt_score);
while ($stmt->fetch()) {
    $attempts[] = [
        'attempt_date' => $attempt_date,
        'attempt_score' => $attempt_score
    ];
}
$stmt->close();
// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qualifier Test - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <script src="../includes/js/qualifier_test.js" defer></script>
    <style>
        .attempt-history-table {
            /* width: 100%; */
            border-collapse: collapse;
            margin-top: 20px;
        }
        .attempt-history-table th, .attempt-history-table td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }
        .attempt-history-table th {
            background-color: #0c213b;
            color: white;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="profile-container">
                <h2>Qualifier Test</h2>
                <div class="instructions">
                    <h3>Instructions</h3>
                    <p>Please read the following instructions carefully before starting the test:</p>
                    <ul>
                        <li>The test consists of 20 multiple-choice questions.</li>
                        <li>You need to score at least 18 out of 20 to pass the test.</li>
                        <li>Each question has one correct answer and three incorrect answers.</li>
                        <li>You can attempt the test multiple times if you do not pass.</li>
                    </ul>
                </div>
                <button id="startTestButton">Start Test</button>
                <h3>Attempt History</h3>
                <table class="attempt-history-table">
                    <thead>
                        <tr>
                            <th>Attempt Date</th>
                            <th>Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attempts as $attempt) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attempt['attempt_date']); ?></td>
                                <td><?php echo htmlspecialchars($attempt['attempt_score']); ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>