<?php
session_start();
require_once '../../../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../../index.php");
    exit();
}

$answer_id = $_GET['id'];

// Get answer and related details
$stmt = $pdo->prepare("
    SELECT a.*, q.question_text, q.id as question_id, qz.title as quiz_title 
    FROM answers a 
    JOIN questions q ON a.question_id = q.id 
    JOIN quizzes qz ON q.quiz_id = qz.id 
    WHERE a.id = ?
");
$stmt->execute([$answer_id]);
$answer = $stmt->fetch();

if (!$answer) {
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
            // Start transaction
            $pdo->beginTransaction();

            // If this answer is being marked as correct, unmark other answers
            if ($is_correct) {
                $stmt = $pdo->prepare("UPDATE answers SET is_correct = 0 WHERE question_id = ?");
                $stmt->execute([$answer['question_id']]);
            }

            // Update this answer
            $stmt = $pdo->prepare("UPDATE answers SET answer_text = ?, is_correct = ? WHERE id = ?");
            $stmt->execute([$answer_text, $is_correct, $answer_id]);

            $pdo->commit();

            header("Location: manage.php?question_id=" . $answer['question_id']);
            exit();
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Error updating answer: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Answer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            max-width: 800px;
            margin: 20px auto;
            line-height: 1.6;
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }

        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            min-height: 100px;
            font-size: 14px;
        }

        .error {
            color: #dc3545;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-cancel {
            background-color: #6c757d;
        }

        .btn-cancel:hover {
            background-color: #545b62;
        }

        .checkbox-group {
            margin-top: 15px;
        }

        .checkbox-group label {
            display: flex;
            align-items: center;
            font-weight: normal;
        }

        .checkbox-group input[type="checkbox"] {
            margin-right: 10px;
        }

        .context-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border-left: 4px solid #007bff;
        }

        .breadcrumb {
            margin-bottom: 20px;
            color: #6c757d;
        }

        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="breadcrumb">


            <a href="manage.php?question_id=<?= $answer['question_id'] ?>">Question</a>
            Edit Answer
        </div>

        <h1>Edit Answer</h1>

        <div class="context-info">
            <p><strong>Quiz:</strong> <?= $answer['quiz_title'] ?></p>
            <p><strong>Question:</strong> <?= $answer['question_text'] ?></p>
        </div>

        <?php if (isset($error)): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="answer_text">Answer Text:</label>
                <textarea
                    name="answer_text"
                    id="answer_text"
                    required><?= $answer['answer_text'] ?></textarea>
            </div>

            <div class="checkbox-group">
                <label>
                    <input
                        type="checkbox"
                        name="is_correct"
                        value="1"
                        <?= $answer['is_correct'] ? 'checked' : '' ?>>
                    Mark as correct answer
                </label>
            </div>

            <div style="margin-top: 20px;">
                <button type="submit" class="btn btn-primary">Update Answer</button>
                <a href="manage.php?question_id=<?= $answer['question_id'] ?>" class="btn btn-cancel">Cancel</a>
            </div>
        </form>
    </div>
</body>

</html>