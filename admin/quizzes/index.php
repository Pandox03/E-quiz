<?php
session_start();
require_once '../../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$stmt = $pdo->query("SELECT q.*, 
    (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count 
    FROM quizzes q");
$quizzes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Quizzes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 10px;
            border: 1px solid #ddd;
            text-align: left;
        }

        th {
            background-color: #f4f4f4;
        }

        .btn {
            display: inline-block;
            padding: 8px 12px;
            text-decoration: none;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            margin: 5px;
        }

        .btn-edit {
            background-color: #2196F3;
        }

        .btn-delete {
            background-color: #f44336;
        }

        .btn-questions {
            background-color: #ff9800;
        }
    </style>
</head>

<body>
    <h1>Manage Quizzes</h1>
    <a href="create.php" class="btn">Create New Quiz</a>
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Duration</th>
                <th>Passing score</th>
                <th>Status</th>
                <th>Questions</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($quizzes as $quiz): ?>
                <tr>
                    <td><?= htmlspecialchars($quiz['title']) ?></td>
                    <td><?= htmlspecialchars($quiz['description']) ?></td>
                    <td><?= $quiz['duration'] ?> minutes</td>
                    <td><?= $quiz['passing_score'] ?>%</td>
                    <td><?= $quiz['status'] ?></td>
                    <td><?= $quiz['question_count'] ?> questions</td>
                    <td>
                        <a href="questions/manage.php?quiz_id=<?= $quiz['id'] ?>" class="btn btn-questions">Manage Questions</a>
                        <a href="edit.php?id=<?= $quiz['id'] ?>" class="btn btn-edit">Edit</a>
                        <a href="delete.php?id=<?= $quiz['id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>