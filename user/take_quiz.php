<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: available_quizzes.php");
    exit();
}

$quiz_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Get quiz attempt and quiz information
$stmt = $pdo->prepare("
    SELECT qa.*, q.title, q.duration, q.description
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.quiz_id = ? AND qa.user_id = ? 
    AND qa.status = 'in_progress'
    ORDER BY qa.start_time DESC LIMIT 1
");
$stmt->execute([$quiz_id, $user_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header("Location: available_quizzes.php");
    exit();
}

// Get all questions with their answers
$stmt = $pdo->prepare("
    SELECT 
        q.*, 
        (SELECT COUNT(*) FROM user_answers ua 
         WHERE ua.attempt_id = ? AND ua.question_id = q.id) as is_answered,
        a.id as answer_id,
        a.answer_text,
        (SELECT ua.id FROM user_answers ua 
         WHERE ua.attempt_id = ? AND ua.answer_id = a.id) as is_selected
    FROM questions q 
    LEFT JOIN answers a ON q.id = a.question_id
    WHERE q.quiz_id = ?
    ORDER BY q.id, a.id
");
$stmt->execute([$attempt['id'], $attempt['id'], $quiz_id]);
$results = $stmt->fetchAll();

// Organize questions and answers
$questions = [];
foreach ($results as $row) {
    if (!isset($questions[$row['id']])) {
        $questions[$row['id']] = [
            'id' => $row['id'],
            'question_text' => $row['question_text'],
            'points' => $row['points'],
            'is_answered' => $row['is_answered'],
            'answers' => []
        ];
    }
    if ($row['answer_id']) {
        $questions[$row['id']]['answers'][] = [
            'id' => $row['answer_id'],
            'answer_text' => $row['answer_text'],
            'is_selected' => $row['is_selected']
        ];
    }
}

// Handle answer submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_answer'])) {
    if (isset($_POST['question_id']) && isset($_POST['answer_id'])) {
        $question_id = $_POST['question_id'];
        $answer_id = $_POST['answer_id'];

        // Remove existing answer
        $stmt = $pdo->prepare("DELETE FROM user_answers WHERE attempt_id = ? AND question_id = ?");
        $stmt->execute([$attempt['id'], $question_id]);

        // Insert new answer
        $stmt = $pdo->prepare("INSERT INTO user_answers (attempt_id, question_id, answer_id) VALUES (?, ?, ?)");
        $stmt->execute([$attempt['id'], $question_id, $answer_id]);

        header("Location: take_quiz.php?id=" . $quiz_id);
        exit();
    }
}

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $total_points = 0;
    $earned_points = 0;

    foreach ($questions as $question) {
        $total_points += $question['points'];

        $stmt = $pdo->prepare("
            SELECT a.is_correct 
            FROM user_answers ua
            JOIN answers a ON ua.answer_id = a.id
            WHERE ua.attempt_id = ? AND ua.question_id = ?
        ");
        $stmt->execute([$attempt['id'], $question['id']]);
        $result = $stmt->fetch();

        if ($result && $result['is_correct']) {
            $earned_points += $question['points'];
        }
    }

    $score = ($earned_points / $total_points) * 100;

    $stmt = $pdo->prepare("
        UPDATE quiz_attempts 
        SET status = 'completed', 
            score = ?,
            end_time = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$score, $attempt['id']]);

    header("Location: quiz_result.php?attempt_id=" . $attempt['id']);
    exit();
}

// Calculate remaining time
$start_time = strtotime($attempt['start_time']);
$end_time = $start_time + ($attempt['duration'] * 60);
$remaining_time = $end_time - time();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($attempt['title']) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .quiz-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timer {
            float: right;
            font-size: 1.2em;
            color: #dc3545;
            font-weight: bold;
        }

        .question-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .answer-option {
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }

        .answer-option:hover {
            background-color: #f8f9fa;
        }

        .answer-option.selected {
            background-color: #cce5ff;
            border-color: #b8daff;
        }

        .navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1em;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-success {
            background-color: #28a745;
            color: white;
        }

        .progress-bar {
            background: white;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }

        .question-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .question-number {
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #f8f9fa;
            cursor: pointer;
        }

        .question-number.answered {
            background: #28a745;
            color: white;
        }

        .save-btn {
            margin-top: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .save-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="quiz-header">
            <div class="timer" id="timer">Time remaining: calculating...</div>
            <h1><?= htmlspecialchars($attempt['title']) ?></h1>
            <p><?= nl2br(htmlspecialchars($attempt['description'])) ?></p>
        </div>

        <div class="progress-bar">
            <h3>Progress</h3>
            <div class="question-list">
                <?php $question_num = 1; ?>
                <?php foreach ($questions as $question): ?>
                    <div class="question-number <?= $question['is_answered'] ? 'answered' : '' ?>">
                        <?= $question_num++ ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php $question_num = 1; ?>
        <?php foreach ($questions as $question): ?>
            <div class="question-card">
                <h3>Question <?= $question_num++ ?></h3>
                <p><?= htmlspecialchars($question['question_text']) ?></p>

                <form method="POST">
                    <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                    <?php foreach ($question['answers'] as $answer): ?>
                        <div class="answer-option <?= $answer['is_selected'] ? 'selected' : '' ?>">
                            <label>
                                <input type="radio"
                                    name="answer_id"
                                    value="<?= $answer['id'] ?>"
                                    <?= $answer['is_selected'] ? 'checked' : '' ?>>
                                <?= htmlspecialchars($answer['answer_text']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <input type="hidden" name="submit_answer" value="1">
                    <button type="submit" class="save-btn">Save Answer</button>
                </form>
            </div>
        <?php endforeach; ?>

        <div class="navigation">
            <form method="POST" onsubmit="return confirm('Are you sure you want to submit the quiz? This action cannot be undone.');">
                <button type="submit" name="submit_quiz" class="btn btn-success">Submit Quiz</button>
            </form>
        </div>
    </div>

    <script>
        const endTime = <?= $end_time ?> * 1000;

        function updateTimer() {
            const now = new Date().getTime();
            const timeLeft = endTime - now;

            if (timeLeft <= 0) {
                document.querySelector('form[name="submit_quiz"]').submit();
                return;
            }

            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

            document.getElementById('timer').textContent =
                `Time remaining: ${minutes}:${seconds.toString().padStart(2, '0')}`;
        }

        setInterval(updateTimer, 1000);
        updateTimer();
    </script>
</body>

</html>