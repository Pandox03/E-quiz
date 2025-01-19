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

// Get 5 recent users 
$stmt = $pdo->query("SELECT username, email FROM users WHERE role = 'user' ORDER BY id DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
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

        .recent-users {
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

        .welcome {
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

    <div class="navbar">
        <a href="dashboard.php">Dashboard</a>
        <a href="users/index.php">Manage Users</a>
        <a href="quizzes/index.php">Manage Quizzes</a>

    </div>

    <div class="container">

        <div class="welcome">
            <h1>Welcome, <?php echo $_SESSION['username']; ?>!</h1>
            <p>This is your admin dashboard where you can manage users and quizzes.</p>
        </div>

        <div class="stats-container">
            <div class="stat-box">
                <h2>Total Users</h2>
                <h3><?php echo $users_count; ?></h3>
                <a href="users/index.php">View All Users →</a>
            </div>

            <div class="stat-box">
                <h2>Total Quizzes</h2>
                <h3><?php echo $quiz_count; ?></h3>
                <a href="quizzes/index.php">View All Quizzes →</a>
            </div>
        </div>

        <div class="recent-users">
            <h2>Recent Users</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>

                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_users as $user): ?>
                        <tr>
                            <td><?php echo $user['username']; ?></td>
                            <td><?php echo $user['email']; ?></td>

                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>