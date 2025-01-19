<?php
session_start();
require_once '../connection.php';

// Check if user is logged in and has user role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Check if quiz ID is provided
if (!isset($_GET['id'])) {
    header("Location: available_quizzes.php");
    exit();
}

$quiz_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // Check if quiz exists and is published
    $stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ? AND status = 'published'");
    $stmt->execute([$quiz_id]);
    $quiz = $stmt->fetch();

    if (!$quiz) {
        throw new Exception("Quiz not found or not published");
    }

    // Check for existing in-progress attempt
    $stmt = $pdo->prepare("
        SELECT id FROM quiz_attempts 
        WHERE user_id = ? AND quiz_id = ? AND status = 'in_progress'
    ");
    $stmt->execute([$user_id, $quiz_id]);
    $existing_attempt = $stmt->fetch();

    if ($existing_attempt) {
        // Redirect to the quiz if there's an existing attempt
        header("Location: take_quiz.php?id=" . $quiz_id);
        exit();
    }

    // Create new quiz attempt
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempts (user_id, quiz_id, start_time, status) 
        VALUES (?, ?, CURRENT_TIMESTAMP, 'in_progress')
    ");
    $stmt->execute([$user_id, $quiz_id]);

    $pdo->commit();

    // Redirect to take quiz page
    header("Location: take_quiz.php?id=" . $quiz_id);
    exit();
} catch (Exception $e) {
    $pdo->rollBack();

    // Store error message in session
    $_SESSION['error'] = "Unable to start quiz: " . $e->getMessage();
    header("Location: available_quizzes.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Starting Quiz...</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f4f4f4;
        }

        .loading {
            text-align: center;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <div class="loading">
        <div class="spinner"></div>
        <p>Starting quiz, please wait...</p>
    </div>
</body>

</html>