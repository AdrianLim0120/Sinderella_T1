<?php
session_start();
if (!isset($_SESSION['sind_id'])) {
    header("Location: ../login_sind.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

$sind_id = $_SESSION['sind_id'];

// Retrieve 20 random questions
$questions = [];
$stmt = $conn->prepare("SELECT question_id, question_text, f_option0, f_option1, f_option2, f_option3 FROM qualifier_test ORDER BY RAND() LIMIT 20");
$stmt->execute();
$stmt->bind_result($question_id, $question_text, $f_option0, $f_option1, $f_option2, $f_option3);
while ($stmt->fetch()) {
    $options = [$f_option0, $f_option1, $f_option2, $f_option3];
    shuffle($options);
    $questions[] = [
        'question_id' => $question_id,
        'question_text' => $question_text,
        'options' => $options
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
    <title>Start Test - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_sind.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_sind.php'; ?>
            <div class="profile-container">
                <h2>Qualifier Test</h2>
                <form id="testForm" action="submit_test.php" method="POST">
                    <?php foreach ($questions as $index => $question) { ?>
                        <div class="question-block">
                            <p><strong>Question <?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($question['question_text']); ?></p>
                            <?php foreach ($question['options'] as $option) { ?>
                                <label>
                                    <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="<?php echo htmlspecialchars($option); ?>" required>
                                    <?php echo htmlspecialchars($option); ?>
                                </label><br>
                            <?php } ?>
                        </div>
                    <?php } ?>
                    <button type="submit">Submit Test</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>