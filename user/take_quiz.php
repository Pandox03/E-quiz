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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #6B46C1;
            --secondary-color: #9F7AEA;
            --accent-color: #553C9A;
            --text-dark: #2D3748;
            --text-light: #FFFFFF;
            --background: #F8F7FF;
        }

        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 0;
            background: var(--background);
            color: var(--text-dark);
        }

        nav {
            background-color: var(--text-light);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(107, 70, 193, 0.1);
        }

        nav h1 {
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
            color: var(--primary-color);
        }

        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }

        .quiz-header {
            background: var(--text-light);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        .timer {
            float: right;
            font-size: 1.2em;
            color: var(--primary-color);
            font-weight: bold;
        }

        .question-card {
            background: var(--text-light);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .question-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(107, 70, 193, 0.2);
        }

        .answer-option {
            margin: 10px 0;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .answer-option:hover {
            border-color: var(--secondary-color);
            background-color: var(--background);
        }

        .answer-option.selected {
            background-color: var(--secondary-color);
            border-color: var(--primary-color);
            color: var(--text-light);
        }

        .progress-bar {
            background: var(--text-light);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        .question-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .question-number {
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--background);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .question-number.answered {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .save-btn {
            background-color: var(--secondary-color);
            color: var(--text-light);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 15px;
            transition: all 0.3s ease;
        }

        .save-btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: var(--primary-color);
            color: var(--text-light);
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
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