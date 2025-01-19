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
    <title>Available Quizzes - E-quiz</title>
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

        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px; /* Space between cards */
            margin-top: 20px;
        }

        .quiz-card {
            background: var(--text-light);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            min-height: 300px;
        }

        .quiz-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(107, 70, 193, 0.2);
        }

        .quiz-title {
            font-size: 1.4em;
            margin: 0 0 10px 0;
            color: var(--text-dark);
        }

        .quiz-info {
            margin: 15px 0;
            color: #666;
            flex-grow: 1;
        }

        .quiz-info p {
            margin: 5px 0;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            text-decoration: none;
            color: var(--text-light);
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
            margin-top: auto;
        }

        .btn-start {
            background-color: var(--primary-color);
        }

        .btn-resume {
            background-color: var(--secondary-color);
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
            background: var(--text-light);
            border-radius: 8px;
            margin-top: 20px;
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
                                        <span class="badge badge-danger"><?= $quiz['best_score'] ?>% (Not Passed)</span>
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