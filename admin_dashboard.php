<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle user operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $role = $_POST['role'];

        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$username, $email, $password, $role]);
        $user_id = $pdo->lastInsertId();

        // Create corresponding record in students or staff table
        if ($role == 'student') {
            $name = $_POST['name'];
            $department = $_POST['department'];
            $semester = $_POST['semester'];
            $stmt = $pdo->prepare("INSERT INTO students (user_id, name, email, department, semester) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $email, $department, $semester]);
        } elseif ($role == 'staff') {
            $name = $_POST['name'];
            $department = $_POST['department'];
            $designation = $_POST['designation'];
            $stmt = $pdo->prepare("INSERT INTO staff (user_id, name, email, department, designation) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $name, $email, $department, $designation]);
        }

        $success = "User added successfully!";
    }

    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $success = "User deleted successfully!";
    }
}

// Get all users
$users_stmt = $pdo->prepare("SELECT * FROM users");
$users_stmt->execute();
$users = $users_stmt->fetchAll();

// Get all students
$students_stmt = $pdo->prepare("SELECT * FROM students");
$students_stmt->execute();
$students = $students_stmt->fetchAll();

// Get all staff
$staff_stmt = $pdo->prepare("SELECT * FROM staff");
$staff_stmt->execute();
$staff = $staff_stmt->fetchAll();

