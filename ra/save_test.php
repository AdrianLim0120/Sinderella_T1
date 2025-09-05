<?php
session_start();
if (!isset($_SESSION['adm_id'])) {
    header("Location: ../login_adm.php");
    exit();
}

// Database connection
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete questions
    if (!empty($_POST['deleted_questions'])) {
        $deletedQuestions = explode(',', $_POST['deleted_questions']);
        foreach ($deletedQuestions as $question_id) {
            $stmt = $conn->prepare("DELETE FROM qualifier_test WHERE question_id = ?");
            $stmt->bind_param("i", $question_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Update existing questions
    if (isset($_POST['questions'])) {
        foreach ($_POST['questions'] as $question_id => $question) {
            $stmt = $conn->prepare("UPDATE qualifier_test SET question_text = ?, f_option0 = ?, f_option1 = ?, f_option2 = ?, f_option3 = ? WHERE question_id = ?");
            $stmt->bind_param("sssssi", $question['question_text'], $question['f_option0'], $question['f_option1'], $question['f_option2'], $question['f_option3'], $question_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Insert new questions
    if (isset($_POST['new_questions'])) {
        foreach ($_POST['new_questions'] as $new_question) {
            $stmt = $conn->prepare("INSERT INTO qualifier_test (question_text, f_option0, f_option1, f_option2, f_option3) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $new_question['question_text'], $new_question['f_option0'], $new_question['f_option1'], $new_question['f_option2'], $new_question['f_option3']);
            $stmt->execute();
            $stmt->close();
        }
    }

    // $conn->close();
    header("Location: update_test.php?success=1");
    exit();
}
?>