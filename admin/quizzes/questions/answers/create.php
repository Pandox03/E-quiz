<?php
// create_answer.php
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

// Get question details
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $answer_text = trim($_POST['answer_text']);
    $is_correct = isset($_POST['is_correct']) ? 1 : 0;

    if (empty($answer_text)) {
        $error = "Answer text is required";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
            $stmt->execute([$question_id, $answer_text, $is_correct]);
            header("Location: manage.php?question_id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error adding answer: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Answer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            max-width: 800px;
            margin: 20px auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            margin-right: 10px;
        }

        .btn-cancel {
            background-color: #f44336;
        }

        .checkbox-group {
            margin-top: 10px;
        }

        .question-text {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <h1>Add Answer</h1>
    <h2>Quiz: <?= htmlspecialchars($question['quiz_title']) ?></h2>
    <div class="question-text">
        <strong>Question:</strong> <?= htmlspecialchars($question['question_text']) ?>
    </div>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="answer_text">Answer Text:</label>
            <textarea name="answer_text" id="answer_text" rows="3" required><?= isset($_POST['answer_text']) ? htmlspecialchars($_POST['answer_text']) : '' ?></textarea>
        </div>

        <div class="checkbox-group">
            <label>
                <input type="checkbox" name="is_correct" value="1" <?= isset($_POST['is_correct']) ? 'checked' : '' ?>>
                This is the correct answer
            </label>
        </div>

        <button type="submit" class="btn">Add Answer</button>
        <a href="manage.php?question_id=<?= $question_id ?>" class="btn btn-cancel">Cancel</a>
    </form>
</body>

</html>