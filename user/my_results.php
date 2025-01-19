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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        h1 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--text-dark);
        }

        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px; /* Space between cards */
            margin-top: 20px;
        }

        .result-card {
            background: var(--text-light);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            min-height: 300px;
        }

        .result-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(107, 70, 193, 0.2);
        }

        .score-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin: 10px 0;
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

        .view-btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            color: var(--text-light);
            background-color: var(--primary-color);
            border-radius: 4px;
            margin-top: auto;
            text-align: center;
            transition: opacity 0.3s;
        }

        .view-btn:hover {
            opacity: 0.9;
        }

        .stats {
            flex-grow: 1;
            margin: 15px 0;
        }

        .stats p {
            margin: 5px 0;
            color: #666;
        }

        .empty-message {
            text-align: center;
            padding: 40px;
            background: var(--text-light);
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