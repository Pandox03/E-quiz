<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$stmt = $pdo->query("SELECT q.*, 
    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
    FROM quizzes q");
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes - E-quiz</title>
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

        .nav-links a:hover {
            background-color: var(--background);
            color: var(--primary-color);
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

        .create-btn {
            background: var(--text-light);
            color: var(--primary-color);
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .create-btn:hover {
            background: var(--accent-color);
            color: var(--text-light);
        }

        .content-card {
            background: var(--text-light);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        .quiz-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .quiz-card {
            background: var(--text-light);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
            transition: all 0.3s ease;
        }

        .quiz-card:hover {
            transform: translateY(-5px);
        }

        .quiz-title {
            font-size: 1.2rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .quiz-meta {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .quiz-status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .status-published { background: var(--success-color); color: white; }
        .status-draft { background: var(--warning-color); color: var(--text-dark); }

        .card-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .card-actions a {
            flex: 1;
            text-align: center;
            padding: 0.5rem;
            border-radius: 8px;
            color: var(--text-light);
            text-decoration: none;
            transition: opacity 0.3s;
        }

        .card-actions a:hover {
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }

            nav {
                padding: 15px 20px;
            }

            .nav-links a {
                padding: 6px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <h1>E-quiz Admin</h1>
        <div class="nav-links">
            <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../users/index.php"><i class="fas fa-users"></i> Users</a>
            <a href="index.php"><i class="fas fa-book"></i> Quizzes</a>
            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1><i class="fas fa-book"></i> Manage Quizzes</h1>
            <a href="create.php" class="create-btn">
                <i class="fas fa-plus"></i> Create New Quiz
            </a>
        </div>

        <div class="content-card">
            <div class="quiz-grid">
                <?php foreach ($quizzes as $quiz): ?>
                    <div class="quiz-card">
                        <h3 class="quiz-title">
                            <?= htmlspecialchars($quiz['title']) ?>
                        </h3>
                        <div class="quiz-meta">
                            <i class="far fa-clock"></i> <?= $quiz['duration'] ?> min | 
                            <i class="fas fa-percentage"></i> <?= $quiz['passing_score'] ?>% to pass |
                            <i class="fas fa-question-circle"></i> <?= $quiz['question_count'] ?> questions
                        </div>
                        <span class="quiz-status status-<?= strtolower($quiz['status']) ?>">
                            <?= ucfirst($quiz['status']) ?>
                        </span>
                        <p><?= mb_strimwidth(htmlspecialchars($quiz['description']), 0, 100, "...") ?></p>
                        <div class="card-actions">
                            <a href="questions/manage.php?quiz_id=<?= $quiz['id'] ?>" 
                               style="background: var(--primary-color);">
                                <i class="fas fa-tasks"></i> Manage
                            </a>
                            <a href="delete.php?id=<?= $quiz['id'] ?>" 
                               style="background: var(--danger-color);"
                               onclick="return confirm('Are you sure you want to delete this quiz?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>

</html>