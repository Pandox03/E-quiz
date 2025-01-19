<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Website</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            text-align: center;
        }

        header {
            background-color: #4CAF50;
            color: white;
            padding: 10px 0;
        }

        h1 {
            margin: 20px 0;
        }

        .container {
            margin: 20px auto;
            padding: 20px;
            max-width: 800px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .description {
            font-size: 1.2em;
            margin: 20px 0;
        }

        .buttons {
            margin-top: 20px;
        }

        .buttons a {
            text-decoration: none;
            color: white;
            background: #4CAF50;
            padding: 10px 20px;
            border-radius: 5px;
            margin: 5px;
            display: inline-block;
            transition: background 0.3s;
        }

        .buttons a:hover {
            background: #45a049;
        }
    </style>
</head>

<body>
    <header>
        <h1>Welcome to the Quiz Platform</h1>
    </header>
    <div class="container">
        <p class="description">
            Test your knowledge on various topics with our interactive quizzes!
            Join us today to challenge yourself, improve your skills, and have fun.
        </p>
        <div class="buttons">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </div>
</body>

</html>