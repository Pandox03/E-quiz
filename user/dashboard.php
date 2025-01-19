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
    <title>User Dashboard</title>
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
        }

        .container {
            width: 80%;
            margin: 20px auto;
        }

        .user-info {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .stat-box {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            width: 45%;
        }

        .recent-attempts {
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 0.8em;
        }

        .badge-success {
            background-color: #dff0d8;
            color: #3c763d;
        }

        .badge-danger {
            background-color: #f2dede;
            color: #a94442;
        }

        .badge-warning {
            background-color: #fcf8e3;
            color: #8a6d3b;
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
        <div class="user-info">
            <h1>Welcome, <?php echo $user['username']; ?>!</h1>
            <p>Email: <?php echo $user['email']; ?></p>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <h2>Total Quiz Attempts</h2>
                <h3><?php echo $total_attempts; ?></h3>
                <a href="my_results.php">View All Results →</a>
            </div>

            <div class="stat-box">
                <h2>Average Score</h2>
                <h3><?php echo $avg_score; ?>%</h3>
                <p>Keep practicing to improve your score!</p>
            </div>
        </div>

        <div class="recent-attempts">
            <h2>Recent Quiz Attempts</h2>
            <table>
                <thead>
                    <tr>
                        <th>Quiz Title</th>
                        <th>Score</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_attempts as $attempt): ?>
                        <tr>
                            <td><?php echo $attempt['title']; ?></td>
                            <td><?php echo $attempt['score']; ?>%</td>
                            <td>
                                <?php
                                $status_badge = '';
                                switch ($attempt['status']) {
                                    case 'completed':
                                        $status_badge = 'badge-success';
                                        break;
                                    case 'in_progress':
                                        $status_badge = 'badge-warning';
                                        break;
                                    case 'timeout':
                                        $status_badge = 'badge-danger';
                                        break;
                                }
                                ?>
                                <span class="badge <?php echo $status_badge; ?>">
                                    <?php echo ucfirst($attempt['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($attempt['start_time'])); ?></td>
                            <td>
                                <?php if ($attempt['status'] == 'completed'): ?>
                                    <span class="badge <?php echo $attempt['score'] >= $attempt['passing_score'] ? 'badge-success' : 'badge-danger'; ?>">
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