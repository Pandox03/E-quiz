<?php
session_start();
require_once '../connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Count total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$users_count = $stmt->fetch()['total'];

// Count total quizzes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
$quiz_count = $stmt->fetch()['total'];

// Get user's average score
$stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM quiz_attempts WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['user_id']]);
$avg_score = round($stmt->fetch()['avg_score'] ?? 0, 1);

// Get recent users with their average scores
$stmt = $pdo->query("
    SELECT u.username, u.email, 
    COALESCE(ROUND(AVG(qa.score), 1), 0) as avg_score,
    COUNT(DISTINCT qa.id) as total_attempts,
    COUNT(DISTINCT CASE WHEN qa.score >= q.passing_score THEN qa.id END) as passed_attempts
    FROM users u 
    LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
    LEFT JOIN quizzes q ON qa.quiz_id = q.id
    WHERE u.role = 'user'
    GROUP BY u.id, u.username, u.email 
    ORDER BY u.id DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();

// Get additional statistics
$stmt = $pdo->query("
    SELECT 
    COUNT(DISTINCT q.id) as total_questions,
    COUNT(DISTINCT a.id) as total_answers,
    (SELECT COUNT(*) FROM quiz_attempts WHERE status = 'completed' AND score >= (SELECT passing_score FROM quizzes WHERE id = quiz_id)) as total_passed,
    (SELECT COUNT(*) FROM quizzes WHERE status = 'published') as published_quizzes
    FROM questions q
    LEFT JOIN answers a ON q.id = a.question_id
");
$additional_stats = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-quiz</title>
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

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: var(--text-light);
            padding: 2rem;
            border-radius: 20px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(107, 70, 193, 0.15);
        }

        .welcome-card h1 {
            margin: 0;
            font-size: 2rem;
        }

        .welcome-card p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--text-light);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
            transition: transform 0.3s ease;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 250px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin: 0.5rem 0;
            color: var(--text-dark);
        }

        .stat-card p {
            color: #718096;
            margin: 0;
            margin-bottom: 0.5rem;
        }

        .stat-card small {
            display: block;
            color: #718096;
            margin-bottom: 1rem;
        }

        .stat-card .action-btn {
            margin-top: auto;
            display: inline-block;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-light);
            background: var(--primary-color);
            transition: all 0.3s ease;
        }

        .stat-card .action-btn:hover {
            background: var(--accent-color);
        }

        .recent-users {
            background: var(--text-light);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        .recent-users h2 {
            color: var(--text-dark);
            margin-top: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: var(--background);
            color: var(--text-dark);
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #E2E8F0;
        }

        .action-btn {
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-light);
            background: var(--primary-color);
            transition: all 0.3s ease;
        }

        .action-btn:hover {
            background: var(--accent-color);
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
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="users/index.php"><i class="fas fa-users"></i> Users</a>
            <a href="quizzes/index.php"><i class="fas fa-book"></i> Quizzes</a>
            <a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome back, <?php echo $_SESSION['username']; ?>! 👋</h1>
            <p><i class="fas fa-shield-alt"></i> Administrator Dashboard</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?php echo $users_count; ?></h3>
                <p>Total Users</p>
                <a href="users/index.php" class="action-btn">Manage Users</a>
            </div>
            <div class="stat-card">
                <i class="fas fa-book"></i>
                <h3><?php echo $quiz_count; ?></h3>
                <p>Total Quizzes</p>
                <small>(<?php echo $additional_stats['published_quizzes']; ?> Published)</small>
                <a href="quizzes/index.php" class="action-btn">Manage Quizzes</a>
            </div>
            <div class="stat-card">
                <i class="fas fa-question-circle"></i>
                <h3><?php echo $additional_stats['total_questions']; ?></h3>
                <p>Total Questions</p>
                <small>(<?php echo $additional_stats['total_answers']; ?> Answers)</small>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <h3><?php echo $additional_stats['total_passed']; ?></h3>
                <p>Passed Attempts</p>
                <small>Out of <?php 
                    $stmt = $pdo->query("SELECT COUNT(*) as total FROM quiz_attempts WHERE status = 'completed'");
                    echo $stmt->fetch()['total'];
                ?> Total Attempts</small>
            </div>
        </div>

        <div class="recent-users">
            <h2><i class="fas fa-user-clock"></i> Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Average Score</th>
                        <th>Attempts</th>
                        <th>Pass Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><i class="fas fa-user"></i> <?php echo $user['username']; ?></td>
                            <td><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></td>
                            <td>
                                <i class="fas fa-star"></i> 
                                <?php echo $user['avg_score']; ?>%
                            </td>
                            <td>
                                <i class="fas fa-pen"></i>
                                <?php echo $user['total_attempts']; ?>
                            </td>
                            <td>
                                <i class="fas fa-trophy"></i>
                                <?php 
                                    echo $user['total_attempts'] > 0 
                                        ? round(($user['passed_attempts'] / $user['total_attempts']) * 100) 
                                        : 0; 
                                ?>%
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>