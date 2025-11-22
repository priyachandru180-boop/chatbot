<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: login.php');
    exit();
}

// Get staff details first (needed for assignment creation)
$stmt = $pdo->prepare("SELECT s.*, u.username FROM staff s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff = $stmt->fetch();

if (!$staff) {
    die("Staff record not found!");
}

$success = '';
$error = '';

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_assignment'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $max_marks = $_POST['max_marks'];
    $subject_id = $_POST['subject_id'];

    $stmt = $pdo->prepare("INSERT INTO assignments (staff_id, subject_id, title, description, due_date, max_marks, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    if ($stmt->execute([$staff['staff_id'], $subject_id, $title, $description, $due_date, $max_marks])) {
        $success = "Assignment created successfully!";
    } else {
        $error = "Error creating assignment!";
    }
}

// Handle assignment update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_assignment'])) {
    $assignment_id = $_POST['assignment_id'];
    $title = $_POST['title'];
    $description = $_POST['description'];
    $due_date = $_POST['due_date'];
    $max_marks = $_POST['max_marks'];
    $subject_id = $_POST['subject_id'];

    $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, due_date = ?, max_marks = ?, subject_id = ? WHERE assignment_id = ? AND staff_id = ?");
    if ($stmt->execute([$title, $description, $due_date, $max_marks, $subject_id, $assignment_id, $staff['staff_id']])) {
        $success = "Assignment updated successfully!";
    } else {
        $error = "Error updating assignment!";
    }
}

// Handle attendance marking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $student_id = $_POST['student_id'];
    $subject_id = $_POST['subject_id'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    $remarks = $_POST['remarks'];

    // Check if attendance already exists for this student, subject, and date
    $check_stmt = $pdo->prepare("SELECT * FROM student_attendance WHERE student_id = ? AND subject_id = ? AND date = ?");
    $check_stmt->execute([$student_id, $subject_id, $date]);

    if ($check_stmt->fetch()) {
        $error = "Attendance already marked for this student on selected date!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO student_attendance (student_id, subject_id, date, status, remarks) VALUES (?, ?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $subject_id, $date, $status, $remarks])) {
            $success = "Attendance marked successfully!";
        } else {
            $error = "Error marking attendance!";
        }
    }
}

