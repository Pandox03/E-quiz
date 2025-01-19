<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get all quiz attempts by the user with quiz information
$stmt = $pdo->prepare("
    SELECT 
        qa.*,
        q.title,
        q.description,
        q.passing_score,
        (
            SELECT COUNT(*)
            FROM questions
            WHERE quiz_id = q.id
        ) as total_questions,
        (
            SELECT COUNT(*)
            FROM user_answers ua
            WHERE ua.attempt_id = qa.id
        ) as answered_questions,
        (
            SELECT COUNT(*)
            FROM user_answers ua
            JOIN answers a ON ua.answer_id = a.id
            WHERE ua.attempt_id = qa.id AND a.is_correct = 1
        ) as correct_answers
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    WHERE qa.user_id = ?
    ORDER BY qa.start_time DESC
");
$stmt->execute([$user_id]);
$attempts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Quiz Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .container {
            max-width: 1000px;
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

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .result-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .result-card h3 {
            margin-top: 0;
            color: #333;
        }

        .score-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .pass {
            background-color: #d4edda;
            color: #155724;
        }

        .fail {
            background-color: #f8d7da;
            color: #721c24;
        }

        .timeout {
            background-color: #fff3cd;
            color: #856404;
        }

        .stats {
            margin: 15px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }

        .view-btn {
            display: inline-block;
            padding: 8px 16px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }

        .view-btn:hover {
            background-color: #0056b3;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            margin-top: 20px;
        }

        .datetime {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
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
        <h1>My Quiz Results</h1>

        <?php if (empty($attempts)): ?>
            <div class="empty-message">
                <h2>No quiz attempts yet</h2>
                <p>Take a quiz to see your results here!</p>
                <a href="available_quizzes.php" class="view-btn">View Available Quizzes</a>
            </div>
        <?php else: ?>
            <div class="results-grid">
                <?php foreach ($attempts as $attempt): ?>
                    <div class="result-card">
                        <h3><?= htmlspecialchars($attempt['title']) ?></h3>

                        <div class="datetime">
                            Started: <?= date('M d, Y H:i', strtotime($attempt['start_time'])) ?>
                        </div>

                        <?php if ($attempt['end_time']): ?>
                            <div class="datetime">
                                Completed: <?= date('M d, Y H:i', strtotime($attempt['end_time'])) ?>
                            </div>
                        <?php endif; ?>

                        <div class="score-badge <?=
                                                $attempt['status'] === 'timeout' ? 'timeout' : ($attempt['score'] >= $attempt['passing_score'] ? 'pass' : 'fail')
                                                ?>">
                            <?php if ($attempt['status'] === 'timeout'): ?>
                                TIMEOUT
                            <?php else: ?>
                                <?= $attempt['score'] >= $attempt['passing_score'] ? 'PASSED' : 'FAILED' ?>
                            <?php endif; ?>
                        </div>

                        <div class="stats">
                            <p><strong>Score:</strong> <?= $attempt['score'] ?>%</p>
                            <p><strong>Questions:</strong> <?= $attempt['answered_questions'] ?>/<?= $attempt['total_questions'] ?></p>
                            <p><strong>Correct:</strong> <?= $attempt['correct_answers'] ?></p>
                            <p><strong>Required to Pass:</strong> <?= $attempt['passing_score'] ?>%</p>
                        </div>

                        <?php if ($attempt['status'] === 'completed'): ?>
                            <a href="quiz_result.php?attempt_id=<?= $attempt['id'] ?>" class="view-btn">
                                View Details
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>