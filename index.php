<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-quiz</title>

    <style>
        :root {
            --primary-color: #6B46C1;
            --secondary-color: #9F7AEA;
            --accent-color: #553C9A;
            --text-dark: #2D3748;
            --text-light: #FFFFFF;
            --background: #F8F7FF;
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
            background-color: var(--text-light);
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        nav a:last-child {
            background-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.2);
        }

        nav a:hover {
            transform: translateY(-2px);
        }

        nav a:first-child:hover {
            background-color: var(--primary-color);
            color: var(--text-light);
        }

        nav a:last-child:hover {
            background-color: var(--accent-color);
        }

        .hero-section {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            min-height: calc(100vh - 70px);
        }

        .hero-content {
            flex: 1;
            max-width: 600px;
            animation: fadeIn 0.8s ease-in;
        }

        .hero-image {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .hero-image img {
            background-image: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            max-width: 100%;
            height: auto;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(107, 70, 193, 0.15);
        }

        .hero-title {
            font-size: clamp(2rem, 5vw, 3.5rem);
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: #4A5568;
            margin-bottom: 2.5rem;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .cta-button {
            padding: 12px 32px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .primary-button {
            background-color: var(--primary-color);
            color: var(--text-light);
            box-shadow: 0 4px 15px rgba(107, 70, 193, 0.2);
        }

        .secondary-button {
            background-color: var(--text-light);
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .cta-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 70, 193, 0.3);
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

        @media (max-width: 968px) {
            .hero-section {
                flex-direction: column-reverse;
                text-align: center;
                padding: 1rem;
                gap: 1rem;
            }

            .hero-content {
                max-width: 100%;
            }

            .hero-image {
                width: 100%;
                max-width: 500px;
            }

            .cta-buttons {
                justify-content: center;
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

            .cta-button {
                width: 100%;
                text-align: center;
            }

            .cta-buttons {
                flex-direction: column;
                width: 100%;
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
        <div class="hero-section">
            <div class="hero-content">
                <h1 class="hero-title">Master Your Knowledge</h1>
                <p class="hero-subtitle">Challenge yourself with interactive quizzes and track your progress in real-time. Join our learning community today.</p>
                <div class="cta-buttons">
                    <a href="login.php" class="cta-button primary-button">Start Learning</a>
                    <a href="register.php" class="cta-button secondary-button">Sign Up</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="public/hero_quiz.png" 
                     alt="Educational Background"
                     loading="lazy">
            </div>
        </div>
    </main>
</body>
</html>