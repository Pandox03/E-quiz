<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['success'] = "User deleted successfully!";
    header("Location: index.php");
    exit();
}

// Fetch all users except current admin
$stmt = $pdo->prepare("SELECT u.id, u.username, u.email, u.role, 
    COUNT(DISTINCT qa.id) as total_attempts,
    COALESCE(ROUND(AVG(qa.score), 1), 0) as avg_score
    FROM users u 
    LEFT JOIN quiz_attempts qa ON u.id = qa.user_id
    WHERE u.id != ?
    GROUP BY u.id, u.username, u.email, u.role");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - E-quiz Admin</title>
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

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .add-user-btn {
            background: var(--primary-color);
            color: var(--text-light);
            padding: 0.75rem 1.5rem;
            border-radius: 30px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .add-user-btn:hover {
            background: var(--accent-color);
        }

        .users-table {
            background: var(--text-light);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            text-align: left;
            padding: 1rem;
            background: var(--background);
            color: var(--text-dark);
            font-weight: 500;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #E2E8F0;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .edit-btn, .delete-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            text-decoration: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            transition: all 0.3s ease;
        }

        .edit-btn {
            background: var(--success-color);
            color: #fff;
        }

        .delete-btn {
            background: var(--danger-color);
            color: var(--text-light);
        }

        .edit-btn:hover {
            background: #3da066;
        }

        .delete-btn:hover {
            background: #e14d4d;
        }

        .success-msg, .error-msg {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }

        .success-msg {
            background: #C6F6D5;
            color: #2F855A;
        }

        .error-msg {
            background: #FED7D7;
            color: #C53030;
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
            background: var(--background);
        }

        .role-admin {
            color: var(--primary-color);
            background: #EBF4FF;
        }



        @media (max-width: 768px) {
            .container { padding: 0 1rem; }
            nav { padding: 15px 20px; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>

<body>
    <nav>
        <h1>E-quiz Admin</h1>
        <div class="nav-links">
            <a href="../dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="index.php"><i class="fas fa-users"></i> Users</a>
            <a href="../quizzes/index.php"><i class="fas fa-book"></i> Quizzes</a>
            <a href="../../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </nav>

    <div class="container">
        <div class="header-section">
            <h1>Manage Users</h1>
            <a href="create.php" class="add-user-btn">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-msg">
                <i class="fas fa-check-circle"></i> 
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-msg">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="users-table">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Attempts</th>
                        <th>Avg. Score</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <i class="fas fa-user"></i> 
                                <?php echo $user['username']; ?>
                            </td>
                            <td>
                                <i class="fas fa-envelope"></i> 
                                <?php echo $user['email']; ?>
                            </td>
                            <td><?php echo $user['total_attempts']; ?></td>
                            <td><?php echo $user['avg_score']; ?>%</td>
                            <td class="action-buttons">
                                <a href="edit.php?id=<?php echo $user['id']; ?>" class="edit-btn">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <?php if ($user['role'] != 'admin'): ?>
                                    <a href="index.php?delete=<?php echo $user['id']; ?>" 
                                       class="delete-btn"
                                       onclick="return confirm('Are you sure you want to delete this user?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
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