// Get assignments created by this staff
$assignments_stmt = $pdo->prepare("
    SELECT a.*, s.subject_name, COUNT(sub.submission_id) as submissions 
    FROM assignments a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    LEFT JOIN submissions sub ON a.assignment_id = sub.assignment_id 
    WHERE a.staff_id = ? 
    GROUP BY a.assignment_id 
    ORDER BY a.created_at DESC
");
$assignments_stmt->execute([$staff['staff_id']]);
$assignments = $assignments_stmt->fetchAll();

// Get all students
$students_stmt = $pdo->prepare("SELECT * FROM students");
$students_stmt->execute();
$students = $students_stmt->fetchAll();

// Get subjects
$subjects_stmt = $pdo->prepare("SELECT * FROM subjects");
$subjects_stmt->execute();
$subjects = $subjects_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EduSync Pro | Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3a0ca3;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --info: #7209b7;
            --dark: #1a1a2e;
            --light: #f8f9fa;
            --gradient-primary: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            --gradient-secondary: linear-gradient(135deg, #f72585 0%, #b5179e 100%);
            --gradient-success: linear-gradient(135deg, #4cc9f0 0%, #4895ef 100%);
            --gradient-warning: linear-gradient(135deg, #f8961e 0%, #f3722c 100%);
            --card-bg: rgba(255, 255, 255, 0.95);
            --sidebar-bg: linear-gradient(180deg, #1a1a2e 0%, #16213e 100%);
            --glass-effect: rgba(255, 255, 255, 0.1);
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 15px 45px rgba(0, 0, 0, 0.15);
            --radius: 16px;
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
            backdrop-filter: blur(10px);
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: white;
            padding: 0;
            box-shadow: var(--shadow);
            z-index: 100;
            position: relative;
            overflow: hidden;
            animation: slideInLeft 0.8s ease-out;
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-secondary);
        }

        .sidebar-header {
            padding: 30px 25px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            animation: fadeInDown 0.8s ease-out 0.2s both;
        }

        .sidebar-header h2 {
            font-size: 1.6rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar-header p {
            font-size: 0.9rem;
            opacity: 0.8;
            color: #4cc9f0;
        }

        .sidebar-menu {
            padding: 25px 0;
        }

        .menu-item {
            padding: 16px 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 4px solid transparent;
            margin: 5px 15px;
            border-radius: 12px 0 0 12px;
            position: relative;
            overflow: hidden;
            animation: fadeInLeft 0.6s ease-out both;
        }

        .menu-item:nth-child(1) { animation-delay: 0.3s; }
        .menu-item:nth-child(2) { animation-delay: 0.4s; }
        .menu-item:nth-child(3) { animation-delay: 0.5s; }
        .menu-item:nth-child(4) { animation-delay: 0.6s; }
        .menu-item:nth-child(5) { animation-delay: 0.7s; }
        .menu-item:nth-child(6) { animation-delay: 0.8s; }

        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: var(--gradient-primary);
            opacity: 0.1;
            transition: var(--transition);
        }

        .menu-item:hover::before,
        .menu-item.active::before {
            left: 0;
        }

        .menu-item:hover,
        .menu-item.active {
            color: white;
            border-left: 4px solid var(--accent);
            transform: translateX(5px);
        }

        .menu-item i {
            width: 20px;
            text-align: center;
            font-size: 1.2rem;
            z-index: 1;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
            background: rgba(248, 249, 250, 0.1);
            animation: fadeIn 1s ease-out;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 35px;
            background: var(--card-bg);
            padding: 25px 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            animation: slideInDown 0.8s ease-out 0.3s both;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 8px;
            height: 100%;
            background: var(--gradient-secondary);
        }

        .header h1 {
            color: var(--dark);
            font-size: 2rem;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 700;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-avatar {
            width: 55px;
            height: 55px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.4rem;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
            transition: var(--transition);
            animation: bounceIn 1s ease-out 0.5s both;
        }

        .user-avatar:hover {
            transform: scale(1.1) rotate(5deg);
        }

        .logout-btn {
            background: var(--gradient-secondary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
            animation: pulse 2s infinite 1s;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(247, 37, 133, 0.4);
            animation: none;
        }

        /* Card Styles */
        .dashboard-section {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .dashboard-section:nth-child(1) { animation-delay: 0.4s; }
        .dashboard-section:nth-child(2) { animation-delay: 0.5s; }
        .dashboard-section:nth-child(3) { animation-delay: 0.6s; }
        .dashboard-section:nth-child(4) { animation-delay: 0.7s; }

        .dashboard-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }

        .dashboard-section:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }

        .dashboard-section h2 {
            color: var(--dark);
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Button Styles */
        .btn {
            padding: 14px 28px;
            border: none;
            border-radius: 50px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn-primary {
            background: var(--gradient-primary);
            color: white;
            box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 97, 238, 0.4);
        }

        .btn-success {
            background: var(--gradient-success);
            color: white;
            box-shadow: 0 4px 15px rgba(76, 201, 240, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(76, 201, 240, 0.4);
        }

        .btn-warning {
            background: var(--gradient-warning);
            color: white;
            box-shadow: 0 4px 15px rgba(248, 150, 30, 0.3);
        }

        .btn-warning:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(248, 150, 30, 0.4);
        }

        .btn-danger {
            background: var(--gradient-secondary);
            color: white;
            box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
        }

        .btn-danger:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(247, 37, 133, 0.4);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            animation: zoomIn 0.6s ease-out;
        }

        th {
            background: var(--gradient-primary);
            color: white;
            font-weight: 600;
            padding: 18px;
            text-align: left;
            font-size: 0.95rem;
        }

        td {
            padding: 18px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            animation: fadeIn 0.8s ease-out;
        }

        tr {
            animation: slideInRight 0.5s ease-out both;
        }

        tr:nth-child(1) { animation-delay: 0.1s; }
        tr:nth-child(2) { animation-delay: 0.2s; }
        tr:nth-child(3) { animation-delay: 0.3s; }
        tr:nth-child(4) { animation-delay: 0.4s; }
        tr:nth-child(5) { animation-delay: 0.5s; }

        tr:hover td {
            background: rgba(67, 97, 238, 0.05);
            transform: scale(1.01);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 10px 18px;
            border-radius: 8px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: var(--transition);
        }

        /* Status Badges */
        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: tada 1s ease-out;
        }

        .status-present {
            background: rgba(76, 201, 240, 0.2);
            color: #4895ef;
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .status-absent {
            background: rgba(247, 37, 133, 0.2);
            color: #f72585;
            border: 1px solid rgba(247, 37, 133, 0.3);
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            animation: fadeIn 0.3s ease-out;
        }

        .modal-content {
            background: var(--card-bg);
            margin: 5% auto;
            padding: 35px;
            border-radius: var(--radius);
            width: 90%;
            max-width: 650px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: modalAppear 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(-60px) scale(0.9) rotateX(-10deg);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1) rotateX(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(67, 97, 238, 0.1);
        }

        .modal-header h3 {
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .close {
            color: var(--text-light);
            font-size: 30px;
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

        .close:hover {
            color: var(--accent);
            background: rgba(247, 37, 133, 0.1);
            transform: rotate(90deg);
        }

        .form-group {
            margin-bottom: 25px;
            animation: slideInUp 0.5s ease-out both;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid rgba(67, 97, 238, 0.1);
            border-radius: 12px;
            font-size: 1rem;
            transition: var(--transition);
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(67, 97, 238, 0.1);
            transform: translateY(-2px);
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            animation: fadeInUp 0.6s ease-out 0.6s both;
        }

        /* Alert Styles */
        .alert {
            padding: 20px 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            font-weight: 500;
            box-shadow: var(--shadow);
            border-left: 5px solid;
            animation: slideInRight 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .alert-success {
            background: rgba(76, 201, 240, 0.15);
            color: #4895ef;
            border-left-color: #4cc9f0;
        }

        .alert-error {
            background: rgba(247, 37, 133, 0.15);
            color: #f72585;
            border-left-color: #f72585;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 30px;
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: var(--transition);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
            opacity: 0;
            animation: flipInX 0.8s ease-out forwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.2s; }
        .stat-card:nth-child(2) { animation-delay: 0.3s; }
        .stat-card:nth-child(3) { animation-delay: 0.4s; }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient-primary);
        }

        .stat-card:hover {
            transform: translateY(-8px) rotate(1deg);
            box-shadow: var(--shadow-hover);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            animation: bounceIn 1s ease-out;
        }

        .icon-assignments {
            background: var(--gradient-primary);
        }

        .icon-students {
            background: var(--gradient-success);
        }

        .icon-subjects {
            background: var(--gradient-secondary);
        }

        .stat-info h3 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 700;
            animation: countUp 1.5s ease-out;
        }

        .stat-info p {
            color: #666;
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #666;
            animation: fadeIn 1s ease-out;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: bounce 2s infinite;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
        }

        /* Animation Keyframes */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes bounceIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 1;
                transform: scale(1.05);
            }
            70% {
                transform: scale(0.9);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }

        @keyframes flipInX {
            from {
                transform: perspective(400px) rotateX(90deg);
                opacity: 0;
            }
            to {
                transform: perspective(400px) rotateX(0deg);
                opacity: 1;
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: scale3d(0.3, 0.3, 0.3);
            }
            50% {
                opacity: 1;
            }
        }

        @keyframes bounce {
            0%, 20%, 53%, 80%, 100% {
                transform: translate3d(0, 0, 0);
            }
            40%, 43% {
                transform: translate3d(0, -15px, 0);
            }
            70% {
                transform: translate3d(0, -7px, 0);
            }
            90% {
                transform: translate3d(0, -2px, 0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 4px 20px rgba(247, 37, 133, 0.5);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(247, 37, 133, 0.3);
            }
        }

        @keyframes tada {
            0% {
                transform: scale3d(1, 1, 1);
            }
            10%, 20% {
                transform: scale3d(0.9, 0.9, 0.9) rotate3d(0, 0, 1, -3deg);
            }
            30%, 50%, 70%, 90% {
                transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, 3deg);
            }
            40%, 60%, 80% {
                transform: scale3d(1.1, 1.1, 1.1) rotate3d(0, 0, 1, -3deg);
            }
            100% {
                transform: scale3d(1, 1, 1);
            }
        }

        @keyframes countUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* Floating Animation */
        .floating {
            animation: float 6s ease-in-out infinite;
        }

        /* Particle Background */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }

        /* Typewriter Effect */
        .typewriter {
            overflow: hidden;
            border-right: 3px solid var(--primary);
            white-space: nowrap;
            animation: typing 3.5s steps(40, end), blink-caret 0.75s step-end infinite;
        }

        @keyframes typing {
            from { width: 0 }
            to { width: 100% }
        }

        @keyframes blink-caret {
            from, to { border-color: transparent }
            50% { border-color: var(--primary) }
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .dashboard-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
            }
            
            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .header {
                flex-direction: column;
                gap: 20px;
                align-items: flex-start;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
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
    <!-- Animated Background Particles -->
    <div class="particles" id="particles"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap floating"></i> EduSync Pro</h2>
                <p>Staff Dashboard Portal</p>
            </div>
            <!--<div class="sidebar-menu">
                <a href="#" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard Overview</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>Course Management</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-users"></i>
                    <span>Student Portal</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance System</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Analytics & Reports</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>-->
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> <span class="typewriter">Staff Dashboard</span></h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($staff['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div><strong><?php echo htmlspecialchars($staff['name']); ?></strong></div>
                        <div style="color: var(--primary);"><?php echo htmlspecialchars($staff['designation']); ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon icon-assignments">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($assignments); ?></h3>
                        <p>Assignments Created</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-students">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($students); ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-subjects">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($subjects); ?></h3>
                        <p>Active Subjects</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Create Assignment Section -->
            <div class="dashboard-section">
                <h2><i class="fas fa-plus-circle"></i> Create New Assignment</h2>
                <button class="btn btn-success" onclick="openAssignmentModal()">
                    <i class="fas fa-plus"></i> Create New Assignment
                </button>
            </div>

            <!-- Assignments Section -->
            <div class="dashboard-section">
                <h2><i class="fas fa-tasks"></i> My Assignments</h2>
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-tasks"></i>
                        <h3>No Assignments Created</h3>
                        <p>You haven't created any assignments yet. Click the button above to create your first assignment.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Title</th>
                                <th>Due Date</th>
                                <th>Max Marks</th>
                                <th>Submissions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($assignment['subject_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?></td>
                                    <td><span style="font-weight: 600; color: var(--primary);"><?php echo htmlspecialchars($assignment['max_marks']); ?></span></td>
                                    <td>
                                        <span class="status-badge <?php echo $assignment['submissions'] > 0 ? 'status-present' : 'status-absent'; ?>">
                                            <i class="fas fa-paper-plane"></i> <?php echo htmlspecialchars($assignment['submissions']); ?> Submissions
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary action-btn" onclick="viewSubmissions(<?php echo $assignment['assignment_id']; ?>)">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-warning action-btn" onclick="editAssignment(<?php echo $assignment['assignment_id']; ?>, '<?php echo htmlspecialchars($assignment['title']); ?>', '<?php echo htmlspecialchars($assignment['description']); ?>', '<?php echo $assignment['due_date']; ?>', <?php echo $assignment['max_marks']; ?>, <?php echo $assignment['subject_id']; ?>)">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Students Section -->
            <div class="dashboard-section">
                <h2><i class="fas fa-user-graduate"></i> Student Management</h2>
                <?php if (empty($students)): ?>
                    <div class="empty-state">
                        <i class="fas fa-user-graduate"></i>
                        <h3>No Students Found</h3>
                        <p>There are no students registered in the system yet.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Semester</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($student['student_id']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($student['name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['department']); ?></td>
                                    <td><span style="font-weight: 600; color: var(--info);">Sem <?php echo htmlspecialchars($student['semester']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-primary action-btn" onclick="viewStudent(<?php echo $student['student_id']; ?>)">
                                                <i class="fas fa-user"></i> Profile
                                            </button>
                                            <button class="btn btn-success action-btn" onclick="openAttendanceModal(<?php echo $student['student_id']; ?>)">
                                                <i class="fas fa-calendar-check"></i> Attendance
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Create Assignment Modal -->
    <div id="assignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-tasks"></i> Create New Assignment</h3>
                <span class="close" onclick="closeAssignmentModal()">&times;</span>
            </div>
            <form method="POST" id="assignmentForm">
                <input type="hidden" name="create_assignment" value="1">
                <div class="form-group">
                    <label>Subject:</label>
                    <select name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" name="title" required placeholder="Enter assignment title">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" style="height: 120px;"
                        placeholder="Enter assignment description"></textarea>
                </div>
                <div class="form-group">
                    <label>Due Date:</label>
                    <input type="datetime-local" name="due_date" required>
                </div>
                <div class="form-group">
                    <label>Max Marks:</label>
                    <input type="number" name="max_marks" required min="1" max="100" placeholder="Enter maximum marks">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeAssignmentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div id="editAssignmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Assignment</h3>
                <span class="close" onclick="closeEditAssignmentModal()">&times;</span>
            </div>
            <form method="POST" id="editAssignmentForm">
                <input type="hidden" name="update_assignment" value="1">
                <input type="hidden" name="assignment_id" id="edit_assignment_id">
                <div class="form-group">
                    <label>Subject:</label>
                    <select name="subject_id" id="edit_subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Title:</label>
                    <input type="text" name="title" id="edit_title" required placeholder="Enter assignment title">
                </div>
                <div class="form-group">
                    <label>Description:</label>
                    <textarea name="description" id="edit_description" style="height: 120px;"
                        placeholder="Enter assignment description"></textarea>
                </div>
                <div class="form-group">
                    <label>Due Date:</label>
                    <input type="datetime-local" name="due_date" id="edit_due_date" required>
                </div>
                <div class="form-group">
                    <label>Max Marks:</label>
                    <input type="number" name="max_marks" id="edit_max_marks" required min="1" max="100" placeholder="Enter maximum marks">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeEditAssignmentModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Update Assignment
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Modal -->
    <div id="attendanceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-check"></i> Mark Attendance</h3>
                <span class="close" onclick="closeAttendanceModal()">&times;</span>
            </div>
            <form method="POST" id="attendanceForm">
                <input type="hidden" name="mark_attendance" value="1">
                <input type="hidden" name="student_id" id="attendance_student_id">
                <div class="form-group">
                    <label>Subject:</label>
                    <select name="subject_id" required>
                        <option value="">Select Subject</option>
                        <?php foreach ($subjects as $subject): ?>
                            <option value="<?php echo $subject['subject_id']; ?>">
                                <?php echo htmlspecialchars($subject['subject_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Date:</label>
                    <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="present">Present</option>
                        <option value="absent">Absent</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Remarks:</label>
                    <input type="text" name="remarks" placeholder="Enter remarks (optional)">
                </div>
                <div class="modal-buttons">
                    <button type="button" class="btn btn-danger" onclick="closeAttendanceModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Mark Attendance
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Create animated particles
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 15;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random properties
                const size = Math.random() * 60 + 10;
                const posX = Math.random() * 100;
                const posY = Math.random() * 100;
                const delay = Math.random() * 20;
                const duration = Math.random() * 30 + 20;
                
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${posX}%`;
                particle.style.top = `${posY}%`;
                particle.style.animationDelay = `${delay}s`;
                particle.style.animationDuration = `${duration}s`;
                particle.style.opacity = Math.random() * 0.3 + 0.1;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Modal functions
        function openAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'block';
            document.getElementById('assignmentForm').reset();
        }

        function closeAssignmentModal() {
            document.getElementById('assignmentModal').style.display = 'none';
        }

        function openEditAssignmentModal(assignmentId, title, description, dueDate, maxMarks, subjectId) {
            // Fill the form with current assignment data
            document.getElementById('edit_assignment_id').value = assignmentId;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            
            // Format the due date for datetime-local input
            const formattedDueDate = dueDate.replace(' ', 'T');
            document.getElementById('edit_due_date').value = formattedDueDate;
            
            document.getElementById('edit_max_marks').value = maxMarks;
            document.getElementById('edit_subject_id').value = subjectId;
            
            // Show the modal
            document.getElementById('editAssignmentModal').style.display = 'block';
        }

        function closeEditAssignmentModal() {
            document.getElementById('editAssignmentModal').style.display = 'none';
        }

        function openAttendanceModal(studentId) {
            document.getElementById('attendance_student_id').value = studentId;
            document.getElementById('attendanceModal').style.display = 'block';
            document.getElementById('attendanceForm').reset();
            document.querySelector('#attendanceForm input[name="date"]').value = '<?php echo date('Y-m-d'); ?>';
        }

        function closeAttendanceModal() {
            document.getElementById('attendanceModal').style.display = 'none';
        }

        // Action functions
        function viewSubmissions(assignmentId) {
            window.location.href = 'view_submissions.php?assignment_id=' + assignmentId;
        }

        function editAssignment(assignmentId, title, description, dueDate, maxMarks, subjectId) {
            openEditAssignmentModal(assignmentId, title, description, dueDate, maxMarks, subjectId);
        }

        function viewStudent(studentId) {
            openStudentProfileModal(studentId);
        }

        function openStudentProfileModal(studentId) {
            // Show loading state
            document.getElementById('studentProfileContent').innerHTML = `
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p>Loading student information...</p>
                </div>
            `;
            
            // Show the modal
            document.getElementById('studentProfileModal').style.display = 'block';
            
            // Fetch student details via AJAX
            fetchStudentDetails(studentId);
        }

        function closeStudentProfileModal() {
            document.getElementById('studentProfileModal').style.display = 'none';
        }

        function fetchStudentDetails(studentId) {
            fetch(`get_student_details.php?student_id=${studentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('studentProfileContent').innerHTML = `
                            <div style="text-align: center; padding: 40px; color: var(--accent);">
                                <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                                <p>${data.error}</p>
                             </div>
                        `;
                    } else {
                        displayStudentProfile(data.student, data.events);
                    }
                })
                .catch(error => {
                    console.error('Error fetching student details:', error);
                    document.getElementById('studentProfileContent').innerHTML = `
                        <div style="text-align: center; padding: 40px; color: var(--accent);">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2rem;"></i>
                            <p>Error loading student information</p>
                        </div>
                    `;
                });
        }

        function displayStudentProfile(student, events) {
            const eventsHTML = events.length > 0 ? 
                events.map(event => `
                    <div style="background: white; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 4px solid var(--primary);">
                        <div style="display: flex; justify-content: between; align-items: start; margin-bottom: 10px;">
                            <div style="flex: 1;">
                                <strong style="color: var(--dark);">${event.event_name}</strong>
                                ${event.event_location ? `<div style="color: #666; font-size: 0.9rem;"><i class="fas fa-map-marker-alt"></i> ${event.event_location}</div>` : ''}
                            </div>
                            <div style="text-align: right;">
                                ${event.prize_won ? `
                                    <span class="status-badge status-present" style="font-size: 0.8rem;">
                                        <i class="fas fa-trophy"></i> 
                                        ${event.secured_place ? ` ${event.secured_place}` : ' Winner'}
                                    </span>
                                ` : `
                                    <span class="status-badge status-absent" style="font-size: 0.8rem;">
                                        <i class="fas fa-calendar-check"></i> Participated
                                    </span>
                                `}
                            </div>
                        </div>
                        <div style="color: #888; font-size: 0.85rem;">
                            <i class="fas fa-calendar"></i> ${new Date(event.created_at).toLocaleDateString()}
                        </div>
                    </div>
                `).join('') : 
                `<div style="text-align: center; padding: 30px; color: #666;">
                    <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px;"></i>
                    <p>No event participation recorded</p>
                </div>`;

            const profileContent = `
                <div style="padding: 20px;">
                    <div style="text-align: center; margin-bottom: 30px;">
                        <div class="user-avatar" style="margin: 0 auto 15px; width: 80px; height: 80px; font-size: 2rem;">
                            ${student.name.charAt(0).toUpperCase()}
                        </div>
                        <h3 style="color: var(--dark); margin-bottom: 5px;">${student.name}</h3>
                        <p style="color: var(--primary);">Student ID: ${student.student_id}</p>
                    </div>
                    
                    <div style="background: rgba(67, 97, 238, 0.05); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                        <h4 style="color: var(--dark); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-info-circle"></i> Basic Information
                        </h4>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <strong>Email:</strong><br>
                                <span style="color: #666;">${student.email}</span>
                            </div>
                            <div>
                                <strong>Phone:</strong><br>
                                <span style="color: #666;">${student.phone || 'Not provided'}</span>
                            </div>
                            <div>
                                <strong>Department:</strong><br>
                                <span style="color: #666;">${student.department}</span>
                            </div>
                            <div>
                                <strong>Semester:</strong><br>
                                <span style="color: var(--info); font-weight: 600;">${student.semester}</span>
                            </div>
                            <div>
                                <strong>Enrollment Date:</strong><br>
                                <span style="color: #666;">${student.enrollment_date ? new Date(student.enrollment_date).toLocaleDateString() : 'Not available'}</span>
                            </div>
                            <div>
                                <strong>Date of Birth:</strong><br>
                                <span style="color: #666;">${student.date_of_birth ? new Date(student.date_of_birth).toLocaleDateString() : 'Not available'}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background: rgba(76, 201, 240, 0.05); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
                        <h4 style="color: var(--dark); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-map-marker-alt"></i> Contact Information
                        </h4>
                        <div>
                            <strong>Address:</strong><br>
                            <span style="color: #666;">${student.address || 'Not provided'}</span>
                        </div>
                    </div>
                    
                    <div style="background: rgba(247, 37, 133, 0.05); padding: 20px; border-radius: 12px;">
                        <h4 style="color: var(--dark); margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-calendar-alt"></i> Event Participation
                            <span class="status-badge" style="font-size: 0.8rem; background: rgba(67, 97, 238, 0.2); color: var(--primary);">
                                ${events.length} Events
                            </span>
                        </h4>
                        <div style="max-height: 300px; overflow-y: auto;">
                            ${eventsHTML}
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('studentProfileContent').innerHTML = profileContent;
        }

        // Close modal when clicking outside
        // Close modal when clicking outside
        window.onclick = function (event) {
            const assignmentModal = document.getElementById('assignmentModal');
            const editAssignmentModal = document.getElementById('editAssignmentModal');
            const attendanceModal = document.getElementById('attendanceModal');
            const studentProfileModal = document.getElementById('studentProfileModal');

            if (event.target == assignmentModal) closeAssignmentModal();
            if (event.target == editAssignmentModal) closeEditAssignmentModal();
            if (event.target == attendanceModal) closeAttendanceModal();
            if (event.target == studentProfileModal) closeStudentProfileModal();
        }

        // Close modal with Escape key
        // Close modal with Escape key
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAssignmentModal();
                closeEditAssignmentModal();
                closeAttendanceModal();
                closeStudentProfileModal();
            }
        });

        // Form validation
        document.getElementById('assignmentForm').addEventListener('submit', function (e) {
            const dueDate = new Date(this.due_date.value);
            const now = new Date();

            if (dueDate <= now) {
                e.preventDefault();
                alert('Due date must be in the future!');
                return false;
            }
        });

        document.getElementById('editAssignmentForm').addEventListener('submit', function (e) {
            const dueDate = new Date(this.due_date.value);
            const now = new Date();

            if (dueDate <= now) {
                e.preventDefault();
                alert('Due date must be in the future!');
                return false;
            }
        });

        // Set minimum datetime for assignment due date to current time
        document.addEventListener('DOMContentLoaded', function () {
            createParticles();
            
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            
            // Set min for create assignment form
            const dueDateInput = document.querySelector('#assignmentForm input[name="due_date"]');
            if (dueDateInput) {
                dueDateInput.min = localDateTime;
            }
            
            // Set min for edit assignment form
            const editDueDateInput = document.querySelector('#editAssignmentForm input[name="due_date"]');
            if (editDueDateInput) {
                editDueDateInput.min = localDateTime;
            }
        });

        // Chatbot functionality
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
    <!-- Student Profile Modal -->
    <div id="studentProfileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-graduate"></i> Student Profile</h3>
                <span class="close" onclick="closeStudentProfileModal()">&times;</span>
            </div>
            <div id="studentProfileContent">
                <!-- Student details will be loaded here -->
                <div style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary);"></i>
                    <p>Loading student information...</p>
                </div>
            </div>
            <div class="modal-buttons">
                <button type="button" class="btn btn-danger" onclick="closeStudentProfileModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
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