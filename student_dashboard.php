<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
}

// Get student data FIRST before any processing
$stmt = $pdo->prepare("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

// Check if student record exists BEFORE proceeding
if (!$student) {
    die("Student record not found! Please contact administrator.");
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $stmt = $pdo->prepare("UPDATE students SET name = ?, email = ?, phone = ? WHERE user_id = ?");
    if ($stmt->execute([$name, $email, $phone, $_SESSION['user_id']])) {
        $success = "Profile updated successfully!";
        // Refresh student data
        $stmt = $pdo->prepare("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $student = $stmt->fetch();
    } else {
        $error = "Error updating profile!";
    }
}

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = $_POST['assignment_id'];

    // Handle file upload
    $target_dir = "uploads/assignments/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $file_name = time() . '_' . basename($_FILES["assignment_file"]["name"]);
    $file_path = $target_dir . $file_name;

    // Check if already submitted
    $check_stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
    $check_stmt->execute([$assignment_id, $student['student_id']]);

    if ($check_stmt->fetch()) {
        $error = "You have already submitted this assignment!";
    } else {
        if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $file_path)) {
            $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, submitted_on) VALUES (?, ?, ?, NOW())");
            if ($stmt->execute([$assignment_id, $student['student_id'], $file_path])) {
                $success = "Assignment submitted successfully!";
            } else {
                $error = "Error submitting assignment!";
            }
        } else {
            $error = "Error uploading file!";
        }
    }
}

// Handle event addition with certificate
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_event'])) {
    $event_name = $_POST['event_name'];
    $event_location = $_POST['event_location'];
    $prize_won = isset($_POST['prize_won']) ? 1 : 0;
    $secured_place = $_POST['secured_place'];
    $certificate_path = null;

    // Handle certificate upload
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
        $target_dir = "uploads/certificates/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES["certificate_file"]["name"]);
        $certificate_path = $target_dir . $file_name;

        if (!move_uploaded_file($_FILES["certificate_file"]["tmp_name"], $certificate_path)) {
            $error = "Error uploading certificate file!";
            $certificate_path = null;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO events (user_id, event_name, event_location, prize_won, secured_place, certificate_path) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$_SESSION['user_id'], $event_name, $event_location, $prize_won, $secured_place, $certificate_path])) {
        $success = "Event added successfully!" . ($certificate_path ? " Certificate uploaded." : "");
    } else {
        $error = "Error adding event!";
    }
}

// Handle certificate update for existing event
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_certificate'])) {
    $event_id = $_POST['event_id'];

    // Handle certificate upload
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == 0) {
        $target_dir = "uploads/certificates/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_name = time() . '_' . basename($_FILES["certificate_file"]["name"]);
        $certificate_path = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["certificate_file"]["tmp_name"], $certificate_path)) {
            $stmt = $pdo->prepare("UPDATE events SET certificate_path = ? WHERE event_id = ? AND user_id = ?");
            if ($stmt->execute([$certificate_path, $event_id, $_SESSION['user_id']])) {
                $success = "Certificate uploaded successfully!";
            } else {
                $error = "Error updating certificate!";
            }
        } else {
            $error = "Error uploading certificate file!";
        }
    } else {
        $error = "Please select a certificate file to upload!";
    }
}

