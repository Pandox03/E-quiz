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
            background: var(--background);
            margin: 0;
            padding: 0;
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

        .nav-links {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            padding: 8px 24px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            color: var(--text-dark);
        }

        .nav-links a:hover {
            background-color: var(--background);
            color: var(--primary-color);
        }

        .container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .result-header {
            background: var(--text-light);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stat-card {
            background: var(--text-light);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(107, 70, 193, 0.2);
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            margin: 10px 0;
            color: var(--primary-color);
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

        .result-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: bold;
            margin: 15px 0;
        }

        .pass {
            background-color: #d4edda;
            color: #155724;
        }

        .fail {
            background-color: #f8d7da;
            color: #721c24;
        }

        .answer {
            margin: 10px 0;
            padding: 12px;
            border-radius: 8px;
            transition: transform 0.2s;
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

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: var(--primary-color);
            color: var(--text-light);
            text-decoration: none;
            border-radius: 8px;
            margin-top: 20px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        h1, h2, h3 {
            color: var(--primary-color);
        }
    </style>
</head>

<body>
    <nav>
        <h1>E-quiz</h1>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="available_quizzes.php"><i class="fas fa-book"></i> Quizzes</a>
            <a href="my_results.php"><i class="fas fa-chart-bar"></i> Results</a>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

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
            <h2><i class="fas fa-tasks"></i> Questions Review</h2>
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

        <a href="available_quizzes.php" class="btn">
            <i class="fas fa-arrow-left"></i> Back to Quizzes
        </a>
    </div>
</body>

</html>