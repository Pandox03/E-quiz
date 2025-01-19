<!-- login.php -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - E-quiz</title>
    <style>
        :root {
            --primary-color: #6B46C1;
            --secondary-color: #9F7AEA;
            --accent-color: #553C9A;
            --text-dark: #2D3748;
            --text-light: #FFFFFF;
            --background: #F8F7FF;
            --error-color: #E53E3E;
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

        nav .nav-links {
            display: flex;
            gap: 15px;
        }

        nav a {
            text-decoration: none;
            padding: 8px 24px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        nav a:first-child {
            background-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.2);
        }

        nav a:last-child {
            background-color: var(--text-light);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .login-container {
            max-width: 450px;
            margin: 2rem auto;
            padding: 2rem;
            background: var(--text-light);
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(107, 70, 193, 0.15);
            animation: fadeIn 0.8s ease-in;
        }

        .login-title {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 500;
            color: var(--text-dark);
        }

        .form-group input {
            padding: 12px 16px;
            border: 2px solid #E2E8F0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(107, 70, 193, 0.1);
        }

        .submit-button {
            background-color: var(--primary-color);
            color: var(--text-light);
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }

        .submit-button:hover {
            background-color: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.2);
        }

        .register-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-dark);
        }

        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .login-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            nav {
                padding: 15px 20px;
            }

            .nav-links {
                gap: 10px;
            }

            nav a {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <h1>E-quiz</h1>
        <div class="nav-links">
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </nav>
    <main>
        <div class="login-container">
            <h1 class="login-title">Welcome Back</h1>
            <form class="login-form" action="dashboard.php" method="POST">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="submit-button">Login</button>
            </form>
            <p class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </main>
</body>

</html>