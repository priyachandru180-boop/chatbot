<?php
session_start(); // Add session_start() at top
require 'config.php';

// For this example, we'll use admin as department head
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: login.php');
    exit();
}

// Handle student addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_student'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $semester = $_POST['semester'];
    $phone = $_POST['phone'];
    $admission_year = $_POST['admission_year'];

    // Create user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'student')");
    $stmt->execute([$username, $email, $password]);
    $user_id = $pdo->lastInsertId();

    // Create student
    $stmt = $pdo->prepare("INSERT INTO students (user_id, name, email, phone, department, admission_year, semester) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $email, $phone, $department, $admission_year, $semester]);
    $success = "Student added successfully!";
}

// Handle staff addition
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_staff'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $name = $_POST['name'];
    $department = $_POST['department'];
    $designation = $_POST['designation'];
    $phone = $_POST['phone'];

    // Create user
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'staff')");
    $stmt->execute([$username, $email, $password]);
    $user_id = $pdo->lastInsertId();

    // Create staff
    $stmt = $pdo->prepare("INSERT INTO staff (user_id, name, email, phone, department, designation) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $email, $phone, $department, $designation]);
    $success = "Staff added successfully!";
}

// Handle student deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_student'])) {
    $student_id = $_POST['student_id'];

    // Get user_id first
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    // Delete student
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);

    // Delete user
    $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->execute([$student['user_id']]);

    $success = "Student deleted successfully!";
}

// Get all department data
$dept_stats_stmt = $pdo->prepare("
    SELECT department, COUNT(*) as student_count 
    FROM students 
    GROUP BY department
");
$dept_stats_stmt->execute();
$department_stats = $dept_stats_stmt->fetchAll();

// Get all staff by department
$staff_dept_stmt = $pdo->prepare("
    SELECT department, COUNT(*) as staff_count 
    FROM staff 
    GROUP BY department
");
$staff_dept_stmt->execute();
$staff_by_dept = $staff_dept_stmt->fetchAll();

// Get all students with details
$all_students_stmt = $pdo->prepare("SELECT * FROM students");
$all_students_stmt->execute();
$all_students = $all_students_stmt->fetchAll();

// Get all staff with details
$all_staff_stmt = $pdo->prepare("SELECT * FROM staff");
$all_staff_stmt->execute();
$all_staff = $all_staff_stmt->fetchAll();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Department Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f4f4f4;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .dashboard-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
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
            border: none;
            cursor: pointer;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-warning {
            background: #ffc107;
            color: black;
        }

        .btn-danger {
            background: #dc3545;
        }

        .logout {
            float: right;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 50%;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
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
        <h1>Department Dashboard</h1>
        <a href="logout.php" class="btn logout">Logout</a>
        <p>Welcome, Department Head (<?php echo $_SESSION['username']; ?>)</p>
    </div>

    <?php if (isset($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="stats-container">
        <div class="stat-card">
            <h3>Total Departments</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo count($department_stats); ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Students</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo count($all_students); ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Staff</h3>
            <p style="font-size: 24px; font-weight: bold;"><?php echo count($all_staff); ?></p>
        </div>
    </div>

    <div class="dashboard-section">
        <h2>Department-wise Statistics</h2>
        <table>
            <thead>
                <tr>
                    <th>Department</th>
                    <th>Number of Students</th>
                    <th>Number of Staff</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($department_stats as $dept):
                    $staff_count = 0;
                    foreach ($staff_by_dept as $staff) {
                        if ($staff['department'] == $dept['department']) {
                            $staff_count = $staff['staff_count'];
                            break;
                        }
                    }
                    ?>
                    <tr>
                        <td><?php echo $dept['department']; ?></td>
                        <td><?php echo $dept['student_count']; ?></td>
                        <td><?php echo $staff_count; ?></td>
                        <td>
                            <button class="btn" onclick="viewDepartment('<?php echo $dept['department']; ?>')">View
                                Details</button>
                            <button class="btn btn-warning"
                                onclick="manageDepartment('<?php echo $dept['department']; ?>')">Manage</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="dashboard-section">
        <h2>All Students (Full Access)</h2>
        <button class="btn btn-success" onclick="openAddStudentModal()">Add Student</button>
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
                <?php foreach ($all_students as $student): ?>
                    <tr>
                        <td><?php echo $student['student_id']; ?></td>
                        <td><?php echo $student['name']; ?></td>
                        <td><?php echo $student['email']; ?></td>
                        <td><?php echo $student['department']; ?></td>
                        <td><?php echo $student['semester']; ?></td>
                        <td>
                            <button class="btn" onclick="viewStudent(<?php echo $student['student_id']; ?>)">View</button>
                            <button class="btn btn-warning"
                                onclick="editStudent(<?php echo $student['student_id']; ?>)">Edit</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="delete_student" value="1">
                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Student Modal -->
    <div id="addStudentModal" class="modal">
        <div class="modal-content">
            <h3>Add New Student</h3>
            <form method="POST">
                <input type="hidden" name="add_student" value="1">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Phone:</label>
                    <input type="text" name="phone">
                </div>
                <div class="form-group">
                    <label>Department:</label>
                    <input type="text" name="department" required>
                </div>
                <div class="form-group">
                    <label>Admission Year:</label>
                    <input type="number" name="admission_year" min="2000" max="2030" value="2024" required>
                </div>
                <div class="form-group">
                    <label>Semester:</label>
                    <input type="number" name="semester" min="1" max="8" required>
                </div>
                <button type="button" class="btn" onclick="closeAddStudentModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Add Student</button>
            </form>
        </div>
    </div>

    <script>
        function openAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'block';
        }

        function closeAddStudentModal() {
            document.getElementById('addStudentModal').style.display = 'none';
        }

        function viewDepartment(deptName) {
            alert('View department: ' + deptName);
        }

        function manageDepartment(deptName) {
            alert('Manage department: ' + deptName);
        }

        function viewStudent(studentId) {
            window.location.href = 'student_details.php?id=' + studentId;
        }

        function editStudent(studentId) {
            window.location.href = 'edit_student.php?id=' + studentId;
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            if (event.target.className === 'modal') {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>

</html>