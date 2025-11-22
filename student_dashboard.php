<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'student') {
    header('Location: login.php');
    exit();
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

    // Get student_id
    $stmt = $pdo->prepare("SELECT student_id FROM students WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $student_data = $stmt->fetch();

    // Check if already submitted
    $check_stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
    $check_stmt->execute([$assignment_id, $student_data['student_id']]);

    if ($check_stmt->fetch()) {
        $error = "You have already submitted this assignment!";
    } else {
        if (move_uploaded_file($_FILES["assignment_file"]["tmp_name"], $file_path)) {
            $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, submitted_on) VALUES (?, ?, ?, NOW())");
            if ($stmt->execute([$assignment_id, $student_data['student_id'], $file_path])) {
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

// Get student data
$stmt = $pdo->prepare("SELECT s.*, u.username FROM students s JOIN users u ON s.user_id = u.user_id WHERE u.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student = $stmt->fetch();

if (!$student) {
    die("Student record not found!");
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c5530;
            --primary-dark: #1e3a24;
            --primary-light: #4a7c59;
            --secondary: #6b7280;
            --accent: #8b5cf6;
            --success: #059669;
            --warning: #d97706;
            --danger: #dc2626;
            --light: #f8fafc;
            --dark: #1f2937;
            --border: #e5e7eb;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--primary);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }

        .welcome-section h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 8px;
        }

        .welcome-section p {
            color: var(--secondary);
            font-size: 1.1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 16px;
            background: var(--light);
            padding: 12px 20px;
            border-radius: 8px;
            border: 1px solid var(--border);
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--border);
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-title i {
            color: var(--primary-light);
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .info-item {
            padding: 16px;
            background: var(--light);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .info-label {
            font-size: 0.875rem;
            color: var(--secondary);
            text-transform: uppercase;
            font-weight: 500;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: var(--primary);
            color: white;
            font-weight: 500;
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid var(--primary-dark);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background: #f9fafb;
        }

        .status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .status-present {
            background: #d1fae5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .status-absent {
            background: #fee2e2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .status-submitted {
            background: #d1fae5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .status-pending {
            background: #fef3c7;
            color: var(--warning);
            border: 1px solid #fde68a;
        }

        .status-uploaded {
            background: #dbeafe;
            color: #1d4ed8;
            border: 1px solid #93c5fd;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            font-family: inherit;
        }

        .btn:focus {
            outline: 2px solid var(--primary-light);
            outline-offset: 2px;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
        }

        .btn-success {
            background: var(--success);
            color: white;
        }

        .btn-success:hover {
            background: #047857;
        }

        .btn-warning {
            background: var(--warning);
            color: white;
        }

        .btn-warning:hover {
            background: #b45309;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background: #b91c1c;
        }

        .btn-info {
            background: #0ea5e9;
            color: white;
        }

        .btn-info:hover {
            background: #0284c7;
        }

        .btn-outline {
            background: transparent;
            color: var(--primary);
            border: 1px solid var(--primary);
        }

        .btn-outline:hover {
            background: var(--primary);
            color: white;
        }

        .btn-logout {
            background: var(--secondary);
            color: white;
        }

        .btn-logout:hover {
            background: #4b5563;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            box-shadow: var(--shadow-lg);
            animation: modalSlideIn 0.3s ease;
            position: relative;
        }

        @keyframes modalSlideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .close {
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--secondary);
            transition: color 0.3s ease;
            background: none;
            border: none;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .close:hover {
            color: var(--danger);
        }

        .modal-body {
            padding: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 85, 48, 0.1);
        }

        .form-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
            margin-top: 24px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d1fae5;
            color: var(--success);
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 8px;
            color: var(--secondary);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .assignment-details {
            margin-bottom: 24px;
        }

        .assignment-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-top: 16px;
        }

        .assignment-detail-item {
            padding: 12px;
            background: var(--light);
            border-radius: 8px;
        }

        .assignment-detail-label {
            font-size: 0.875rem;
            color: var(--secondary);
            margin-bottom: 4px;
        }

        .assignment-detail-value {
            font-weight: 600;
            color: var(--dark);
        }

        .assignment-description {
            margin-top: 20px;
            padding: 16px;
            background: var(--light);
            border-radius: 8px;
            border-left: 4px solid var(--primary);
        }

        .assignment-description h4 {
            margin-bottom: 8px;
            color: var(--primary);
        }

        .certificate-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .file-info {
            font-size: 0.875rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        @media (max-width: 768px) {
            .assignment-details-grid {
                grid-template-columns: 1fr;
            }

            .certificate-actions {
                flex-direction: column;
                align-items: flex-start;
            }
        }

        /* Chatbot Styles */
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
    <div class="container">
        <div class="header">
            <div class="header-content">
                <div class="welcome-section">
                    <h1>Academic Student Portal</h1>
                    <p>Welcome to your personalized dashboard</p>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($student['name'], 0, 1)); ?>
                    </div>
                    <div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($student['name']); ?></div>
                        <div style="font-size: 0.9rem; color: var(--secondary);">Student ID:
                            <?php echo htmlspecialchars($student['username']); ?>
                        </div>
                    </div>
                    <a href="logout.php" class="btn btn-logout">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
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

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($assignments); ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php
                    $submitted = 0;
                    foreach ($assignments as $assignment) {
                        if ($assignment['submission_id'])
                            $submitted++;
                    }
                    echo $submitted;
                    ?>
                </div>
                <div class="stat-label">Submitted</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($assignments) - $submitted; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($attendance); ?></div>
                <div class="stat-label">Attendance Records</div>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-user-graduate"></i> Student Profile</h2>
                </div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['name']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email Address</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['phone']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Department</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['department']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Semester</div>
                        <div class="info-value"><?php echo htmlspecialchars($student['semester']); ?></div>
                    </div>
                </div>
                <button class="btn btn-warning" onclick="openProfileModal()" style="margin-top: 20px; width: 100%;">
                    <i class="fas fa-edit"></i> Update Profile Information
                </button>
            </div>

            <div class="card">
                <div class="card-header">
                    <h2 class="card-title"><i class="fas fa-calendar-check"></i> Recent Attendance</h2>
                </div>
                <?php if (empty($attendance)): ?>
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h3>No Attendance Records</h3>
                        <p>Your attendance records will appear here once available.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
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
                                                class="status <?php echo $record['status'] == 'present' ? 'status-present' : 'status-absent'; ?>">
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
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-file-alt"></i> Course Assignments</h2>
            </div>
            <?php if (empty($assignments)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Assignments Available</h3>
                    <p>You don't have any assignments at the moment.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
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
                                            <span class="status status-submitted">
                                                <i class="fas fa-check"></i> <?php echo $assignment['status'] ?? 'Submitted'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="status status-pending">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?assignment_id=<?php echo $assignment['assignment_id']; ?>"
                                                class="btn btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if (!$assignment['submission_id']): ?>
                                                <button class="btn btn-success"
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
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-calendar-alt"></i> Events & Achievements</h2>
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
                <div class="table-container">
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
                                            class="status <?php echo $event['prize_won'] ? 'status-submitted' : 'status-pending'; ?>">
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
                                            <span class="status status-uploaded">
                                                <i class="fas fa-file-certificate"></i> Uploaded
                                            </span>
                                        <?php else: ?>
                                            <span class="status status-pending">
                                                <i class="fas fa-times"></i> Not Uploaded
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="certificate-actions">
                                            <?php if ($event['certificate_path']): ?>
                                                <a href="<?php echo htmlspecialchars($event['certificate_path']); ?>"
                                                    class="btn btn-info" target="_blank" download>
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-warning"
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
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Update Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-user-edit"></i> Update Profile</h3>
                <button class="close" onclick="closeProfileModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control"
                            value="<?php echo htmlspecialchars($student['name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone Number</label>
                        <input type="text" name="phone" class="form-control"
                            value="<?php echo htmlspecialchars($student['phone']); ?>">
                    </div>
                    <div class="form-actions">
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
                <h3 class="modal-title"><i class="fas fa-file-upload"></i> Submit Assignment</h3>
                <button class="close" onclick="closeSubmitModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="submit_assignment" value="1">
                    <input type="hidden" name="assignment_id" id="assignment_id">
                    <div class="form-group">
                        <label class="form-label">Upload Assignment File</label>
                        <input type="file" name="assignment_file" class="form-control" required style="padding: 8px;"
                            accept=".pdf,.doc,.docx,.txt">
                        <small style="color: var(--secondary); margin-top: 5px; display: block;">Accepted formats: PDF,
                            DOC, DOCX, TXT</small>
                    </div>
                    <div class="form-actions">
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
                <h3 class="modal-title"><i class="fas fa-calendar-plus"></i> Add New Event</h3>
                <button class="close" onclick="closeAddEventModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_event" value="1">
                    <div class="form-group">
                        <label class="form-label">Event Name *</label>
                        <input type="text" name="event_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Event Location</label>
                        <input type="text" name="event_location" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label" style="display: flex; align-items: center; gap: 8px;">
                            <input type="checkbox" name="prize_won" value="1" style="width: auto;">
                            Prize Won
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Secured Place</label>
                        <input type="text" name="secured_place" class="form-control" placeholder="e.g., 1st, 2nd, 3rd">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Upload Certificate (Optional)</label>
                        <input type="file" name="certificate_file" class="form-control" style="padding: 8px;"
                            accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color: var(--secondary); margin-top: 5px; display: block;">Accepted formats: PDF,
                            JPG, JPEG, PNG</small>
                    </div>
                    <div class="form-actions">
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
                <h3 class="modal-title"><i class="fas fa-file-certificate"></i> Upload Certificate</h3>
                <button class="close" onclick="closeUploadCertificateModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="update_certificate" value="1">
                    <input type="hidden" name="event_id" id="certificate_event_id">
                    <div class="form-group">
                        <label class="form-label">Select Certificate File</label>
                        <input type="file" name="certificate_file" class="form-control" required style="padding: 8px;"
                            accept=".pdf,.jpg,.jpeg,.png">
                        <small style="color: var(--secondary); margin-top: 5px; display: block;">Accepted formats: PDF,
                            JPG, JPEG, PNG</small>
                    </div>
                    <div class="form-actions">
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
                    <h3 class="modal-title"><i class="fas fa-file-alt"></i> Assignment Details</h3>
                    <a href="?" class="close">&times;</a>
                </div>
                <div class="modal-body">
                    <div class="assignment-details">
                        <h3><?php echo htmlspecialchars($assignment_details['title']); ?></h3>
                        <div class="assignment-details-grid">
                            <div class="assignment-detail-item">
                                <div class="assignment-detail-label">Subject</div>
                                <div class="assignment-detail-value">
                                    <?php echo htmlspecialchars($assignment_details['subject_name']); ?>
                                </div>
                            </div>
                            <div class="assignment-detail-item">
                                <div class="assignment-detail-label">Due Date</div>
                                <div class="assignment-detail-value">
                                    <?php echo htmlspecialchars($assignment_details['due_date']); ?>
                                </div>
                            </div>
                            <div class="assignment-detail-item">
                                <div class="assignment-detail-label">Status</div>
                                <div class="assignment-detail-value">
                                    <?php if ($assignment_details['submission_id']): ?>
                                        <span class="status status-submitted">
                                            <i class="fas fa-check"></i> Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="status status-pending">
                                            <i class="fas fa-clock"></i> Pending
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="assignment-detail-item">
                                <div class="assignment-detail-label">Marks</div>
                                <div class="assignment-detail-value">
                                    <span
                                        style="font-weight: 600; color: <?php echo $assignment_details['marks'] ? 'var(--success)' : 'var(--secondary)'; ?>">
                                        <?php echo $assignment_details['marks'] ?? 'Not graded'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php if ($assignment_details['description']): ?>
                            <div class="assignment-description">
                                <h4>Assignment Description</h4>
                                <p><?php echo htmlspecialchars($assignment_details['description']); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($assignment_details['submission_id']): ?>
                            <div class="assignment-description">
                                <h4>Submission Details</h4>
                                <p><strong>Submitted on:</strong>
                                    <?php echo htmlspecialchars($assignment_details['submitted_on']); ?></p>
                                <p><strong>File:</strong>
                                    <?php if ($assignment_details['file_path']): ?>
                                        <a href="<?php echo htmlspecialchars($assignment_details['file_path']); ?>" download
                                            class="file-info">
                                            <i class="fas fa-download"></i> Download submitted file
                                        </a>
                                    <?php else: ?>
                                        File not available
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="form-actions">
                        <a href="?" class="btn btn-primary">
                            <i class="fas fa-times"></i> Close
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
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