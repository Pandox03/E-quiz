<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: index.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM quizzes WHERE id = :id");
$stmt->execute(['id' => $id]);
$quiz = $stmt->fetch();

if (!$quiz) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $passing_score = $_POST['passing_score'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE quizzes SET title = :title, description = :description, duration = :duration, passing_score = :passing_score, status = :status WHERE id = :id");
    $stmt->execute(['title' => $title, 'description' => $description, 'id' => $id, 'duration' => $duration, 'passing_score' => $passing_score, 'status' => $status]);

    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Quiz</title>
</head>

<body>
    <h1>Update Quiz</h1>
    <form method="post">
        <label for="title">Title:</label>
        <input type="text" name="title" id="title" value="<?= $quiz['title'] ?>" required>
        <br>
        <label for="description">Description:</label>
        <textarea name="description" id="description" required><?= $quiz['description'] ?></textarea>
        <br>
        <label for="duration">duration:</label>
        <input type="datetime" name="duration" id="duration" required value="<?= $quiz['duration'] ?>">
        <br>
        <label for="passing_score">passin score:</label>
        <input type="number" name="passing_score" id="passing_score" required value="<?= $quiz['passing_score'] ?>">
        <br>
        <label for="status">status:</label>
        <select name="status" id="status">
            <option value="draft">draft</option>
            <option value="published">published</option>
        </select>
        <br>
        <button type="submit">Update</button>
    </form>
</body>

</html>