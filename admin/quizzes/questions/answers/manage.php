<?php
session_start();
require_once '../../../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../../login.php");
    exit();
}

if (!isset($_GET['question_id'])) {
    header("Location: ../../index.php");
    exit();
}

$question_id = $_GET['question_id'];

// Get question and quiz details
$stmt = $pdo->prepare("
    SELECT q.*, qz.title as quiz_title 
    FROM questions q 
    JOIN quizzes qz ON q.quiz_id = qz.id 
    WHERE q.id = ?
");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    header("Location: ../../index.php");
    exit();
}

// Get answers for this question
$stmt = $pdo->prepare("SELECT * FROM answers WHERE question_id = ? ORDER BY id");
$stmt->execute([$question_id]);
$answers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Answers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .answer-card {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            margin: 5px;
        }

        .btn-edit {
            background-color: #2196F3;
        }

        .btn-delete {
            background-color: #f44336;
        }

        .correct-badge {
            background-color: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            margin-left: 10px;
        }
    </style>
</head>

<body>
    <h1>Manage Answers</h1>
    <h2>Quiz: <?= htmlspecialchars($question['quiz_title']) ?></h2>
    <h3>Question: <?= htmlspecialchars($question['question_text']) ?></h3>

    <a href="../manage.php?quiz_id=<?= $question['quiz_id'] ?>" class="btn">Back to Questions</a>
    <a href="create.php?question_id=<?= $question_id ?>" class="btn">Add New Answer</a>

    <?php foreach ($answers as $answer): ?>
        <div class="answer-card">
            <div>
                <?= htmlspecialchars($answer['answer_text']) ?>
                <?php if ($answer['is_correct']): ?>
                    <span class="correct-badge">Correct Answer</span>
                <?php endif; ?>
            </div>
            <div>
                <a href="edit.php?id=<?= $answer['id'] ?>" class="btn btn-edit">Edit</a>
                <a href="delete.php?id=<?= $answer['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($answers)): ?>
        <p>No answers added yet. Click "Add New Answer" to create your first answer.</p>
    <?php endif; ?>
</body>

</html>