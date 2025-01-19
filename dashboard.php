<?php
session_start();
require 'connection.php';

// Registration and Login Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['email']) && isset($_POST['confirm_password'])) {

        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $_SESSION['error'] = "Passwords do not match!";
            header("Location: register.php");
            exit();
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "Username or email already exists!";
            header("Location: register.php");
            exit();
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$username, $email, $hashed_password]);

        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    }

    if (isset($_POST['username']) && isset($_POST['password']) && !isset($_POST['confirm_password'])) {

        $username = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header("Location: admin/dashboard.php");
            } else {
                header("Location: user/dashboard.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid username or password!";
            header("Location: login.php");
            exit();
        }
    }
}
