<?php
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
$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = ?");
$stmt->execute([$quiz_id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: ../index.php");
    exit();
}

// Get questions with their answers
$stmt = $pdo->prepare("
    SELECT q.*, 
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count,
        (SELECT COUNT(*) FROM answers WHERE question_id = q.id AND is_correct = 1) as correct_answer_count
    FROM questions q 
    WHERE q.quiz_id = ?
    ORDER BY q.id
");
$stmt->execute([$quiz_id]);
$questions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Questions - <?= htmlspecialchars($quiz['title']) ?></title>
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
            --success-color: #48BB78;
            --warning-color: #ECC94B;
            --danger-color: #F56565;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--background);
            margin: 0;
            padding: 0;
            min-height: 100vh;
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

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(107, 70, 193, 0.15);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--text-light);
            color: var(--primary-color);
        }

        .btn-edit {
            background: var(--primary-color);
            color: var(--text-light);
        }

        .btn-delete {
            background: var(--danger-color);
            color: var(--text-light);
        }

        .btn-answers {
            background: var(--warning-color);
            color: var(--text-dark);
        }

        .question-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }

        .question-item {
            background: var(--text-light);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(107, 70, 193, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s;
        }

        .question-item:hover {
            transform: translateX(5px);
        }

        .question-content {
            flex: 1;
        }

        .question-text {
            font-size: 1.1rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .question-meta {
            display: flex;
            gap: 1rem;
            color: #666;
            font-size: 0.9rem;
        }

        .question-actions {
            display: flex;
            gap: 0.5rem;
        }

        .question-actions a {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            color: var(--text-light);
            text-decoration: none;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            nav {
                padding: 15px 20px;
            }

            .btn {
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <h1>E-quiz Admin</h1>
        <div class="nav-links">
            <a href="../../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../../users/index.php"><i class="fas fa-users"></i> Users</a>
            <a href="../index.php"><i class="fas fa-book"></i> Quizzes</a>
            <a href="../../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>
                <i class="fas fa-question-circle"></i> 
                Questions for: <?= htmlspecialchars($quiz['title']) ?>
            </h1>
            <div>
                <a href="../index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Quizzes
                </a>
                <a href="create.php?quiz_id=<?= $quiz_id ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Question
                </a>
            </div>
        </div>

        <?php if (!empty($questions)): ?>
            <div class="question-list">
                <?php foreach ($questions as $question): ?>
                    <div class="question-item">
                        <div class="question-content">
                            <div class="question-text">
                                <?= htmlspecialchars($question['question_text']) ?>
                            </div>
                            <div class="question-meta">
                                <span><i class="fas fa-star"></i> <?= $question['points'] ?> points</span>
                                <span><i class="fas fa-check"></i> <?= $question['correct_answer_count'] ?>/<?= $question['answer_count'] ?> correct</span>
                            </div>
                        </div>
                        <div class="question-actions">
                            <a href="answers/manage.php?question_id=<?= $question['id'] ?>" 
                               style="background: var(--primary-color);">
                                <i class="fas fa-list"></i> Answers
                            </a>
                            <a href="delete.php?id=<?= $question['id'] ?>" 
                               style="background: var(--danger-color);"
                               onclick="return confirm('Delete this question?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2><i class="fas fa-info-circle"></i> No questions yet</h2>
                <p>Get started by adding your first question!</p>
                <a href="create.php?quiz_id=<?= $quiz_id ?>" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add First Question
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>