<?php
session_start();
require_once '../connection.php';

// Check if user is logged in and is a regular user
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../login.php");
    exit();
}

// Get user's information
$stmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Get user's total attempts
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM quiz_attempts WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$total_attempts = $stmt->fetch()['total'];

// Get user's average score
$stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM quiz_attempts WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$_SESSION['user_id']]);
$avg_score = round($stmt->fetch()['avg_score'] ?? 0, 1);

// Get recent quiz attempts
$stmt = $pdo->prepare("
    SELECT qa.*, q.title, q.passing_score 
    FROM quiz_attempts qa 
    JOIN quizzes q ON qa.quiz_id = q.id 
    WHERE qa.user_id = ? 
    ORDER BY qa.start_time DESC 
    LIMIT 5
");
$stmt->execute([$_SESSION['user_id']]);
$recent_attempts = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - E-quiz</title>
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
            height: 200px; /* Fixed height */
            overflow-y: auto; /* Scrollable if content exceeds */
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2rem;
            margin: 0.5rem 0;
            color: var(--text-dark);
        }

        .stat-card p {
            color: #718096;
            margin: 0;
        }

        .recent-attempts {
            background: var(--text-light);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        .recent-attempts h2 {
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

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .badge-success {
            background-color: #C6F6D5;
            color: #2F855A;
        }

        .badge-warning {
            background-color: #FEFCBF;
            color: #975A16;
        }

        .badge-danger {
            background-color: #FED7D7;
            color: #C53030;
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
        <h1>E-quiz</h1>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="available_quizzes.php"><i class="fas fa-book"></i> Quizzes</a>
            <a href="my_results.php"><i class="fas fa-chart-bar"></i> Results</a>
            <a href="../logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-card">
            <h1>Welcome back, <?php echo $user['username']; ?>! 👋</h1>
            <p><i class="fas fa-envelope"></i> <?php echo $user['email']; ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-tasks"></i>
                <h3><?php echo $total_attempts; ?></h3>
                <p>Total Attempts</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-chart-line"></i>
                <h3><?php echo $avg_score; ?>%</h3>
                <p>Average Score</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-trophy"></i>
                <h3><?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) as passed FROM quiz_attempts WHERE user_id = ? AND status = 'completed' AND score >= (SELECT passing_score FROM quizzes WHERE id = quiz_id)");
                    $stmt->execute([$_SESSION['user_id']]);
                    echo $stmt->fetch()['passed'];
                ?></h3>
                <p>Quizzes Passed</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-book-open"></i>
                <h3><?php 
                    $stmt = $pdo->prepare("SELECT COUNT(*) as available FROM quizzes");
                    $stmt->execute();
                    echo $stmt->fetch()['available'];
                ?></h3>
                <p>Available Quizzes</p>
            </div>
        </div>

        <div class="recent-attempts">
            <h2><i class="fas fa-history"></i> Recent Quiz Attempts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Quiz</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_attempts as $attempt): ?>
                        <tr>
                            <td><i class="fas fa-file-alt"></i> <?php echo $attempt['title']; ?></td>
                            <td><?php echo $attempt['score']; ?>%</td>
                            <td>
                                <?php
                                $status_badge = '';
                                $status_icon = '';
                                switch ($attempt['status']) {
                                    case 'completed':
                                        $status_badge = 'badge-success';
                                        $status_icon = 'fas fa-check';
                                        break;
                                    case 'in_progress':
                                        $status_badge = 'badge-warning';
                                        $status_icon = 'fas fa-clock';
                                        break;
                                    case 'timeout':
                                        $status_badge = 'badge-danger';
                                        $status_icon = 'fas fa-times';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $status_badge; ?>">
                                    <i class="<?php echo $status_icon; ?>"></i> 
                                    <?php echo ucfirst($attempt['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($attempt['start_time'])); ?></td>
                            <td>
                                <?php if ($attempt['status'] == 'completed'): ?>
                                    <span class="badge <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <i class="fas <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                        <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'Passed' : 'Failed'; ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>