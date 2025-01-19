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
$stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE id != ?");
$stmt->execute([$_SESSION['user_id']]);
$users = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
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
            width: 90%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #f5f5f5;
        }

        .action-buttons a {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px;
            text-decoration: none;
            border-radius: 3px;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
        }

        .add-user-btn {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 3px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .success-msg {
            background-color: #dff0d8;
            color: #3c763d;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .error-msg {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 3px;
        }
    </style>
</head>

<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="../dashboard.php">Dashboard</a>
        <a href="index.php">Manage Users</a>
        <a href="../quizzes/index.php">Manage Quizzes</a>
        <a href="../../logout.php" style="float: right;">Logout</a>
    </div>

    <div class="container">
        <h1>Manage Users</h1>


        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-msg">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-msg">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>


        <a href="create.php" class="add-user-btn">Add New User</a>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['username']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td class="action-buttons">
                            <a href="edit.php?id=<?php echo $user['id']; ?>" class="edit-btn">Edit</a>
                            <?php if ($user['role'] != 'admin'): ?>
                                <a href="index.php?delete=<?php echo $user['id']; ?>"
                                    class="delete-btn"
                                    onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>