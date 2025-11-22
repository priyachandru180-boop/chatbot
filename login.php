<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        switch ($user['role']) {
            case 'student':
                header('Location: student_dashboard.php');
                break;
            case 'staff':
                header('Location: staff_dashboard.php');
                break;
            case 'admin':
                header('Location: admin_dashboard.php');
                break;
            default:
                $error = "Invalid role";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Login - Institution System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #e0e7ff;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #ef4444;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-secondary: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --radius: 0.75rem;
            --radius-lg: 1rem;
            --shadow-sm: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background Elements */
        .background-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s infinite linear;
        }

        .shape-1 {
            width: 300px;
            height: 300px;
            background: white;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .shape-2 {
            width: 200px;
            height: 200px;
            background: var(--primary-light);
            bottom: -100px;
            right: 10%;
            animation-delay: -5s;
        }

        .shape-3 {
            width: 150px;
            height: 150px;
            background: white;
            top: 20%;
            right: -75px;
            animation-delay: -10s;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            33% {
                transform: translateY(-30px) rotate(120deg);
            }
            66% {
                transform: translateY(15px) rotate(240deg);
            }
        }

        .login-container {
            max-width: 440px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-xl);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: slideUp 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
            font-weight: 700;
            font-size: 1.75rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        h2::after {
            content: '🎓';
            position: absolute;
            right: 0;
            top: 0;
            font-size: 1.5rem;
            background: none;
            -webkit-text-fill-color: initial;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--gray-700);
            transition: all 0.3s ease;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            font-family: 'Inter', sans-serif;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
            transform: translateY(-2px);
        }

        input[type="text"]:hover,
        input[type="password"]:hover {
            border-color: var(--primary);
        }

        button {
            width: 100%;
            padding: 1rem 1.25rem;
            background: var(--gradient-primary);
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s;
        }

        button:hover::before {
            left: 100%;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        button:active {
            transform: translateY(0);
        }

        .error {
            color: var(--danger);
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(239, 68, 68, 0.1);
            border-radius: var(--radius);
            border-left: 4px solid var(--danger);
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .test-credentials {
            margin-top: 2rem;
            padding: 1.5rem;
            background: rgba(99, 102, 241, 0.05);
            border-radius: var(--radius);
            border: 1px solid rgba(99, 102, 241, 0.1);
            animation: fadeIn 0.6s ease-out 0.3s both;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .test-credentials h4 {
            color: var(--gray-700);
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .test-credentials h4::before {
            content: '🔑';
        }

        .test-credentials p {
            margin-bottom: 0.5rem;
            color: var(--gray-600);
            font-size: 0.9rem;
        }

        .test-credentials strong {
            color: var(--primary);
        }

        /* Floating label animation */
        .form-group:focus-within label {
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Particle effect for successful login */
        .particle {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--primary);
            border-radius: 50%;
            pointer-events: none;
            opacity: 0;
        }

        /* Responsive design */
        @media (max-width: 480px) {
            .login-container {
                padding: 2rem 1.5rem;
                margin: 1rem;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            input[type="text"],
            input[type="password"],
            button {
                padding: 0.875rem 1rem;
            }
        }

        /* Loading animation */
        .loading {
            display: none;
            width: 20px;
            height: 20px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Role badges in test credentials */
        .role-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: var(--gradient-primary);
            color: white;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
    </style>
</head>

<body>
    <div class="background-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="login-container">
        <h2>Institution Login</h2>
        <?php if (isset($error))
            echo "<div class='error'>$error</div>"; ?>
        <form method="POST" id="loginForm">
            <div class="form-group">
                <label>Username:</label>
                <input type="text" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Password:</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <button type="submit" id="loginBtn">
                <span id="btnText">Login</span>
                <div class="loading" id="loadingSpinner"></div>
            </button>
        </form>

        <div class="test-credentials">
            <h4>Test Credentials</h4>
            <p><span class="role-badge">Student</span><strong>john_doe</strong> / password</p>
            <p><span class="role-badge">Staff</span><strong>prof_smith</strong> / password</p>
            <p><span class="role-badge">Admin</span><strong>admin</strong> / password</p>
        </div>
    </div>

    <script>
        // Form submission animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const spinner = document.getElementById('loadingSpinner');
            
            btnText.style.display = 'none';
            spinner.style.display = 'block';
            btn.disabled = true;
            
            // Create particle effect
            createParticles(btn);
        });

        // Particle effect function
        function createParticles(element) {
            const rect = element.getBoundingClientRect();
            const particles = 8;
            
            for (let i = 0; i < particles; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const x = rect.left + rect.width / 2;
                const y = rect.top + rect.height / 2;
                
                particle.style.left = x + 'px';
                particle.style.top = y + 'px';
                
                document.body.appendChild(particle);
                
                const angle = Math.random() * Math.PI * 2;
                const velocity = 50 + Math.random() * 50;
                const vx = Math.cos(angle) * velocity / 10;
                const vy = Math.sin(angle) * velocity / 10;
                
                const animation = particle.animate([
                    { 
                        transform: `translate(0, 0) scale(1)`,
                        opacity: 1
                    },
                    { 
                        transform: `translate(${vx}px, ${vy}px) scale(0)`,
                        opacity: 0
                    }
                ], {
                    duration: 800 + Math.random() * 400,
                    easing: 'cubic-bezier(0, .9, .57, 1)'
                });
                
                animation.onfinish = () => particle.remove();
            }
        }

        // Input focus effects
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.classList.add('focused');
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.classList.remove('focused');
                }
            });
        });
    </script>
</body>

</html>