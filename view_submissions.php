<?php
session_start(); // Add session_start() at top
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: login.php');
    exit();
}

$assignment_id = $_GET['assignment_id'];

// Get assignment details
$assignment_stmt = $pdo->prepare("
    SELECT a.*, s.subject_name 
    FROM assignments a 
    JOIN subjects s ON a.subject_id = s.subject_id 
    WHERE a.assignment_id = ?
");
$assignment_stmt->execute([$assignment_id]);
$assignment = $assignment_stmt->fetch();

// Get submissions
$submissions_stmt = $pdo->prepare("
    SELECT sub.*, stu.name as student_name, stu.email 
    FROM submissions sub 
    JOIN students stu ON sub.student_id = stu.student_id 
    WHERE sub.assignment_id = ?
");
$submissions_stmt->execute([$assignment_id]);
$submissions = $submissions_stmt->fetchAll();

// Handle grading
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = $_POST['submission_id'];
    $marks = $_POST['marks'];

    $stmt = $pdo->prepare("UPDATE submissions SET marks = ?, status = 'graded' WHERE submission_id = ?");
    $stmt->execute([$marks, $submission_id]);
    $success = "Submission graded successfully!";

    // Refresh submissions
    $submissions_stmt->execute([$assignment_id]);
    $submissions = $submissions_stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>View Submissions</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        .header {
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background: #f8f9fa;
        }

        .btn {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }

        .success {
            color: green;
            margin: 10px 0;
        }
    </style>
</head>

<body>
    <div class="header">
        <a href="staff_dashboard.php" class="btn">Back to Dashboard</a>
        <h1>Submissions for: <?php echo $assignment['title']; ?></h1>
        <p>Subject: <?php echo $assignment['subject_name']; ?></p>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>Student Name</th>
                <th>Email</th>
                <th>Submitted On</th>
                <th>File</th>
                <th>Marks</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($submissions as $submission): ?>
                <tr>
                    <td><?php echo $submission['student_name']; ?></td>
                    <td><?php echo $submission['email']; ?></td>
                    <td><?php echo $submission['submitted_on']; ?></td>
                    <td><?php echo $submission['file_path']; ?></td>
                    <td><?php echo $submission['marks'] ?? 'Not graded'; ?></td>
                    <td><?php echo $submission['status']; ?></td>
                    <td>
                        <?php if ($submission['status'] == 'submitted'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="grade_submission" value="1">
                                <input type="hidden" name="submission_id" value="<?php echo $submission['submission_id']; ?>">
                                <input type="number" name="marks" min="0" max="<?php echo $assignment['max_marks']; ?>"
                                    placeholder="Marks" style="width: 80px; padding: 4px;">
                                <button type="submit" class="btn" style="padding: 4px 8px;">Grade</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>

</html>