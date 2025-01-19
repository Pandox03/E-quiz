<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $passing_score = $_POST['passing_score'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("INSERT INTO quizzes (title, description,duration, passing_score, status) VALUES (:title, :description, :duration, :passing_score, :status)");
    $stmt->execute(['title' => $title, 'description' => $description, 'duration' => $duration, 'passing_score' => $passing_score, 'status' => $status]);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Quiz</title>
</head>

<body>
    <h1>Create Quiz</h1>
    <form method="post">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" required>
        <br>
        <label for="description">Description:</label>
        <textarea name="description" id="description" required></textarea>
        <br>
        <label for="duration">duration:</label>
        <input type="datetime" name="duration" id="duration" required>
        <br>
        <label for="passing_score">passin score:</label>
        <input type="number" name="passing_score" id="passing_score" required>
        <br>
        <label for="status">status:</label>
        <select name="status" id="status">
            <option value="draft">draft</option>
            <option value="published">published</option>
        </select>
        <br>
        <button type="submit">Create</button>
    </form>
</body>

</html>