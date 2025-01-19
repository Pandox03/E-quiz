<?php
// edit_question.php
session_start();
require_once '../../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: ../index.php");
    exit();
}

$question_id = $_GET['id'];

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
            $stmt = $pdo->prepare("UPDATE questions SET question_text = ?, points = ? WHERE id = ?");
            $stmt->execute([$question_text, $points, $question_id]);
            header("Location: manage.php?quiz_id=" . $question['quiz_id']);
            exit();
        } catch (PDOException $e) {
            $error = "Error updating question: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question - <?= htmlspecialchars($question['quiz_title']) ?></title>
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
    <h1>Edit Question</h1>
    <h2>Quiz: <?= htmlspecialchars($question['quiz_title']) ?></h2>

    <?php if (isset($error)): ?>
        <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="form-group">
            <label for="question_text">Question Text:</label>
            <textarea name="question_text" id="question_text" rows="4" required><?= htmlspecialchars($question['question_text']) ?></textarea>
        </div>

        <div class="form-group">
            <label for="points">Points:</label>
            <input type="number" name="points" id="points" value="<?= htmlspecialchars($question['points']) ?>" min="1" required>
        </div>

        <button type="submit" class="btn">Update Question</button>
        <a href="manage.php?quiz_id=<?= $question['quiz_id'] ?>" class="btn btn-cancel">Cancel</a>
    </form>
</body>

</html>