// Get department statistics
$dept_stats_stmt = $pdo->prepare("
    SELECT department, COUNT(*) as count 
    FROM students 
    GROUP BY department
");
$dept_stats_stmt->execute();
$dept_stats = $dept_stats_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberAdmin | Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --neon-blue: #00f3ff;
            --neon-purple: #b967ff;
            --neon-pink: #ff2a6d;
            --neon-green: #00ff9f;
            --dark-bg: #0a0a0f;
            --darker-bg: #050508;
            --card-bg: rgba(16, 16, 26, 0.8);
            --card-border: rgba(0, 243, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #b8b8b8;
            --glow-blue: 0 0 20px #00f3ff;
            --glow-purple: 0 0 20px #b967ff;
            --glow-pink: 0 0 20px #ff2a6d;
            --radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(0, 243, 255, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(185, 103, 255, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 50% 50%, rgba(255, 42, 109, 0.03) 0%, transparent 50%);
        }

        .cyber-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0, 243, 255, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0, 243, 255, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            opacity: 0.3;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            min-height: 100vh;
        }

        /* Cyber Sidebar */
        .cyber-sidebar {
            background: var(--darker-bg);
            border-right: 1px solid var(--card-border);
            position: relative;
            overflow: hidden;
        }

        .cyber-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--neon-blue), transparent);
            animation: scanline 3s linear infinite;
        }

        @keyframes scanline {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .sidebar-header {
            padding: 30px 25px;
            text-align: center;
            border-bottom: 1px solid var(--card-border);
            position: relative;
        }

        .sidebar-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: var(--glow-blue);
            margin-bottom: 10px;
        }

        .sidebar-header p {
            color: var(--neon-green);
            font-size: 0.9rem;
            font-weight: 500;
        }

        .cyber-menu {
            padding: 25px 0;
        }

        .cyber-menu-item {
            padding: 18px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            margin: 8px 15px;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
        }

        .cyber-menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 243, 255, 0.1), transparent);
            transition: var(--transition);
        }

        .cyber-menu-item:hover::before {
            left: 100%;
        }

        .cyber-menu-item.active {
            color: var(--neon-blue);
            border-left-color: var(--neon-blue);
            background: rgba(0, 243, 255, 0.05);
            box-shadow: inset 0 0 15px rgba(0, 243, 255, 0.1);
        }

        .cyber-menu-item i {
            width: 20px;
            text-align: center;
            font-size: 1.2rem;
        }

        /* Main Content */
        .cyber-main {
            padding: 30px;
            overflow-y: auto;
        }

        .cyber-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            padding: 25px 30px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            position: relative;
            backdrop-filter: blur(10px);
        }

        .cyber-header::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            right: -1px;
            height: 2px;
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple), var(--neon-pink));
            border-radius: var(--radius) var(--radius) 0 0;
        }

        .cyber-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .cyber-user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .cyber-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-purple));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark-bg);
            font-weight: bold;
            font-size: 1.4rem;
            position: relative;
            overflow: hidden;
        }

        .cyber-avatar::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-purple), var(--neon-pink), var(--neon-green));
            border-radius: 50%;
            z-index: -1;
            animation: rotate 3s linear infinite;
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .cyber-logout {
            background: transparent;
            color: var(--neon-pink);
            border: 1px solid var(--neon-pink);
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            font-weight: 600;
            text-decoration: none;
            position: relative;
            overflow: hidden;
        }

        .cyber-logout:hover {
            background: var(--neon-pink);
            color: var(--dark-bg);
            box-shadow: var(--glow-pink);
        }

        /* Stats Grid */
        .cyber-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .cyber-stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }

        .cyber-stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--neon-blue);
            box-shadow: var(--glow-blue);
        }

        .cyber-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 20px;
            position: relative;
        }

        .stat-icon::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: conic-gradient(from 0deg, var(--neon-blue), var(--neon-purple), var(--neon-pink), var(--neon-blue));
            border-radius: 50%;
            z-index: -1;
            animation: rotate 3s linear infinite;
        }

        .stat-icon i {
            background: var(--card-bg);
            width: 66px;
            height: 66px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cyber-stat-info h3 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-green));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }

        .cyber-stat-info p {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }

        /* Cyber Sections */
        .cyber-section {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            backdrop-filter: blur(10px);
        }

        .cyber-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
        }

        .cyber-section h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.6rem;
            margin-bottom: 25px;
            color: var(--neon-blue);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Cyber Table */
        .cyber-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(10, 10, 15, 0.6);
            border-radius: var(--radius);
            overflow: hidden;
            border: 1px solid var(--card-border);
        }

        .cyber-table th {
            background: linear-gradient(45deg, var(--neon-blue), var(--neon-purple));
            color: var(--dark-bg);
            font-weight: 600;
            padding: 20px;
            text-align: left;
            font-family: 'Orbitron', sans-serif;
            font-size: 0.9rem;
        }

        .cyber-table td {
            padding: 20px;
            border-bottom: 1px solid var(--card-border);
            color: var(--text-secondary);
            transition: var(--transition);
        }

        .cyber-table tr:hover td {
            background: rgba(0, 243, 255, 0.05);
            color: var(--text-primary);
        }

        /* Cyber Buttons */
        .cyber-btn {
            padding: 14px 28px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            text-decoration: none;
            font-family: 'Rajdhani', sans-serif;
            position: relative;
            overflow: hidden;
            background: transparent;
            color: var(--text-primary);
            border: 1px solid;
        }

        .cyber-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: var(--transition);
        }

        .cyber-btn:hover::before {
            left: 100%;
        }

        .cyber-btn-primary {
            border-color: var(--neon-blue);
            color: var(--neon-blue);
        }

        .cyber-btn-primary:hover {
            background: var(--neon-blue);
            color: var(--dark-bg);
            box-shadow: var(--glow-blue);
        }

        .cyber-btn-success {
            border-color: var(--neon-green);
            color: var(--neon-green);
        }

        .cyber-btn-success:hover {
            background: var(--neon-green);
            color: var(--dark-bg);
            box-shadow: var(--glow-blue);
        }

        .cyber-btn-danger {
            border-color: var(--neon-pink);
            color: var(--neon-pink);
        }

        .cyber-btn-danger:hover {
            background: var(--neon-pink);
            color: var(--dark-bg);
            box-shadow: var(--glow-pink);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 10px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        /* Role Badges */
        .cyber-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-admin {
            background: rgba(255, 42, 109, 0.2);
            color: var(--neon-pink);
            border: 1px solid rgba(255, 42, 109, 0.3);
        }

        .badge-staff {
            background: rgba(0, 243, 255, 0.2);
            color: var(--neon-blue);
            border: 1px solid rgba(0, 243, 255, 0.3);
        }

        .badge-student {
            background: rgba(0, 255, 159, 0.2);
            color: var(--neon-green);
            border: 1px solid rgba(0, 255, 159, 0.3);
        }

        /* Cyber Modal */
        .cyber-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(5, 5, 8, 0.9);
            backdrop-filter: blur(10px);
        }

        .cyber-modal-content {
            background: var(--card-bg);
            margin: 5% auto;
            padding: 40px;
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            width: 90%;
            max-width: 600px;
            position: relative;
            animation: cyberSlideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes cyberSlideIn {
            from {
                opacity: 0;
                transform: translateY(-50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .cyber-modal-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(90deg, var(--neon-blue), var(--neon-purple));
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .modal-header h3 {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            color: var(--neon-blue);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cyber-close {
            color: var(--text-secondary);
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: var(--transition);
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .cyber-close:hover {
            color: var(--neon-pink);
            background: rgba(255, 42, 109, 0.1);
        }

        .cyber-form-group {
            margin-bottom: 25px;
        }

        .cyber-form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--neon-blue);
            font-size: 0.95rem;
        }

        .cyber-form-group input,
        .cyber-form-group select {
            width: 100%;
            padding: 15px 20px;
            background: rgba(10, 10, 15, 0.6);
            border: 1px solid var(--card-border);
            border-radius: var(--radius);
            color: var(--text-primary);
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Rajdhani', sans-serif;
        }

        .cyber-form-group input:focus,
        .cyber-form-group select:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 2px rgba(0, 243, 255, 0.2);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        /* Alert */
        .cyber-alert {
            padding: 20px 25px;
            border-radius: var(--radius);
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            border: 1px solid;
            border-left: 4px solid;
        }

        .alert-success {
            background: rgba(0, 255, 159, 0.1);
            border-color: var(--neon-green);
            color: var(--neon-green);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .cyber-sidebar {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .cyber-main {
                padding: 20px 15px;
            }
            
            .cyber-header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .cyber-stats {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .cyber-modal-content {
                padding: 25px;
                margin: 10% auto;
            }
        }
        /* Chatbot Styles */
        .chatbot-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .chatbot-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
        }

        .chatbot-icon::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff);
            border-radius: 50%;
            z-index: -1;
            animation: rotate 3s linear infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chatbot-icon:hover::before {
            opacity: 1;
        }

        .chatbot-icon i {
            color: white;
            font-size: 28px;
            z-index: 1;
        }

        .chatbot-icon:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
        }

        .chatbot-pulse {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            animation: pulse 2s infinite;
        }

        .chatbot-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #ff4757;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            animation: bounce 1s infinite;
        }

        /* Chatbot Modal */
        .chatbot-container {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .chatbot-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            animation: float 3s ease-in-out infinite;
            position: relative;
            overflow: hidden;
            text-decoration: none;
        }

        .chatbot-icon::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #ff6b6b, #4ecdc4, #45b7d1, #96ceb4, #feca57, #ff9ff3, #54a0ff);
            border-radius: 50%;
            z-index: -1;
            animation: rotate 3s linear infinite;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chatbot-icon:hover::before {
            opacity: 1;
        }

        .chatbot-icon i {
            color: white;
            font-size: 28px;
            z-index: 1;
        }

        .chatbot-icon:hover {
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 12px 35px rgba(0, 0, 0, 0.4);
        }

        .chatbot-pulse {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            background: inherit;
            animation: pulse 2s infinite;
        }

        .chatbot-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #ff4757;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: bold;
            animation: bounce 1s infinite;
        }

        /* Tooltip */
        .chatbot-tooltip {
            position: absolute;
            bottom: 100%;
            right: 0;
            background: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            white-space: nowrap;
            margin-bottom: 10px;
            opacity: 0;
            transform: translateY(10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .chatbot-tooltip::after {
            content: '';
            position: absolute;
            top: 100%;
            right: 20px;
            border: 5px solid transparent;
            border-top-color: rgba(0, 0, 0, 0.8);
        }

        .chatbot-icon:hover .chatbot-tooltip {
            opacity: 1;
            transform: translateY(0);
        }

        /* Animations */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        @keyframes bounce {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.2);
            }
        }

        @keyframes rotate {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body>
    <!-- Cyber Grid Background -->
    <div class="cyber-grid"></div>

    <div class="dashboard-container">
        <!-- Cyber Sidebar -->
        <div class="cyber-sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-terminal"></i> CYBERADMIN</h2>
                <p>SYSTEM CONTROL PANEL</p>
            </div>
            <div class="cyber-menu">
                <!--<a href="#" class="cyber-menu-item active">
                    <i class="fas fa-desktop"></i>
                    <span>DASHBOARD</span>
                </a>
                <a href="#" class="cyber-menu-item">
                    <i class="fas fa-users-cog"></i>
                    <span>USER MANAGEMENT</span>
                </a>
                <a href="#" class="cyber-menu-item">
                    <i class="fas fa-chart-network"></i>
                    <span>ANALYTICS</span>
                </a>
                <a href="#" class="cyber-menu-item">
                    <i class="fas fa-shield-alt"></i>
                    <span>SECURITY</span>
                </a>
                <a href="#" class="cyber-menu-item">
                    <i class="fas fa-cogs"></i>
                    <span>SYSTEM SETTINGS</span>
                </a>-->
            </div>
        </div>

        <!-- Main Content -->
        <div class="cyber-main">
            <div class="cyber-header">
                <h1><i class="fas fa-terminal"></i> ADMINISTRATOR DASHBOARD</h1>
                <div class="cyber-user-info">
                    <div class="cyber-avatar">
                        <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                    </div>
                    <div>
                        <div><strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></div>
                        <div style="color: var(--neon-green);">ROOT ACCESS</div>
                    </div>
                    <a href="logout.php" class="cyber-logout">
                        <i class="fas fa-power-off"></i> LOGOUT
                    </a>
                </div>
            </div>

            <!-- Cyber Stats -->
            <div class="cyber-stats">
                <div class="cyber-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users" style="color: var(--neon-blue);"></i>
                    </div>
                    <div class="cyber-stat-info">
                        <h3><?php echo count($users); ?></h3>
                        <p>TOTAL USERS</p>
                    </div>
                </div>
                <div class="cyber-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-graduate" style="color: var(--neon-green);"></i>
                    </div>
                    <div class="cyber-stat-info">
                        <h3><?php echo count($students); ?></h3>
                        <p>STUDENTS</p>
                    </div>
                </div>
                <div class="cyber-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chalkboard-teacher" style="color: var(--neon-purple);"></i>
                    </div>
                    <div class="cyber-stat-info">
                        <h3><?php echo count($staff); ?></h3>
                        <p>STAFF MEMBERS</p>
                    </div>
                </div>
                <div class="cyber-stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-building" style="color: var(--neon-pink);"></i>
                    </div>
                    <div class="cyber-stat-info">
                        <h3><?php echo count($dept_stats); ?></h3>
                        <p>DEPARTMENTS</p>
                    </div>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="cyber-alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <!-- Department Statistics -->
            <div class="cyber-section">
                <h2><i class="fas fa-chart-pie"></i> DEPARTMENT STATISTICS</h2>
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th>DEPARTMENT</th>
                            <th>STUDENT COUNT</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dept_stats as $stat): ?>
                            <tr>
                                <td><strong><?php echo $stat['department']; ?></strong></td>
                                <td>
                                    <span style="color: var(--neon-blue); font-weight: 600;">
                                        <?php echo $stat['count']; ?> USERS
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- User Management -->
            <div class="cyber-section">
                <h2><i class="fas fa-users-cog"></i> USER MANAGEMENT</h2>
                <button class="cyber-btn cyber-btn-success" onclick="openAddUserModal()">
                    <i class="fas fa-user-plus"></i> ADD NEW USER
                </button>
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th>USER ID</th>
                            <th>USERNAME</th>
                            <th>EMAIL</th>
                            <th>ROLE</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong>#<?php echo $user['user_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="cyber-badge <?php echo 'badge-' . $user['role']; ?>">
                                        <i class="fas fa-<?php echo $user['role'] == 'admin' ? 'crown' : ($user['role'] == 'staff' ? 'chalkboard-teacher' : 'user-graduate'); ?>"></i>
                                        <?php echo strtoupper($user['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="cyber-btn cyber-btn-primary action-btn" onclick="editUser(<?php echo $user['user_id']; ?>)">
                                            <i class="fas fa-edit"></i> EDIT
                                        </button>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="delete_user" value="1">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <button type="submit" class="cyber-btn cyber-btn-danger action-btn" onclick="return confirm('CONFIRM USER DELETION?')">
                                                <i class="fas fa-trash"></i> DELETE
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div id="addUserModal" class="cyber-modal">
        <div class="cyber-modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-plus"></i> ADD NEW USER</h3>
                <span class="cyber-close" onclick="closeAddUserModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="add_user" value="1">
                <div class="cyber-form-group">
                    <label>USERNAME:</label>
                    <input type="text" name="username" required placeholder="ENTER USERNAME">
                </div>
                <div class="cyber-form-group">
                    <label>EMAIL:</label>
                    <input type="email" name="email" required placeholder="ENTER EMAIL ADDRESS">
                </div>
                <div class="cyber-form-group">
                    <label>PASSWORD:</label>
                    <input type="password" name="password" required placeholder="ENTER PASSWORD">
                </div>
                <div class="cyber-form-group">
                    <label>ROLE:</label>
                    <select name="role" id="roleSelect" onchange="toggleRoleFields()" required>
                        <option value="">SELECT ROLE</option>
                        <option value="student">STUDENT</option>
                        <option value="staff">STAFF</option>
                        <option value="admin">ADMIN</option>
                    </select>
                </div>

                <div id="studentFields">
                    <div class="cyber-form-group">
                        <label>FULL NAME:</label>
                        <input type="text" name="name" placeholder="ENTER FULL NAME">
                    </div>
                    <div class="cyber-form-group">
                        <label>DEPARTMENT:</label>
                        <input type="text" name="department" placeholder="ENTER DEPARTMENT">
                    </div>
                    <div class="cyber-form-group">
                        <label>SEMESTER:</label>
                        <input type="number" name="semester" min="1" max="8" placeholder="ENTER SEMESTER">
                    </div>
                </div>

                <div id="staffFields">
                    <div class="cyber-form-group">
                        <label>FULL NAME:</label>
                        <input type="text" name="name" placeholder="ENTER FULL NAME">
                    </div>
                    <div class="cyber-form-group">
                        <label>DEPARTMENT:</label>
                        <input type="text" name="department" placeholder="ENTER DEPARTMENT">
                    </div>
                    <div class="cyber-form-group">
                        <label>DESIGNATION:</label>
                        <input type="text" name="designation" placeholder="ENTER DESIGNATION">
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="cyber-btn cyber-btn-danger" onclick="closeAddUserModal()">
                        <i class="fas fa-times"></i> CANCEL
                    </button>
                    <button type="submit" class="cyber-btn cyber-btn-success">
                        <i class="fas fa-check"></i> ADD USER
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddUserModal() {
            document.getElementById('addUserModal').style.display = 'block';
            document.querySelector('#addUserModal form').reset();
            document.getElementById('studentFields').style.display = 'none';
            document.getElementById('staffFields').style.display = 'none';
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').style.display = 'none';
        }

        function toggleRoleFields() {
            var role = document.getElementById('roleSelect').value;
            document.getElementById('studentFields').style.display = 'none';
            document.getElementById('staffFields').style.display = 'none';

            if (role === 'student') {
                document.getElementById('studentFields').style.display = 'block';
            } else if (role === 'staff') {
                document.getElementById('staffFields').style.display = 'block';
            }
        }

        function editUser(userId) {
            window.location.href = 'edit_user.php?id=' + userId;
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('addUserModal');
            if (event.target == modal) closeAddUserModal();
        }

        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAddUserModal();
            }
        });
        // Chatbot functionality
        let chatbotOpen = false;

        function toggleChatbot() {
            const modal = document.getElementById('chatbotModal');
            chatbotOpen = !chatbotOpen;
            modal.style.display = chatbotOpen ? 'block' : 'none';
            
            // Remove notification when opened
            if (chatbotOpen) {
                document.querySelector('.chatbot-notification').style.display = 'none';
            }
        }

        function sendMessage() {
            const input = document.getElementById('chatbotInput');
            const message = input.value.trim();
            
            if (message) {
                addMessage(message, 'user');
                input.value = '';
                
                // Simulate bot response
                setTimeout(() => {
                    generateBotResponse(message);
                }, 1000);
            }
        }

        function sendQuickQuestion(question) {
            addMessage(question, 'user');
            
            // Simulate bot response
            setTimeout(() => {
                generateBotResponse(question);
            }, 1000);
        }

        function addMessage(text, sender) {
            const body = document.getElementById('chatbotBody');
            const messageDiv = document.createElement('div');
            messageDiv.className = `chatbot-message ${sender}`;
            
            if (sender === 'bot') {
                messageDiv.innerHTML = `
                    <div class="message-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="message-content">${text}</div>
                `;
            } else {
                messageDiv.innerHTML = `
                    <div class="message-content">${text}</div>
                `;
            }
            
            body.appendChild(messageDiv);
            body.scrollTop = body.scrollHeight;
        }

        function generateBotResponse(userMessage) {
            let response = "I understand you're asking about: '" + userMessage + "'. In a real implementation, I would connect to an AI service to provide detailed assistance.";
            
            // Simple response logic
            const lowerMessage = userMessage.toLowerCase();
            
            if (lowerMessage.includes('add') && lowerMessage.includes('user')) {
                response = "To add a new user:\n1. Click the 'Add New User' button\n2. Fill in the required information\n3. Select the user role\n4. Click 'Add User' to save";
            } else if (lowerMessage.includes('department') || lowerMessage.includes('statistics')) {
                response = "Department statistics show the distribution of students across different departments. You can view this in the 'Department Statistics' section above.";
            } else if (lowerMessage.includes('user management')) {
                response = "User management allows you to:\n• View all users\n• Add new users\n• Edit existing users\n• Delete users\nUse the table above to manage your users efficiently.";
            } else if (lowerMessage.includes('hello') || lowerMessage.includes('hi')) {
                response = "Hello! I'm here to help you with the EduSync admin dashboard. You can ask me about user management, statistics, or any other features!";
            }
            
            addMessage(response, 'bot');
        }

        function handleKeyPress(event) {
            if (event.key === 'Enter') {
                sendMessage();
            }
        }

        // Close chatbot when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('chatbotModal');
            const icon = document.querySelector('.chatbot-icon');
            
            if (chatbotOpen && !modal.contains(event.target) && !icon.contains(event.target)) {
                toggleChatbot();
            }
        });
    </script>
    <!-- Chatbot Widget -->
    <div class="chatbot-container">
        <div class="chatbot-icon" onclick="toggleChatbot()">
            <div class="chatbot-pulse"></div>
            <i class="fas fa-robot"></i>
            <div class="chatbot-notification">1</div>
        </div>
    </div>

    <!-- Chatbot Widget -->
    <div class="chatbot-container">
        <a href="data_con/index.html" class="chatbot-icon">
            <div class="chatbot-pulse"></div>
            <i class="fas fa-robot"></i>
            <div class="chatbot-notification">AI</div>
            <div class="chatbot-tooltip">Talk to DeepSeek AI Assistant</div>
        </a>
    </div>
</body>

</html>