<?php

session_start();
require_once '../../connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Get user data
if (isset($_GET['id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            $_SESSION['error'] = "User not found!";
            header("Location: index.php");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error fetching user: " . $e->getMessage();
        header("Location: index.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Start with basic update query
    $query = "UPDATE users SET username = ?, email = ?, role = ?";
    $params = [$_POST['username'], $_POST['email'], $_POST['role']];

    // Add password to update if provided
    if (!empty($_POST['password'])) {
        if ($_POST['password'] !== $_POST['confirm_password']) {
            throw new Exception("Passwords do not match!");
        }
        $query .= ", password = ?";
        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
    }

    $query .= " WHERE id = ?";
    $params[] = $_GET['id'];

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    $_SESSION['success'] = "User updated successfully!";
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
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
        <h1>Edit User</h1>

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
                <input type="text" name="username" value="<?php echo $user['username']; ?>" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?php echo $user['email']; ?>" required>
            </div>

            <div class="form-group">
                <label>New Password (leave blank to keep current)</label>
                <input type="password" name="password">
            </div>

            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password">
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="user" <?php echo $user['role'] == 'user' ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                </select>
            </div>

            <button type="submit">Update User</button>
            <a href="index.php" class="back-link">Back to Users List</a>
        </form>
    </div>
</body>

</html>