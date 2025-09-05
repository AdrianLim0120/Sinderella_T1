<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

// Retrieve all questions and options
$questions = [];
$stmt = $conn->prepare("SELECT question_id, question_text, f_option0, f_option1, f_option2, f_option3 FROM qualifier_test");
$stmt->execute();
$stmt->bind_result($question_id, $question_text, $f_option0, $f_option1, $f_option2, $f_option3);
while ($stmt->fetch()) {
    $questions[] = [
        'question_id' => $question_id,
        'question_text' => $question_text,
        'f_option0' => $f_option0,
        'f_option1' => $f_option1,
        'f_option2' => $f_option2,
        'f_option3' => $f_option3
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
    <title>Update Questions - Sinderella</title>
    <link rel="icon" href="../img/sinderella_favicon.png"/>
    <link rel="stylesheet" href="../includes/css/styles_user.css">
    <script src="../includes/js/update_test.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('success') === '1') {
                alert('Questions saved successfully!');
            }
        });
    </script>
    <style>
        .question-block {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            position: relative;
        }
        .question-block label {
            display: block;
            margin-top: 10px;
        }
        .question-block textarea,
        .question-block input {
            width: 98%;
            padding: 5px;
            margin-top: 5px;
        }
        .delete-button {
        }
    </style>
</head>
<body>
    <div class="main-container">
        <?php include '../includes/menu/menu_adm.php'; ?>
        <div class="content-container">
            <?php include '../includes/header_adm.php'; ?>
            <div class="profile-container">
                <h2>Update Questions</h2>
                <form id="updateTestForm" action="save_test.php" method="POST">
                    <input type="hidden" name="deleted_questions" id="deletedQuestions">
                    <div id="questionsContainer">
                        <?php foreach ($questions as $index => $question) { ?>
                            <div class="question-block" data-question-id="<?php echo $question['question_id']; ?>">
                                <button type="button" class="delete-button" style="
                                            position: absolute;
                                            top: 1px;
                                            right: 2%;
                                            background-color: red;
                                            color: white;
                                            border: none;
                                            border-radius: 5px;
                                            width: 25px;
                                            height: 25px;
                                            cursor: pointer;
                                            padding: unset;
                                            "><b></b>X</button>
                                <label>Question <?php echo $index + 1; ?>:</label>
                                <textarea name="questions[<?php echo $question['question_id']; ?>][question_text]" required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                                <label>Correct Option:</label>
                                <input type="text" name="questions[<?php echo $question['question_id']; ?>][f_option0]" value="<?php echo htmlspecialchars($question['f_option0']); ?>" required>
                                <label>False Option 1:</label>
                                <input type="text" name="questions[<?php echo $question['question_id']; ?>][f_option1]" value="<?php echo htmlspecialchars($question['f_option1']); ?>" required>
                                <label>False Option 2:</label>
                                <input type="text" name="questions[<?php echo $question['question_id']; ?>][f_option2]" value="<?php echo htmlspecialchars($question['f_option2']); ?>" required>
                                <label>False Option 3:</label>
                                <input type="text" name="questions[<?php echo $question['question_id']; ?>][f_option3]" value="<?php echo htmlspecialchars($question['f_option3']); ?>" required>
                            </div>
                        <?php } ?>
                    </div>
                    <button type="button" id="addQuestionButton">Add Question</button>
                    <button type="submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>