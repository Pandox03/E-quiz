<?php
// admin/users/create.php
session_start();
require_once '../../connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Validate input
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match!");
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$_POST['username'], $_POST['email']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception("Username or email already exists!");
        }

        // Insert new user
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt->execute([
            $_POST['username'],
            $_POST['email'],
            $hashed_password,
            $_POST['role']
        ]);

        $_SESSION['success'] = "User created successfully!";
        header("Location: index.php");
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New User</title>
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

        .container {
            width: 60%;
            margin: 20px auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        .error-msg {
            background-color: #f2dede;
            color: #a94442;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 3px;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #666;
        }
    </style>
</head>

<body>
    <div class="navbar">
        <a href="../dashboard.php">Dashboard</a>
        <a href="index.php">Manage Users</a>
        <a href="../quizzes/index.php">Manage Quizzes</a>
        <a href="../../logout.php" style="float: right;">Logout</a>
    </div>

    <div class="container">
        <h1>Create New User</h1>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-msg">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
            </div>

            <button type="submit">Create User</button>
            <a href="index.php" class="back-link">Back to Users List</a>
        </form>
    </div>
</body>

</html>