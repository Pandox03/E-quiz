<?php
session_start();
require_once '../../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

if (!isset($_GET['quiz_id'])) {
    header("Location: ../index.php");
    exit();
}

$quiz_id = $_GET['quiz_id'];

// Get quiz details
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: ../index.php");
    exit();
}

// Get questions with their answers
$stmt = $pdo->prepare("
    SELECT q.*, 
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id AND is_correct = 1) as correct_answer_count
    FROM questions q 
    WHERE q.quiz_id = ?
    ORDER BY q.id
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?= htmlspecialchars($quiz['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .question-card {
            border: 1px solid #ddd;
            margin: 10px 0;
            padding: 15px;
            border-radius: 4px;
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

        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        .badge-warning {
            background-color: #ff9800;
            color: white;
        }

        .badge-success {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>

<body>
    <h1>Manage Questions - <?= htmlspecialchars($quiz['title']) ?></h1>
    <a href="../index.php" class="btn">Back to Quizzes</a>
    <a href="create.php?quiz_id=<?= $quiz_id ?>" class="btn">Add New Question</a>

    <?php foreach ($questions as $question): ?>
        <div class="question-card">
            <h3>
                <?= htmlspecialchars($question['question_text']) ?>
                <span class="badge <?= $question['answer_count'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                    <?= $question['answer_count'] ?> answers
                </span>
                <span class="badge <?= $question['correct_answer_count'] > 0 ? 'badge-success' : 'badge-warning' ?>">
                    <?= $question['correct_answer_count'] ?> correct
                </span>
            </h3>
            <p>Points: <?= $question['points'] ?></p>

            <a href="edit.php?id=<?= $question['id'] ?>" class="btn btn-edit">Edit Question</a>
            <a href="delete.php?id=<?= $question['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
            <a href="answers/manage.php?question_id=<?= $question['id'] ?>" class="btn">Manage Answers</a>
        </div>
    <?php endforeach; ?>

    <?php if (empty($questions)): ?>
        <p>No questions added yet. Click "Add New Question" to create your first question.</p>
    <?php endif; ?>
</body>

</html>