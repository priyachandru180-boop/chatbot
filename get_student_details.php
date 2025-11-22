<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    http_response_code(403);
    exit('Access denied');
}

if (isset($_GET['student_id'])) {
    $student_id = $_GET['student_id'];
    
    // Get student basic information
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if ($student) {
        // Get event participation for this student
        $events_stmt = $pdo->prepare("
            SELECT e.*, u.username 
            FROM events e 
            JOIN users u ON e.user_id = u.user_id 
            WHERE u.user_id IN (SELECT user_id FROM students WHERE student_id = ?)
            ORDER BY e.created_at DESC
        ");
        $events_stmt->execute([$student_id]);
        $events = $events_stmt->fetchAll();
        
        $response = [
            'student' => $student,
            'events' => $events
        ];
        
        header('Content-Type: application/json');
        echo json_encode($response);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Student not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Student ID required']);
}
?>