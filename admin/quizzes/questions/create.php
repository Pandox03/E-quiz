<?php
// create_question.php
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
$stmt = $pdo->prepare("SELECT title FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $question_text = trim($_POST['question_text']);
    $points = (int)$_POST['points'];

    if (empty($question_text)) {
        $error = "Question text is required";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text, points) VALUES (?, ?, ?)");
            $stmt->execute([$quiz_id, $question_text, $points]);
            $question_id = $pdo->lastInsertId();
            header("Location: answers/create.php?question_id=" . $question_id);
            exit();
        } catch (PDOException $e) {
            $error = "Error adding question: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Question - <?= htmlspecialchars($quiz['title']) ?></title>
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
    </style>
</head>

<body>
    <h1>Add Question to <?= htmlspecialchars($quiz['title']) ?></h1>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="question_text">Question Text:</label>
            <textarea name="question_text" id="question_text" rows="4" required><?= isset($_POST['question_text']) ? htmlspecialchars($_POST['question_text']) : '' ?></textarea>
        </div>

        <div class="form-group">
            <label for="points">Points:</label>
            <input type="number" name="points" id="points" value="<?= isset($_POST['points']) ? htmlspecialchars($_POST['points']) : '1' ?>" min="1" required>
        </div>

        <button type="submit" class="btn">Add Question & Continue to Answers</button>
        <a href="manage.php?quiz_id=<?= $quiz_id ?>" class="btn btn-cancel">Cancel</a>
    </form>
</body>

</html>