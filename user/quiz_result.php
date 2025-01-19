<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['attempt_id'])) {
    header("Location: available_quizzes.php");
    exit();
}

$attempt_id = $_GET['attempt_id'];
$user_id = $_SESSION['user_id'];

// Get attempt details with quiz info
$stmt = $pdo->prepare("
    SELECT qa.*, q.title, q.description, q.passing_score
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.id = ? AND qa.user_id = ? AND qa.status = 'completed'
");
$stmt->execute([$attempt_id, $user_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header("Location: available_quizzes.php");
    exit();
}

// Get questions with user answers and correct answers
$stmt = $pdo->prepare("
   SELECT 
    q.question_text,
    q.points,
    ua.answer_id as user_answer_id,
    (SELECT answer_text FROM answers WHERE id = ua.answer_id) as user_answer_text,
    (SELECT GROUP_CONCAT(answer_text SEPARATOR '; ') 
     FROM answers 
     WHERE question_id = q.id AND is_correct = 1) as correct_answer_text,
    (SELECT a.is_correct FROM answers a WHERE a.id = ua.answer_id) as is_correct
FROM questions q
LEFT JOIN user_answers ua ON q.id = ua.question_id AND ua.attempt_id = ?
WHERE q.quiz_id = ?
ORDER BY q.id
");
$stmt->execute([$attempt_id, $attempt['quiz_id']]);
$questions = $stmt->fetchAll();

// Calculate statistics
$total_questions = count($questions);
$answered_questions = 0;
$correct_answers = 0;
$total_points = 0;
$earned_points = 0;

foreach ($questions as $question) {
    $total_points += $question['points'];
    if ($question['user_answer_id']) {
        $answered_questions++;
        if ($question['is_correct']) {
            $correct_answers++;
            $earned_points += $question['points'];
        }
    }
}

$completion_rate = ($answered_questions / $total_questions) * 100;
$accuracy_rate = $answered_questions > 0 ? ($correct_answers / $answered_questions) * 100 : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - <?= htmlspecialchars($attempt['title']) ?></title>
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

        .navbar {
            background-color: #333;
            padding: 15px;
            color: white;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            margin-right: 10px;
        }

        .result-header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
        }

        .questions-review {
            margin-top: 30px;
        }

        .question-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .answer {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }

        .correct-answer {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .incorrect-answer {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .not-answered {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .result-badge {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .pass {
            background-color: #d4edda;
            color: #155724;
        }

        .fail {
            background-color: #f8d7da;
            color: #721c24;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 20px;
        }

        .btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="available_quizzes.php">Available Quizzes</a>
        <a href="my_results.php">My Results</a>
        <a href="../logout.php" style="float: right;">Logout</a>
    </div>

    <div class="container">
        <div class="result-header">
            <h1><?= htmlspecialchars($attempt['title']) ?></h1>
            <p><?= nl2br(htmlspecialchars($attempt['description'])) ?></p>

            <div class="result-badge <?= $attempt['score'] >= $attempt['passing_score'] ? 'pass' : 'fail' ?>">
                <?= $attempt['score'] >= $attempt['passing_score'] ? 'PASSED' : 'FAILED' ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>Final Score</h3>
                <div class="stat-value"><?= number_format($attempt['score'], 1) ?>%</div>
                <p>Passing score: <?= $attempt['passing_score'] ?>%</p>
            </div>

            <div class="stat-card">
                <h3>Completion</h3>
                <div class="stat-value"><?= number_format($completion_rate, 1) ?>%</div>
                <p><?= $answered_questions ?> of <?= $total_questions ?> questions</p>
            </div>

            <div class="stat-card">
                <h3>Accuracy</h3>
                <div class="stat-value"><?= number_format($accuracy_rate, 1) ?>%</div>
                <p><?= $correct_answers ?> correct answers</p>
            </div>

            <div class="stat-card">
                <h3>Points</h3>
                <div class="stat-value"><?= $earned_points ?>/<?= $total_points ?></div>
                <p>Total points earned</p>
            </div>
        </div>

        <div class="questions-review">
            <h2>Questions Review</h2>
            <?php $question_num = 1; ?>
            <?php foreach ($questions as $question): ?>
                <div class="question-card">
                    <h3>Question <?= $question_num++ ?></h3>
                    <p><?= htmlspecialchars($question['question_text']) ?></p>

                    <?php if ($question['user_answer_id']): ?>
                        <div class="answer <?= $question['is_correct'] ? 'correct-answer' : 'incorrect-answer' ?>">
                            <strong>Your Answer:</strong> <?= htmlspecialchars($question['user_answer_text']) ?>
                            <?php if (!$question['is_correct']): ?>
                                <div class="correct-answer" style="margin-top: 10px;">
                                    <strong>Correct Answer:</strong> <?= htmlspecialchars($question['correct_answer_text']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="answer not-answered">
                            <strong>Not Answered</strong>
                            <div style="margin-top: 10px;">
                                <strong>Correct Answer:</strong> <?= htmlspecialchars($question['correct_answer_text']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <p><strong>Points:</strong>
                        <?= $question['is_correct'] ? $question['points'] : '0' ?>/<?= $question['points'] ?>
                    </p>
                </div>
            <?php endforeach; ?>
        </div>

        <a href="available_quizzes.php" class="btn">Back to Quizzes</a>
    </div>
</body>

</html>