<?php
session_start();
require_once '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Get all published quizzes with some additional information
$stmt = $pdo->prepare("
    SELECT 
        q.*,
        (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count,
        (SELECT COUNT(*) FROM quiz_attempts 
         WHERE quiz_id = q.id AND user_id = ? AND status = 'completed') as attempts_count,
        (SELECT MAX(score) FROM quiz_attempts 
         WHERE quiz_id = q.id AND user_id = ? AND status = 'completed') as best_score,
        EXISTS(SELECT 1 FROM quiz_attempts 
               WHERE quiz_id = q.id AND user_id = ? 
               AND status = 'in_progress') as has_ongoing_attempt
    FROM quizzes q
    WHERE q.status = 'published'
    ORDER BY q.id DESC
");
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Quizzes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
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

        .navbar a:hover {
            background-color: #555;
            border-radius: 4px;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .quiz-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .quiz-card:hover {
            transform: translateY(-5px);
        }

        .quiz-title {
            font-size: 1.4em;
            margin: 0 0 10px 0;
            color: #333;
        }

        .quiz-info {
            margin: 15px 0;
            color: #666;
        }

        .quiz-info p {
            margin: 5px 0;
        }

        .quiz-stats {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }

        .btn-start {
            background-color: #28a745;
            width: 100%;
        }

        .btn-resume {
            background-color: #ffc107;
            width: 100%;
        }

        .btn:hover {
            opacity: 0.9;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.8em;
            margin-left: 5px;
        }

        .badge-success {
            background-color: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .no-quizzes {
            text-align: center;
            padding: 40px;
            background: white;
            border-radius: 8px;
            margin-top: 20px;
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
        <h1>Available Quizzes</h1>

        <?php if (empty($quizzes)): ?>
            <div class="no-quizzes">
                <h2>No quizzes available at the moment</h2>
                <p>Please check back later for new quizzes.</p>
            </div>
        <?php else: ?>
            <div class="quiz-grid">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <h2 class="quiz-title"><?= htmlspecialchars($quiz['title']) ?></h2>

                        <div class="quiz-info">
                            <p><?= nl2br(htmlspecialchars($quiz['description'])) ?></p>
                            <p><strong>Duration:</strong> <?= $quiz['duration'] ?> minutes</p>
                            <p><strong>Questions:</strong> <?= $quiz['question_count'] ?></p>
                            <p><strong>Passing Score:</strong> <?= $quiz['passing_score'] ?>%</p>
                        </div>

                        <?php if ($quiz['attempts_count'] > 0): ?>
                            <div class="quiz-stats">
                                <p><strong>Your Attempts:</strong> <?= $quiz['attempts_count'] ?></p>
                                <p><strong>Best Score:</strong>
                                    <?php if ($quiz['best_score'] >= $quiz['passing_score']): ?>
                                        <span class="badge badge-success"><?= $quiz['best_score'] ?>% (Passed)</span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= $quiz['best_score'] ?>% (Not Passed)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <?php if ($quiz['has_ongoing_attempt']): ?>
                            <a href="take_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-resume">
                                Resume Quiz
                            </a>
                        <?php else: ?>
                            <a href="start_quiz.php?id=<?= $quiz['id'] ?>" class="btn btn-start">
                                Start Quiz
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>