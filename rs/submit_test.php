<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];
$answers = $_POST['answers'];
$score = 0;

// Calculate score
foreach ($answers as $question_id => $answer) {
    $stmt = $conn->prepare("SELECT f_option0 FROM qualifier_test WHERE question_id = ?");
    $stmt->bind_param("i", $question_id);
    $stmt->execute();
    $stmt->bind_result($correct_answer);
    $stmt->fetch();
    if ($answer == $correct_answer) {
        $score++;
    }
    $stmt->close();
}

// Record attempt
$stmt = $conn->prepare("INSERT INTO qt_attempt_hist (sind_id, attempt_date, attempt_score) VALUES (?, NOW(), ?)");
$stmt->bind_param("ii", $sind_id, $score);
$stmt->execute();
$stmt->close();

// Update status if passed
if ($score >= 18) {
    $stmt = $conn->prepare("UPDATE sinderellas SET sind_status = 'active' WHERE sind_id = ?");
    $stmt->bind_param("i", $sind_id);
    $stmt->execute();
    $stmt->close();
}

// $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Result - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="profile-container">
                <h2>Test Result</h2>
                <p>Your score: <?php echo $score; ?>/20</p>
                <?php if ($score >= 18) { ?>
                    <p>Congratulations! You have passed the test and your status has been updated to active.</p>
                    <a href="manage_profile.php">Back to Profile</a>
                <?php } else { ?>
                    <p>Unfortunately, you did not pass the test. Please try again.</p>
                    <a href="qualifier_test.php">Back to Qualifier Test</a>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>