// Get assignments
$assignments_stmt = $pdo->prepare("
    SELECT a.*, s.subject_name, sub.marks, sub.status, sub.submission_id, sub.submitted_on, sub.file_path
    FROM assignments a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    LEFT JOIN submissions sub ON a.assignment_id = sub.assignment_id AND sub.student_id = ?
    ORDER BY a.due_date
");
$assignments_stmt->execute([$student['student_id']]);
$assignments = $assignments_stmt->fetchAll();

// Get attendance
$attendance_stmt = $pdo->prepare("
    SELECT s.subject_name, sa.date, sa.status, sa.remarks 
    FROM student_attendance sa 
    JOIN subjects s ON sa.subject_id = s.subject_id 
    WHERE sa.student_id = ? 
    ORDER BY sa.date DESC 
    LIMIT 10
");
$attendance_stmt->execute([$student['student_id']]);
$attendance = $attendance_stmt->fetchAll();

// Get events data with certificates
$events_stmt = $pdo->prepare("
    SELECT event_id, event_name, event_location, prize_won, secured_place, certificate_path 
    FROM events 
    WHERE user_id = ? 
    ORDER BY event_id DESC
");
$events_stmt->execute([$_SESSION['user_id']]);
$events = $events_stmt->fetchAll();

// Get assignment details for view modal
if (isset($_GET['assignment_id'])) {
    $assignment_id = $_GET['assignment_id'];
    $assignment_details_stmt = $pdo->prepare("
        SELECT a.*, s.subject_name, sub.marks, sub.status, sub.submission_id, sub.submitted_on, sub.file_path
        FROM assignments a 
        JOIN subjects s ON a.subject_id = s.subject_id 
        LEFT JOIN submissions sub ON a.assignment_id = sub.assignment_id AND sub.student_id = ?
        WHERE a.assignment_id = ?
    ");
    $assignment_details_stmt->execute([$student['student_id'], $assignment_id]);
    $assignment_details = $assignment_details_stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard | Academic Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4361ee;
            --primary-dark: #3a0ca3;
            --primary-light: #4895ef;
            --accent: #f72585;
            --success: #4cc9f0;
            --warning: #f8961e;
            --danger: #e63946;
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
        }

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
            text-decoration: none;
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(247, 37, 133, 0.4);
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
        }

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
        }

        .icon-assignments {
            background: var(--gradient-primary);
        }

        .icon-submitted {
            background: var(--gradient-success);
        }

        .icon-pending {
            background: var(--gradient-warning);
        }

        .icon-attendance {
            background: var(--gradient-secondary);
        }

        .stat-info h3 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            color: var(--dark);
            font-weight: 700;
        }

        .stat-info p {
            color: #666;
            font-size: 0.95rem;
            font-weight: 500;
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
        }

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
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
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
        }

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

        .status-submitted {
            background: rgba(76, 201, 240, 0.2);
            color: #4895ef;
            border: 1px solid rgba(76, 201, 240, 0.3);
        }

        .status-pending {
            background: rgba(248, 150, 30, 0.2);
            color: #f8961e;
            border: 1px solid rgba(248, 150, 30, 0.3);
        }

        .status-uploaded {
            background: rgba(67, 97, 238, 0.2);
            color: #4361ee;
            border: 1px solid rgba(67, 97, 238, 0.3);
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
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            color: #aaa;
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
        }

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

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            color: #666;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--dark);
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

        /* Animation Keyframes */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
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
        }

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
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-graduation-cap"></i> EduSync Pro</h2>
                <p>Student Dashboard Portal</p>
            </div>
            <div class="sidebar-menu">
                <a href="#" class="menu-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard Overview</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-book"></i>
                    <span>My Courses</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-file-alt"></i>
                    <span>Assignments</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-chart-bar"></i>
                    <span>Grades & Progress</span>
                </a>
                <a href="#" class="menu-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <h1><i class="fas fa-tachometer-alt"></i> Student Dashboard</h1>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div><strong><?php echo htmlspecialchars($student['name']); ?></strong></div>
                        <div style="color: var(--primary);">Student ID:
                            <?php echo htmlspecialchars($student['username']); ?></div>
                    </div>
                    <a href="logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-container">
                <div class="stat-card">
                    <div class="stat-icon icon-assignments">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($assignments); ?></h3>
                        <p>Total Assignments</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-submitted">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>
                            <?php
                            $submitted = 0;
                            foreach ($assignments as $assignment) {
                                if ($assignment['submission_id'])
                                    $submitted++;
                            }
                            echo $submitted;
                            ?>
                        </h3>
                        <p>Submitted</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-pending">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($assignments) - $submitted; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon icon-attendance">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo count($attendance); ?></h3>
                        <p>Attendance Records</p>
                    </div>
                </div>
            </div>

            <div class="dashboard-section">
                <h2><i class="fas fa-user-graduate"></i> Student Profile</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div
                        style="background: rgba(67, 97, 238, 0.05); padding: 20px; border-radius: 12px; border-left: 4px solid var(--primary);">
                        <div style="font-size: 0.9rem; color: var(--primary); margin-bottom: 8px;">Full Name</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($student['name']); ?></div>
                    </div>
                    <div
                        style="background: rgba(67, 97, 238, 0.05); padding: 20px; border-radius: 12px; border-left: 4px solid var(--primary);">
                        <div style="font-size: 0.9rem; color: var(--primary); margin-bottom: 8px;">Email Address</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <div
                        style="background: rgba(67, 97, 238, 0.05); padding: 20px; border-radius: 12px; border-left: 4px solid var(--primary);">
                        <div style="font-size: 0.9rem; color: var(--primary); margin-bottom: 8px;">Department</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($student['department']); ?></div>
                    </div>
                    <div
                        style="background: rgba(67, 97, 238, 0.05); padding: 20px; border-radius: 12px; border-left: 4px solid var(--primary);">
                        <div style="font-size: 0.9rem; color: var(--primary); margin-bottom: 8px;">Semester</div>
                        <div style="font-weight: 600; font-size: 1.1rem;">
                            <?php echo htmlspecialchars($student['semester']); ?></div>
                    </div>
                </div>
                <button class="btn btn-warning" onclick="openProfileModal()" style="margin-top: 20px;">
                    <i class="fas fa-edit"></i> Update Profile Information
                </button>
            </div>

            <div class="dashboard-section">
                <h2><i class="fas fa-file-alt"></i> Course Assignments</h2>
                <?php if (empty($assignments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Assignments Available</h3>
                        <p>You don't have any assignments at the moment.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Assignment Title</th>
                                <th>Due Date</th>
                                <th>Marks</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($assignment['subject_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                                    <td><?php echo htmlspecialchars($assignment['due_date']); ?></td>
                                    <td>
                                        <span
                                            style="font-weight: 600; color: <?php echo $assignment['marks'] ? 'var(--success)' : 'var(--secondary)'; ?>">
                                            <?php echo $assignment['marks'] ?? 'Not graded'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($assignment['submission_id']): ?>
                                            <span class="status-badge status-submitted">
                                                <i class="fas fa-check"></i> <?php echo $assignment['status'] ?? 'Submitted'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?assignment_id=<?php echo $assignment['assignment_id']; ?>"
                                                class="btn btn-primary action-btn">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if (!$assignment['submission_id']): ?>
                                                <button class="btn btn-success action-btn"
                                                    onclick="openSubmitModal(<?php echo $assignment['assignment_id']; ?>)">
                                                    <i class="fas fa-upload"></i> Submit
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <h2><i class="fas fa-calendar-check"></i> Recent Attendance</h2>
                <?php if (empty($attendance)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Attendance Records</h3>
                        <p>Your attendance records will appear here once available.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Subject</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance as $record): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($record['subject_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($record['date']); ?></td>
                                    <td>
                                        <span
                                            class="status-badge <?php echo $record['status'] == 'present' ? 'status-present' : 'status-absent'; ?>">
                                            <i
                                                class="fas fa-<?php echo $record['status'] == 'present' ? 'check' : 'times'; ?>"></i>
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($record['remarks']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="dashboard-section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h2><i class="fas fa-calendar-alt"></i> Events & Achievements</h2>
                    <button class="btn btn-primary" onclick="openAddEventModal()">
                        <i class="fas fa-plus"></i> Add Event
                    </button>
                </div>
                <?php if (empty($events)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-plus"></i>
                        <h3>No Events Recorded</h3>
                        <p>Your event participations and achievements will appear here.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Event Name</th>
                                <th>Location</th>
                                <th>Prize Won</th>
                                <th>Secured Place</th>
                                <th>Certificate</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($event['event_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($event['event_location']); ?></td>
                                    <td>
                                        <span
                                            class="status-badge <?php echo $event['prize_won'] ? 'status-submitted' : 'status-pending'; ?>">
                                            <i class="fas fa-<?php echo $event['prize_won'] ? 'trophy' : 'medal'; ?>"></i>
                                            <?php echo $event['prize_won'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($event['secured_place']): ?>
                                            <span style="font-weight: 600; color: var(--success);">
                                                <?php echo htmlspecialchars($event['secured_place']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--secondary);">Not specified</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($event['certificate_path']): ?>
                                            <span class="status-badge status-uploaded">
                                                <i class="fas fa-file-certificate"></i> Uploaded
                                            </span>
                                        <?php else: ?>
                                            <span class="status-badge status-pending">
                                                <i class="fas fa-times"></i> Not Uploaded
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if ($event['certificate_path']): ?>
                                                <a href="<?php echo htmlspecialchars($event['certificate_path']); ?>"
                                                    class="btn btn-primary action-btn" target="_blank" download>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-warning action-btn"
                                                onclick="openUploadCertificateModal(<?php echo $event['event_id']; ?>)">
                                                <i class="fas fa-upload"></i>
                                                <?php echo $event['certificate_path'] ? 'Update' : 'Upload'; ?>
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

    <!-- Profile Update Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-user-edit"></i> Update Profile</h3>
                <span class="close" onclick="closeProfileModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeProfileModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assignment Submit Modal -->
    <div id="submitModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-upload"></i> Submit Assignment</h3>
                <span class="close" onclick="closeSubmitModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="submit_assignment" value="1">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    <div class="form-group">
                        <label>Upload Assignment File</label>
                        <input type="file" name="assignment_file" class="form-control" required style="padding: 8px;"
                            accept=".pdf,.doc,.docx,.txt">
                        <small style="color: var(--secondary); margin-top: 5px; display: block;">Accepted formats: PDF,
                            DOC, DOCX, TXT</small>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeSubmitModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Submit Assignment
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div id="addEventModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-calendar-plus"></i> Add New Event</h3>
                <span class="close" onclick="closeAddEventModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_event" value="1">
                    <div class="form-group">
                        <label>Event Name *</label>
                        <input type="text" name="event_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Event Location</label>
                        <input type="text" name="event_location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="prize_won" value="1" style="width: auto;">
                            Prize Won
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Secured Place</label>
                        <input type="text" name="secured_place" class="form-control" placeholder="e.g., 1st, 2nd, 3rd">
                    </div>
                    <div class="form-group">
                        <label>Upload Certificate (Optional)</label>
                        <input type="file" name="certificate_file" class="form-control" style="padding: 8px;"
                            accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color: var(--secondary); margin-top: 5px; display: block;">Accepted formats: PDF,
                            JPG, JPEG, PNG</small>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeAddEventModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus"></i> Add Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Certificate Modal -->
    <div id="uploadCertificateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-file-certificate"></i> Upload Certificate</h3>
                <span class="close" onclick="closeUploadCertificateModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_certificate" value="1">
                    <input type="hidden" name="event_id" id="certificate_event_id">
                    <div class="form-group">
                        <label>Select Certificate File</label>
                        <input type="file" name="certificate_file" class="form-control" required style="padding: 8px;"
                            accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color: var(--secondary); margin-top: 5px; display: block;">Accepted formats: PDF,
                            JPG, JPEG, PNG</small>
                    </div>
                    <div class="modal-buttons">
                        <button type="button" class="btn btn-danger" onclick="closeUploadCertificateModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Upload Certificate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assignment View Modal -->
    <?php if (isset($_GET['assignment_id']) && isset($assignment_details)): ?>
        <div id="viewAssignmentModal" class="modal" style="display: block;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3><i class="fas fa-file-alt"></i> Assignment Details</h3>
                    <a href="?" class="close">&times;</a>
                </div>
                <div class="modal-body">
                    <div style="margin-bottom: 24px;">
                        <h3 style="color: var(--primary); margin-bottom: 16px;">
                            <?php echo htmlspecialchars($assignment_details['title']); ?></h3>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                            <div style="padding: 12px; background: rgba(67, 97, 238, 0.05); border-radius: 8px;">
                                <div style="font-size: 0.875rem; color: var(--primary); margin-bottom: 4px;">Subject</div>
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($assignment_details['subject_name']); ?></div>
                            </div>
                            <div style="padding: 12px; background: rgba(67, 97, 238, 0.05); border-radius: 8px;">
                                <div style="font-size: 0.875rem; color: var(--primary); margin-bottom: 4px;">Due Date</div>
                                <div style="font-weight: 600;">
                                    <?php echo htmlspecialchars($assignment_details['due_date']); ?></div>
                            </div>
                            <div style="padding: 12px; background: rgba(67, 97, 238, 0.05); border-radius: 8px;">
                                <div style="font-size: 0.875rem; color: var(--primary); margin-bottom: 4px;">Status</div>
                                <div>
                                    <?php if ($assignment_details['submission_id']): ?>
                                        <span class="status-badge status-submitted">
                                            <i class="fas fa-check"></i> Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div style="padding: 12px; background: rgba(67, 97, 238, 0.05); border-radius: 8px;">
                                <div style="font-size: 0.875rem; color: var(--primary); margin-bottom: 4px;">Marks</div>
                                <div
                                    style="font-weight: 600; color: <?php echo $assignment_details['marks'] ? 'var(--success)' : 'var(--secondary)'; ?>">
                                    <?php echo $assignment_details['marks'] ?? 'Not graded'; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($assignment_details['description']): ?>
                            <div
                                style="margin-top: 20px; padding: 16px; background: rgba(67, 97, 238, 0.05); border-radius: 8px; border-left: 4px solid var(--primary);">
                                <h4 style="color: var(--primary); margin-bottom: 8px;">Assignment Description</h4>
                                <p><?php echo htmlspecialchars($assignment_details['description']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($assignment_details['submission_id']): ?>
                            <div
                                style="margin-top: 20px; padding: 16px; background: rgba(67, 97, 238, 0.05); border-radius: 8px; border-left: 4px solid var(--success);">
                                <h4 style="color: var(--success); margin-bottom: 8px;">Submission Details</h4>
                                <p><strong>Submitted on:</strong>
                                    <?php echo htmlspecialchars($assignment_details['submitted_on']); ?></p>
                                <p><strong>File:</strong>
                                    <?php if ($assignment_details['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($assignment_details['file_path']); ?>" download
                                            style="color: var(--primary); text-decoration: none;">
                                            <i class="fas fa-download"></i> Download submitted file
                                        </a>
                                    <?php else: ?>
                                        File not available
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-buttons">
                        <a href="?" class="btn btn-primary">
                            <i class="fas fa-times"></i> Close
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Chatbot Widget -->
    <div class="chatbot-container">
        <a href="data_con/index.html" class="chatbot-icon">
            <div class="chatbot-pulse"></div>
            <i class="fas fa-robot"></i>
            <div class="chatbot-notification">AI</div>
            <div class="chatbot-tooltip">Talk to DeepSeek AI Assistant</div>
        </a>
    </div>

    <script>
        // Modal functions
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'block';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').style.display = 'none';
        }

        function openSubmitModal(assignmentId) {
            document.getElementById('assignment_id').value = assignmentId;
            document.getElementById('submitModal').style.display = 'block';
        }

        function closeSubmitModal() {
            document.getElementById('submitModal').style.display = 'none';
        }

        function openAddEventModal() {
            document.getElementById('addEventModal').style.display = 'block';
        }

        function closeAddEventModal() {
            document.getElementById('addEventModal').style.display = 'none';
        }

        function openUploadCertificateModal(eventId) {
            document.getElementById('certificate_event_id').value = eventId;
            document.getElementById('uploadCertificateModal').style.display = 'block';
        }

        function closeUploadCertificateModal() {
            document.getElementById('uploadCertificateModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modals = document.getElementsByClassName('modal');
            for (let modal of modals) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            }
        }

        // Add keyboard accessibility
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeProfileModal();
                closeSubmitModal();
                closeAddEventModal();
                closeUploadCertificateModal();
            }
        });

        // Add counting animation to stats
        document.addEventListener('DOMContentLoaded', function () {
            const statNumbers = document.querySelectorAll('.stat-info h3');
            statNumbers.forEach(stat => {
                const target = parseInt(stat.textContent);
                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        stat.textContent = target;
                        clearInterval(timer);
                    } else {
                        stat.textContent = Math.floor(current);
                    }
                }, 30);
            });
        });
    </script>
</body>

</html>