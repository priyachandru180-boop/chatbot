<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSSSIETW</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        :root {
            --primary-blue: #4361ee;
            --accent-pink: #f72585;
            --dark-navy: #1a1a2e;
            --light-bg: #f8f9fa;
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --success: #28a745;
            --warning: #ffc107;
            --danger: #dc3545;
            --info: #17a2b8;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-navy);
            min-height: 100vh;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, rgba(19, 7, 7, 0.61) 100%);
            color: white;
            transition: var(--transition);
            box-shadow: var(--shadow);
            backdrop-filter: blur(10px);
            position: fixed;
            height: 100vh;
            z-index: 100;
            overflow-y: auto;
        }

        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(26, 23, 23, 0.1);
            background: rgba(19, 17, 17, 0.1);
            backdrop-filter: blur(5px);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .sidebar-header p {
            font-size: 0.8rem;
            opacity: 0.8;
            font-weight: 300;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 15px 25px;
            display: flex;
            align-items: center;
            cursor: pointer;
            transition: var(--transition);
            margin: 5px 15px;
            border-radius: 12px;
            font-weight: 500;
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.15);
            transform: translateY(-2px);
        }

        .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid var(--accent-pink);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .menu-item i {
            margin-right: 12px;
            font-size: 1.2rem;
            width: 24px;
            text-align: center;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            margin-left: 280px;
            transition: var(--transition);
        }

        .top-nav {
            background: rgba(255, 255, 255, 0.8);
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            backdrop-filter: blur(10px);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255, 255, 255, 0.6);
            padding: 8px 15px;
            border-radius: 50px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: var(--transition);
            position: relative;
        }

        .user-info:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 2px solid var(--primary-blue);
        }

        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            padding: 10px 0;
            min-width: 180px;
            z-index: 101;
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .user-dropdown.active {
            display: block;
        }

        .user-dropdown-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: var(--transition);
            cursor: pointer;
        }

        .user-dropdown-item:hover {
            background: rgba(67, 97, 238, 0.1);
        }

        .user-dropdown-item i {
            color: var(--primary-blue);
            width: 20px;
        }

        .content {
            padding: 30px;
            flex: 1;
            overflow-y: auto;
        }

        /* Card Styles */
        .card {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 16px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
        }

        .card-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-body {
            padding: 25px;
        }

        /* Stats Cards */
        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow);
            text-align: center;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 180px;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .stat-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, var(--primary-blue), var(--accent-pink));
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            z-index: 1;
        }

        .stat-card h3 {
            font-size: 2.2rem;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--dark-navy);
            position: relative;
            z-index: 1;
        }

        .stat-card p {
            font-size: 0.9rem;
            color: #666;
            font-weight: 500;
            position: relative;
            z-index: 1;
        }

        .stat-card .bg-icon {
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 5rem;
            opacity: 0.1;
            color: var(--primary-blue);
            z-index: 0;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }

        th,
        td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        th {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            font-weight: 600;
            position: sticky;
            top: 0;
        }

        tr {
            transition: var(--transition);
        }

        tr:hover {
            background-color: rgba(67, 97, 238, 0.05);
            transform: translateX(5px);
        }

        /* Badge Styles */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-success {
            background: rgba(40, 167, 69, 0.15);
            color: var(--success);
        }

        .badge-warning {
            background: rgba(255, 193, 7, 0.15);
            color: #856404;
        }

        .badge-danger {
            background: rgba(220, 53, 69, 0.15);
            color: var(--danger);
        }

        .badge-info {
            background: rgba(23, 162, 184, 0.15);
            color: var(--info);
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-navy);
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: var(--transition);
            background: rgba(255, 255, 255, 0.8);
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.2);
            transform: translateY(-2px);
        }

        button {
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: var(--transition);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #3a56e4 0%, #2d0a8c 100%);
        }

        .btn-success {
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #a71e2a 100%);
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning) 0%, #e0a800 100%);
            color: black;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .action-buttons button {
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: var(--transition);
        }

        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            transform: translateY(30px);
            opacity: 0;
            transition: var(--transition);
        }

        .modal-overlay.active .modal {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            padding: 20px 25px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 16px 16px 0 0;
        }

        .modal-body {
            padding: 25px;
        }

        .modal-footer {
            padding: 20px 25px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid #e0e0e0;
        }

        /* Chatbot Styles */
        .chatbot-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 350px;
            height: 500px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
            display: none;
            transform: translateY(20px);
            opacity: 0;
            transition: var(--transition);
        }

        .chatbot-container.active {
            display: flex;
            transform: translateY(0);
            opacity: 1;
        }

        .chatbot-header {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chatbot-body {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .chatbot-footer {
            padding: 15px 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 10px;
        }

        .chatbot-footer input {
            flex: 1;
            margin-right: 0;
        }

        .message {
            padding: 12px 16px;
            border-radius: 18px;
            max-width: 80%;
            animation: fadeIn 0.3s ease;
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

        .user-message {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 5px;
        }

        .bot-message {
            background: #f1f1f1;
            align-self: flex-start;
            border-bottom-left-radius: 5px;
        }

        .chatbot-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            transition: var(--transition);
        }

        .chatbot-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        .tab-container {
            display: flex;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 12px;
            padding: 5px;
            backdrop-filter: blur(10px);
        }

        .tab {
            padding: 12px 20px;
            cursor: pointer;
            border-radius: 8px;
            transition: var(--transition);
            font-weight: 500;
            flex: 1;
            text-align: center;
        }

        .tab.active {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(67, 97, 238, 0.3);
        }

        .tab-content {
            display: none;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .tab-content.active {
            display: block;
        }

        .form-row {
            display: flex;
            gap: 20px;
        }

        .form-row .form-group {
            flex: 1;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
        }

        .search-container input {
            flex: 1;
        }

        /* Notification */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            background: linear-gradient(135deg, var(--success) 0%, #1e7e34 100%);
            color: white;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
            z-index: 1002;
            display: none;
            align-items: center;
            gap: 10px;
            animation: bounceIn 0.5s ease;
        }

        @keyframes bounceIn {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }

            70% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .notification.error {
            background: linear-gradient(135deg, var(--danger) 0%, #a71e2a 100%);
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                overflow: visible;
            }

            .sidebar-header h2,
            .sidebar-header p,
            .menu-item span {
                display: none;
            }

            .menu-item {
                justify-content: center;
                padding: 15px;
            }

            .menu-item i {
                margin-right: 0;
                font-size: 1.4rem;
            }

            .main-content {
                margin-left: 80px;
            }

            .stats-container {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.active {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .search-container {
                flex-direction: column;
            }

            .action-buttons {
                flex-direction: column;
            }

            .chatbot-container {
                width: calc(100% - 40px);
                height: 70vh;
            }

            .top-nav {
                padding: 15px 20px;
            }

            .content {
                padding: 20px;
            }
        }

        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-toggle {
            display: none;
            background: linear-gradient(135deg, var(--primary-blue) 0%, #3a0ca3 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 1.2rem;
            margin-right: 15px;
        }

        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: flex;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>GSSSIETW</h2>
                <p>Management Dashboard</p>
            </div>
            <div class="sidebar-menu">
                <div class="menu-item active" data-target="dashboard">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </div>
                <div class="menu-item" data-target="students">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </div>
                <div class="menu-item" data-target="staff">
                    <i class="fas fa-chalkboard-teacher"></i>
                    <span>Staff</span>
                </div>
                <div class="menu-item" data-target="departments">
                    <i class="fas fa-building"></i>
                    <span>Departments</span>
                </div>
                <div class="menu-item" data-target="admin">
                    <i class="fas fa-user-shield"></i>
                    <span>Admin</span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="top-nav">
                <button class="mobile-menu-toggle" id="mobile-menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 id="page-title"> Admin Dashboard</h1>
                <div class="user-info" id="user-info">
                    <img src="https://ui-avatars.com/api/?name=Admin+User&background=4361ee&color=fff" alt="User">
                    <span>Admin User</span>
                    <i class="fas fa-chevron-down"></i>
                    <div class="user-dropdown" id="user-dropdown">
                        <div class="user-dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </div>
                        
                        <div href="logout.php" class="user-dropdown-item" id="logout-btn">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content">
                <!-- Dashboard Tab -->
                <div id="dashboard" class="tab-content active">
                    <div class="stats-container">
                        <div class="stat-card">
                            <i class="fas fa-user-graduate"></i>
                            <div class="bg-icon"><i class="fas fa-user-graduate"></i></div>
                            <h3 id="total-students">0</h3>
                            <p>Total Students</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <div class="bg-icon"><i class="fas fa-chalkboard-teacher"></i></div>
                            <h3 id="total-staff">0</h3>
                            <p>Total Staff</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-building"></i>
                            <div class="bg-icon"><i class="fas fa-building"></i></div>
                            <h3 id="total-departments">0</h3>
                            <p>Departments</p>
                        </div>
                        <div class="stat-card">
                            <i class="fas fa-book"></i>
                            <div class="bg-icon"><i class="fas fa-book"></i></div>
                            <h3>64</h3>
                            <p>Courses</p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Activities</h3>
                            <button class="btn-primary">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-container">
                                <table id="activities-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Activity</th>
                                            <th>User</th>
                                            <th>Details</th>
                                        </tr>
                                    </thead>
                                    <tbody id="activities-body">
                                        <!-- Activities will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Students Tab -->
                <div id="students" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Student Management</h3>
                            <button class="btn-primary" id="add-student-btn">
                                <i class="fas fa-plus"></i> Add Student
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <input type="text" id="student-search" placeholder="Search students...">
                                <button class="btn-primary" id="search-student-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="table-container">
                                <table id="students-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Enrollment Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-body">
                                        <!-- Students will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Tab -->
                <div id="staff" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Staff Management</h3>
                            <button class="btn-primary" id="add-staff-btn">
                                <i class="fas fa-plus"></i> Add Staff
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <input type="text" id="staff-search" placeholder="Search staff...">
                                <button class="btn-primary" id="search-staff-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="table-container">
                                <table id="staff-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="staff-body">
                                        <!-- Staff will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Departments Tab -->
                <div id="departments" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Department Management</h3>
                            <button class="btn-primary" id="add-department-btn">
                                <i class="fas fa-plus"></i> Add Department
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <input type="text" id="department-search" placeholder="Search departments...">
                                <button class="btn-primary" id="search-department-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="table-container">
                                <table id="departments-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Head of Department</th>
                                            <th>Total Staff</th>
                                            <th>Total Students</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="departments-body">
                                        <!-- Departments will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin Tab -->
                <div id="admin" class="tab-content">
                    <div class="card">
                        <div class="card-header">
                            <h3>Admin Management</h3>
                            <button class="btn-primary" id="add-admin-btn">
                                <i class="fas fa-plus"></i> Add Admin
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="search-container">
                                <input type="text" id="admin-search" placeholder="Search admins...">
                                <button class="btn-primary" id="search-admin-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                            </div>
                            <div class="table-container">
                                <table id="admin-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="admin-body">
                                        <!-- Admins will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Modal -->
    <div class="modal-overlay" id="student-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="student-form-title">Add New Student</h3>
                <button class="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="student-form">
                    <input type="hidden" id="student-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student-name">Full Name</label>
                            <input type="text" id="student-name" required>
                        </div>
                        <div class="form-group">
                            <label for="student-email">Email</label>
                            <input type="email" id="student-email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student-department">Department</label>
                            <select id="student-department" required>
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Business Administration">Business Administration</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="student-enrollment">Enrollment Date</label>
                            <input type="date" id="student-enrollment" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="student-phone">Phone</label>
                            <input type="tel" id="student-phone">
                        </div>
                        <div class="form-group">
                            <label for="student-address">Address</label>
                            <input type="text" id="student-address">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-danger close-modal">Cancel</button>
                <button type="submit" form="student-form" class="btn-success">Save Student</button>
            </div>
        </div>
    </div>

    <!-- Staff Modal -->
    <div class="modal-overlay" id="staff-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="staff-form-title">Add New Staff</h3>
                <button class="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="staff-form">
                    <input type="hidden" id="staff-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staff-name">Full Name</label>
                            <input type="text" id="staff-name" required>
                        </div>
                        <div class="form-group">
                            <label for="staff-email">Email</label>
                            <input type="email" id="staff-email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staff-department">Department</label>
                            <select id="staff-department" required>
                                <option value="">Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Business Administration">Business Administration</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="staff-position">Position</label>
                            <input type="text" id="staff-position" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="staff-hire-date">Hire Date</label>
                            <input type="date" id="staff-hire-date" required>
                        </div>
                        <div class="form-group">
                            <label for="staff-phone">Phone</label>
                            <input type="tel" id="staff-phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="staff-address">Address</label>
                        <input type="text" id="staff-address">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-danger close-modal">Cancel</button>
                <button type="submit" form="staff-form" class="btn-success">Save Staff</button>
            </div>
        </div>
    </div>

    <!-- Department Modal -->
    <div class="modal-overlay" id="department-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="department-form-title">Add New Department</h3>
                <button class="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="department-form">
                    <input type="hidden" id="department-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department-name">Department Name</label>
                            <input type="text" id="department-name" required>
                        </div>
                        <div class="form-group">
                            <label for="department-head">Head of Department</label>
                            <select id="department-head" required>
                                <option value="">Select Head</option>
                                <!-- Staff options will be populated here -->
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="department-budget">Budget ($)</label>
                            <input type="number" id="department-budget" required>
                        </div>
                        <div class="form-group">
                            <label for="department-phone">Phone</label>
                            <input type="tel" id="department-phone">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="department-description">Description</label>
                        <textarea id="department-description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-danger close-modal">Cancel</button>
                <button type="submit" form="department-form" class="btn-success">Save Department</button>
            </div>
        </div>
    </div>

    <!-- Admin Modal -->
    <div class="modal-overlay" id="admin-modal">
        <div class="modal">
            <div class="modal-header">
                <h3 id="admin-form-title">Add New Admin</h3>
                <button class="close-modal"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <form id="admin-form">
                    <input type="hidden" id="admin-id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-name">Full Name</label>
                            <input type="text" id="admin-name" required>
                        </div>
                        <div class="form-group">
                            <label for="admin-email">Email</label>
                            <input type="email" id="admin-email" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-role">Role</label>
                            <select id="admin-role" required>
                                <option value="">Select Role</option>
                                <option value="Super Admin">Super Admin</option>
                                <option value="Admin">Admin</option>
                                <option value="Moderator">Moderator</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="admin-permissions">Permissions</label>
                            <select id="admin-permissions" multiple>
                                <option value="students">Manage Students</option>
                                <option value="staff">Manage Staff</option>
                                <option value="departments">Manage Departments</option>
                                <option value="admin">Manage Admins</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="admin-phone">Phone</label>
                            <input type="tel" id="admin-phone">
                        </div>
                        <div class="form-group">
                            <label for="admin-address">Address</label>
                            <input type="text" id="admin-address">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-danger close-modal">Cancel</button>
                <button type="submit" form="admin-form" class="btn-success">Save Admin</button>
            </div>
        </div>
    </div>

    <!-- Chatbot -->
    <div class="chatbot-toggle" id="chatbot-toggle">
        <i class="fas fa-robot"></i>
    </div>

    <div class="chatbot-container" id="chatbot-container">
        <div class="chatbot-header">
            <h3>VTU Assistant</h3>
            <button id="close-chatbot"><i class="fas fa-times"></i></button>
        </div>
        <div class="chatbot-body" id="chatbot-body">
            <div class="message bot-message">
                Hello! I'm the VTU Assistant. How can I help you today? You can ask me about students, staff,
                departments, or admins.
            </div>
        </div>
        <div class="chatbot-footer">
            <input type="text" id="chatbot-input" placeholder="Type your message...">
            <button class="btn-primary" id="send-message">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification">
        <i class="fas fa-check-circle"></i>
        <span>Operation completed successfully!</span>
    </div>

    <script>
        // Database simulation
        const database = {
            students: [
                { id: 'ST001', name: 'John Smith', email: 'john.smith@vtu.edu', department: 'Computer Science', enrollmentDate: '2022-09-01', phone: '555-1234', address: '123 Main St', status: 'Active' },
                { id: 'ST002', name: 'Emily Johnson', email: 'emily.johnson@vtu.edu', department: 'Electrical Engineering', enrollmentDate: '2021-09-01', phone: '555-5678', address: '456 Oak Ave', status: 'Active' },
                { id: 'ST003', name: 'Michael Brown', email: 'michael.brown@vtu.edu', department: 'Mechanical Engineering', enrollmentDate: '2023-01-15', phone: '555-9012', address: '789 Pine Rd', status: 'Inactive' }
            ],
            staff: [
                { id: 'SF001', name: 'Dr. Robert Wilson', email: 'robert.wilson@vtu.edu', department: 'Computer Science', position: 'Professor', hireDate: '2015-08-15', phone: '555-3456', address: '321 Elm St', status: 'Active' },
                { id: 'SF002', name: 'Dr. Sarah Davis', email: 'sarah.davis@vtu.edu', department: 'Electrical Engineering', position: 'Associate Professor', hireDate: '2018-03-20', phone: '555-7890', address: '654 Maple Ave', status: 'Active' },
                { id: 'SF003', name: 'Dr. James Miller', email: 'james.miller@vtu.edu', department: 'Mechanical Engineering', position: 'Assistant Professor', hireDate: '2020-01-10', phone: '555-2345', address: '987 Cedar Ln', status: 'On Leave' }
            ],
            departments: [
                { id: 'DP001', name: 'Computer Science', head: 'Dr. Robert Wilson', staffCount: 25, studentCount: 350, budget: 1500000, phone: '555-1111', description: 'Department of Computer Science and Engineering', status: 'Active' },
                { id: 'DP002', name: 'Electrical Engineering', head: 'Dr. Sarah Davis', staffCount: 18, studentCount: 280, budget: 1200000, phone: '555-2222', description: 'Department of Electrical and Electronics Engineering', status: 'Active' },
                { id: 'DP003', name: 'Mechanical Engineering', head: 'Dr. James Miller', staffCount: 22, studentCount: 320, budget: 1350000, phone: '555-3333', description: 'Department of Mechanical Engineering', status: 'Active' },
                { id: 'DP004', name: 'Business Administration', head: 'Dr. Lisa Anderson', staffCount: 15, studentCount: 240, budget: 900000, phone: '555-4444', description: 'Department of Business Administration and Management', status: 'Inactive' }
            ],
            admins: [
                { id: 'AD001', name: 'Admin User', email: 'admin@vtu.edu', role: 'Super Admin', permissions: ['students', 'staff', 'departments', 'admin'], lastLogin: '2023-10-15', status: 'Active' },
                { id: 'AD002', name: 'Moderator User', email: 'moderator@vtu.edu', role: 'Moderator', permissions: ['students', 'staff'], lastLogin: '2023-10-14', status: 'Active' }
            ],
            activities: [
                { date: '2023-10-15', activity: 'New Student Registration', user: 'John Smith', details: 'Computer Science Department' },
                { date: '2023-10-14', activity: 'Staff Update', user: 'Dr. Emily Johnson', details: 'Promoted to Department Head' },
                { date: '2023-10-13', activity: 'Course Added', user: 'Admin', details: 'Advanced Data Structures' },
                { date: '2023-10-12', activity: 'Department Created', user: 'Admin', details: 'Artificial Intelligence' }
            ]
        };

        // DOM Elements
        const menuItems = document.querySelectorAll('.menu-item');
        const tabContents = document.querySelectorAll('.tab-content');
        const pageTitle = document.getElementById('page-title');
        const sidebar = document.getElementById('sidebar');
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const userInfo = document.getElementById('user-info');
        const userDropdown = document.getElementById('user-dropdown');
        const logoutBtn = document.getElementById('logout-btn');

        // Modals
        const studentModal = document.getElementById('student-modal');
        const staffModal = document.getElementById('staff-modal');
        const departmentModal = document.getElementById('department-modal');
        const adminModal = document.getElementById('admin-modal');
        const closeModalButtons = document.querySelectorAll('.close-modal');

        // Students
        const addStudentBtn = document.getElementById('add-student-btn');
        const studentForm = document.getElementById('student-form');
        const studentsBody = document.getElementById('students-body');
        const studentSearch = document.getElementById('student-search');
        const searchStudentBtn = document.getElementById('search-student-btn');

        // Staff
        const addStaffBtn = document.getElementById('add-staff-btn');
        const staffForm = document.getElementById('staff-form');
        const staffBody = document.getElementById('staff-body');
        const staffSearch = document.getElementById('staff-search');
        const searchStaffBtn = document.getElementById('search-staff-btn');

        // Departments
        const addDepartmentBtn = document.getElementById('add-department-btn');
        const departmentForm = document.getElementById('department-form');
        const departmentsBody = document.getElementById('departments-body');
        const departmentSearch = document.getElementById('department-search');
        const searchDepartmentBtn = document.getElementById('search-department-btn');

        // Admin
        const addAdminBtn = document.getElementById('add-admin-btn');
        const adminForm = document.getElementById('admin-form');
        const adminBody = document.getElementById('admin-body');
        const adminSearch = document.getElementById('admin-search');
        const searchAdminBtn = document.getElementById('search-admin-btn');

        // Chatbot
        const chatbotToggle = document.getElementById('chatbot-toggle');
        const chatbotContainer = document.getElementById('chatbot-container');
        const closeChatbot = document.getElementById('close-chatbot');
        const chatbotBody = document.getElementById('chatbot-body');
        const chatbotInput = document.getElementById('chatbot-input');
        const sendMessage = document.getElementById('send-message');

        // Notification
        const notification = document.getElementById('notification');

        // Initialize the dashboard
        document.addEventListener('DOMContentLoaded', function () {
            updateStats();
            loadStudents();
            loadStaff();
            loadDepartments();
            loadAdmins();
            loadActivities();

            // Set up event listeners for navigation
            menuItems.forEach(item => {
                item.addEventListener('click', function () {
                    const target = this.getAttribute('data-target');

                    // Update active menu item
                    menuItems.forEach(i => i.classList.remove('active'));
                    this.classList.add('active');

                    // Show corresponding tab
                    tabContents.forEach(tab => tab.classList.remove('active'));
                    document.getElementById(target).classList.add('active');

                    // Update page title
                    pageTitle.textContent = this.querySelector('span').textContent;

                    // Close sidebar on mobile after selection
                    if (window.innerWidth <= 768) {
                        sidebar.classList.remove('active');
                    }
                });
            });

            // Mobile menu toggle
            mobileMenuToggle.addEventListener('click', function () {
                sidebar.classList.toggle('active');
            });

            // User dropdown toggle
            userInfo.addEventListener('click', function (e) {
                e.stopPropagation();
                userDropdown.classList.toggle('active');
            });

            // Close dropdown when clicking elsewhere
            document.addEventListener('click', function () {
                userDropdown.classList.remove('active');
            });

            // Logout functionality
            logoutBtn.addEventListener('click', function () {
                if (confirm('Are you sure you want to logout?')) {
                    showNotification('Logging out...', 'success');
                    setTimeout(() => {
                        alert('You have been logged out successfully!');
                        // In a real application, you would redirect to login page
                        // window.location.href = 'login.html';
                    }, 1000);
                }
            });

            // Modal close buttons
            closeModalButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const modal = this.closest('.modal-overlay');
                    modal.classList.remove('active');
                });
            });

            // Close modal when clicking outside
            document.querySelectorAll('.modal-overlay').forEach(modal => {
                modal.addEventListener('click', function (e) {
                    if (e.target === this) {
                        this.classList.remove('active');
                    }
                });
            });

            // Students event listeners
            addStudentBtn.addEventListener('click', () => showModal(studentModal, 'Add New Student'));
            studentForm.addEventListener('submit', saveStudent);
            searchStudentBtn.addEventListener('click', searchStudents);

            // Staff event listeners
            addStaffBtn.addEventListener('click', () => showModal(staffModal, 'Add New Staff'));
            staffForm.addEventListener('submit', saveStaff);
            searchStaffBtn.addEventListener('click', searchStaff);

            // Departments event listeners
            addDepartmentBtn.addEventListener('click', () => showModal(departmentModal, 'Add New Department'));
            departmentForm.addEventListener('submit', saveDepartment);
            searchDepartmentBtn.addEventListener('click', searchDepartments);

            // Admin event listeners
            addAdminBtn.addEventListener('click', () => showModal(adminModal, 'Add New Admin'));
            adminForm.addEventListener('submit', saveAdmin);
            searchAdminBtn.addEventListener('click', searchAdmins);

            // Chatbot event listeners
            chatbotToggle.addEventListener('click', toggleChatbot);
            closeChatbot.addEventListener('click', toggleChatbot);
            sendMessage.addEventListener('click', sendChatMessage);
            chatbotInput.addEventListener('keypress', function (e) {
                if (e.key === 'Enter') {
                    sendChatMessage();
                }
            });

            // Animate stats counting
            animateStats();
        });

        // Modal functions
        function showModal(modal, title) {
            const titleElement = modal.querySelector('h3');
            titleElement.textContent = title;
            modal.classList.add('active');
        }

        // Update dashboard statistics
        function updateStats() {
            document.getElementById('total-students').textContent = database.students.length;
            document.getElementById('total-staff').textContent = database.staff.length;
            document.getElementById('total-departments').textContent = database.departments.length;
        }

        // Animate stats counting
        function animateStats() {
            const statElements = [
                { element: document.getElementById('total-students'), target: database.students.length },
                { element: document.getElementById('total-staff'), target: database.staff.length },
                { element: document.getElementById('total-departments'), target: database.departments.length }
            ];

            statElements.forEach(stat => {
                let current = 0;
                const increment = stat.target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= stat.target) {
                        stat.element.textContent = stat.target;
                        clearInterval(timer);
                    } else {
                        stat.element.textContent = Math.floor(current);
                    }
                }, 30);
            });
        }

        // Load students into the table
        function loadStudents() {
            studentsBody.innerHTML = '';
            if (database.students.length === 0) {
                studentsBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-user-graduate"></i>
                            <p>No students found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            database.students.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${student.id}</td>
                    <td>${student.name}</td>
                    <td>${student.email}</td>
                    <td>${student.department}</td>
                    <td>${student.enrollmentDate}</td>
                    <td><span class="badge ${student.status === 'Active' ? 'badge-success' : 'badge-warning'}">${student.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-student" data-id="${student.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-student" data-id="${student.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                studentsBody.appendChild(row);
            });

            // Add event listeners for edit and delete buttons
            document.querySelectorAll('.edit-student').forEach(button => {
                button.addEventListener('click', function () {
                    editStudent(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-student').forEach(button => {
                button.addEventListener('click', function () {
                    deleteStudent(this.getAttribute('data-id'));
                });
            });
        }

        // Load staff into the table
        function loadStaff() {
            staffBody.innerHTML = '';
            if (database.staff.length === 0) {
                staffBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <p>No staff found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            database.staff.forEach(staff => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${staff.id}</td>
                    <td>${staff.name}</td>
                    <td>${staff.email}</td>
                    <td>${staff.department}</td>
                    <td>${staff.position}</td>
                    <td><span class="badge ${staff.status === 'Active' ? 'badge-success' : staff.status === 'On Leave' ? 'badge-warning' : 'badge-danger'}">${staff.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-staff" data-id="${staff.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-staff" data-id="${staff.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                staffBody.appendChild(row);
            });

            // Add event listeners for edit and delete buttons
            document.querySelectorAll('.edit-staff').forEach(button => {
                button.addEventListener('click', function () {
                    editStaff(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-staff').forEach(button => {
                button.addEventListener('click', function () {
                    deleteStaff(this.getAttribute('data-id'));
                });
            });
        }

        // Load departments into the table
        function loadDepartments() {
            departmentsBody.innerHTML = '';
            if (database.departments.length === 0) {
                departmentsBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-building"></i>
                            <p>No departments found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            database.departments.forEach(dept => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${dept.id}</td>
                    <td>${dept.name}</td>
                    <td>${dept.head}</td>
                    <td>${dept.staffCount}</td>
                    <td>${dept.studentCount}</td>
                    <td><span class="badge ${dept.status === 'Active' ? 'badge-success' : 'badge-warning'}">${dept.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-department" data-id="${dept.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-department" data-id="${dept.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                departmentsBody.appendChild(row);
            });

            // Add event listeners for edit and delete buttons
            document.querySelectorAll('.edit-department').forEach(button => {
                button.addEventListener('click', function () {
                    editDepartment(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-department').forEach(button => {
                button.addEventListener('click', function () {
                    deleteDepartment(this.getAttribute('data-id'));
                });
            });
        }

        // Load admins into the table
        function loadAdmins() {
            adminBody.innerHTML = '';
            if (database.admins.length === 0) {
                adminBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-user-shield"></i>
                            <p>No admins found</p>
                        </td>
                    </tr>
                `;
                return;
            }

            database.admins.forEach(admin => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${admin.id}</td>
                    <td>${admin.name}</td>
                    <td>${admin.email}</td>
                    <td>${admin.role}</td>
                    <td><span class="badge badge-success">${admin.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-admin" data-id="${admin.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-admin" data-id="${admin.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                adminBody.appendChild(row);
            });

            // Add event listeners for edit and delete buttons
            document.querySelectorAll('.edit-admin').forEach(button => {
                button.addEventListener('click', function () {
                    editAdmin(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-admin').forEach(button => {
                button.addEventListener('click', function () {
                    deleteAdmin(this.getAttribute('data-id'));
                });
            });
        }

        // Load activities into the table
        function loadActivities() {
            const activitiesBody = document.getElementById('activities-body');
            activitiesBody.innerHTML = '';
            database.activities.forEach(activity => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${activity.date}</td>
                    <td>${activity.activity}</td>
                    <td>${activity.user}</td>
                    <td>${activity.details}</td>
                `;
                activitiesBody.appendChild(row);
            });
        }

        // Student functions
        function saveStudent(e) {
            e.preventDefault();

            const id = document.getElementById('student-id').value;
            const name = document.getElementById('student-name').value;
            const email = document.getElementById('student-email').value;
            const department = document.getElementById('student-department').value;
            const enrollmentDate = document.getElementById('student-enrollment').value;
            const phone = document.getElementById('student-phone').value;
            const address = document.getElementById('student-address').value;

            if (id) {
                // Update existing student
                const index = database.students.findIndex(s => s.id === id);
                if (index !== -1) {
                    database.students[index] = { ...database.students[index], name, email, department, enrollmentDate, phone, address };
                    showNotification('Student updated successfully!', 'success');
                }
            } else {
                // Add new student
                const newId = 'ST' + String(database.students.length + 1).padStart(3, '0');
                database.students.push({ id: newId, name, email, department, enrollmentDate, phone, address, status: 'Active' });
                showNotification('Student added successfully!', 'success');

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'New Student Registration',
                    user: name,
                    details: department
                });
                loadActivities();
            }

            loadStudents();
            updateStats();
            studentModal.classList.remove('active');
            animateStats();
        }

        function editStudent(id) {
            const student = database.students.find(s => s.id === id);
            if (student) {
                document.getElementById('student-id').value = student.id;
                document.getElementById('student-name').value = student.name;
                document.getElementById('student-email').value = student.email;
                document.getElementById('student-department').value = student.department;
                document.getElementById('student-enrollment').value = student.enrollmentDate;
                document.getElementById('student-phone').value = student.phone;
                document.getElementById('student-address').value = student.address;
                showModal(studentModal, 'Edit Student');
            }
        }

        function deleteStudent(id) {
            if (confirm('Are you sure you want to delete this student?')) {
                database.students = database.students.filter(s => s.id !== id);
                loadStudents();
                updateStats();
                showNotification('Student deleted successfully!', 'success');
                animateStats();

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'Student Deleted',
                    user: 'Admin',
                    details: `Student ID: ${id}`
                });
                loadActivities();
            }
        }

        function searchStudents() {
            const query = studentSearch.value.toLowerCase();
            const filteredStudents = database.students.filter(student =>
                student.name.toLowerCase().includes(query) ||
                student.email.toLowerCase().includes(query) ||
                student.department.toLowerCase().includes(query) ||
                student.id.toLowerCase().includes(query)
            );

            studentsBody.innerHTML = '';
            if (filteredStudents.length === 0) {
                studentsBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No students match your search</p>
                        </td>
                    </tr>
                `;
                return;
            }

            filteredStudents.forEach(student => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${student.id}</td>
                    <td>${student.name}</td>
                    <td>${student.email}</td>
                    <td>${student.department}</td>
                    <td>${student.enrollmentDate}</td>
                    <td><span class="badge ${student.status === 'Active' ? 'badge-success' : 'badge-warning'}">${student.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-student" data-id="${student.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-student" data-id="${student.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                studentsBody.appendChild(row);
            });

            // Reattach event listeners
            document.querySelectorAll('.edit-student').forEach(button => {
                button.addEventListener('click', function () {
                    editStudent(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-student').forEach(button => {
                button.addEventListener('click', function () {
                    deleteStudent(this.getAttribute('data-id'));
                });
            });
        }

        // Staff functions
        function saveStaff(e) {
            e.preventDefault();

            const id = document.getElementById('staff-id').value;
            const name = document.getElementById('staff-name').value;
            const email = document.getElementById('staff-email').value;
            const department = document.getElementById('staff-department').value;
            const position = document.getElementById('staff-position').value;
            const hireDate = document.getElementById('staff-hire-date').value;
            const phone = document.getElementById('staff-phone').value;
            const address = document.getElementById('staff-address').value;

            if (id) {
                // Update existing staff
                const index = database.staff.findIndex(s => s.id === id);
                if (index !== -1) {
                    database.staff[index] = { ...database.staff[index], name, email, department, position, hireDate, phone, address };
                    showNotification('Staff updated successfully!', 'success');
                }
            } else {
                // Add new staff
                const newId = 'SF' + String(database.staff.length + 1).padStart(3, '0');
                database.staff.push({ id: newId, name, email, department, position, hireDate, phone, address, status: 'Active' });
                showNotification('Staff added successfully!', 'success');

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'New Staff Added',
                    user: name,
                    details: `${position} in ${department}`
                });
                loadActivities();
            }

            loadStaff();
            updateStats();
            staffModal.classList.remove('active');
            animateStats();
        }

        function editStaff(id) {
            const staff = database.staff.find(s => s.id === id);
            if (staff) {
                document.getElementById('staff-id').value = staff.id;
                document.getElementById('staff-name').value = staff.name;
                document.getElementById('staff-email').value = staff.email;
                document.getElementById('staff-department').value = staff.department;
                document.getElementById('staff-position').value = staff.position;
                document.getElementById('staff-hire-date').value = staff.hireDate;
                document.getElementById('staff-phone').value = staff.phone;
                document.getElementById('staff-address').value = staff.address;
                showModal(staffModal, 'Edit Staff');
            }
        }

        function deleteStaff(id) {
            if (confirm('Are you sure you want to delete this staff member?')) {
                database.staff = database.staff.filter(s => s.id !== id);
                loadStaff();
                updateStats();
                showNotification('Staff deleted successfully!', 'success');
                animateStats();

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'Staff Deleted',
                    user: 'Admin',
                    details: `Staff ID: ${id}`
                });
                loadActivities();
            }
        }

        function searchStaff() {
            const query = staffSearch.value.toLowerCase();
            const filteredStaff = database.staff.filter(staff =>
                staff.name.toLowerCase().includes(query) ||
                staff.email.toLowerCase().includes(query) ||
                staff.department.toLowerCase().includes(query) ||
                staff.position.toLowerCase().includes(query) ||
                staff.id.toLowerCase().includes(query)
            );

            staffBody.innerHTML = '';
            if (filteredStaff.length === 0) {
                staffBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No staff match your search</p>
                        </td>
                    </tr>
                `;
                return;
            }

            filteredStaff.forEach(staff => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${staff.id}</td>
                    <td>${staff.name}</td>
                    <td>${staff.email}</td>
                    <td>${staff.department}</td>
                    <td>${staff.position}</td>
                    <td><span class="badge ${staff.status === 'Active' ? 'badge-success' : staff.status === 'On Leave' ? 'badge-warning' : 'badge-danger'}">${staff.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-staff" data-id="${staff.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-staff" data-id="${staff.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                staffBody.appendChild(row);
            });

            // Reattach event listeners
            document.querySelectorAll('.edit-staff').forEach(button => {
                button.addEventListener('click', function () {
                    editStaff(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-staff').forEach(button => {
                button.addEventListener('click', function () {
                    deleteStaff(this.getAttribute('data-id'));
                });
            });
        }

        // Department functions
        function saveDepartment(e) {
            e.preventDefault();

            const id = document.getElementById('department-id').value;
            const name = document.getElementById('department-name').value;
            const head = document.getElementById('department-head').value;
            const budget = document.getElementById('department-budget').value;
            const phone = document.getElementById('department-phone').value;
            const description = document.getElementById('department-description').value;

            if (id) {
                // Update existing department
                const index = database.departments.findIndex(d => d.id === id);
                if (index !== -1) {
                    database.departments[index] = {
                        ...database.departments[index],
                        name, head, budget: parseInt(budget), phone, description
                    };
                    showNotification('Department updated successfully!', 'success');
                }
            } else {
                // Add new department
                const newId = 'DP' + String(database.departments.length + 1).padStart(3, '0');
                database.departments.push({
                    id: newId,
                    name,
                    head,
                    staffCount: 0,
                    studentCount: 0,
                    budget: parseInt(budget),
                    phone,
                    description,
                    status: 'Active'
                });
                showNotification('Department added successfully!', 'success');

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'New Department Created',
                    user: 'Admin',
                    details: name
                });
                loadActivities();
            }

            loadDepartments();
            updateStats();
            departmentModal.classList.remove('active');
            animateStats();
        }

        function editDepartment(id) {
            const dept = database.departments.find(d => d.id === id);
            if (dept) {
                document.getElementById('department-id').value = dept.id;
                document.getElementById('department-name').value = dept.name;
                document.getElementById('department-budget').value = dept.budget;
                document.getElementById('department-phone').value = dept.phone;
                document.getElementById('department-description').value = dept.description;

                // Populate head of department dropdown with staff
                const headSelect = document.getElementById('department-head');
                headSelect.innerHTML = '<option value="">Select Head</option>';
                database.staff.forEach(staff => {
                    const option = document.createElement('option');
                    option.value = staff.name;
                    option.textContent = staff.name;
                    option.selected = staff.name === dept.head;
                    headSelect.appendChild(option);
                });

                showModal(departmentModal, 'Edit Department');
            }
        }

        function deleteDepartment(id) {
            if (confirm('Are you sure you want to delete this department?')) {
                database.departments = database.departments.filter(d => d.id !== id);
                loadDepartments();
                updateStats();
                showNotification('Department deleted successfully!', 'success');
                animateStats();

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'Department Deleted',
                    user: 'Admin',
                    details: `Department ID: ${id}`
                });
                loadActivities();
            }
        }

        function searchDepartments() {
            const query = departmentSearch.value.toLowerCase();
            const filteredDepts = database.departments.filter(dept =>
                dept.name.toLowerCase().includes(query) ||
                dept.head.toLowerCase().includes(query) ||
                dept.id.toLowerCase().includes(query)
            );

            departmentsBody.innerHTML = '';
            if (filteredDepts.length === 0) {
                departmentsBody.innerHTML = `
                    <tr>
                        <td colspan="7" class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No departments match your search</p>
                        </td>
                    </tr>
                `;
                return;
            }

            filteredDepts.forEach(dept => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${dept.id}</td>
                    <td>${dept.name}</td>
                    <td>${dept.head}</td>
                    <td>${dept.staffCount}</td>
                    <td>${dept.studentCount}</td>
                    <td><span class="badge ${dept.status === 'Active' ? 'badge-success' : 'badge-warning'}">${dept.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-department" data-id="${dept.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-department" data-id="${dept.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                departmentsBody.appendChild(row);
            });

            // Reattach event listeners
            document.querySelectorAll('.edit-department').forEach(button => {
                button.addEventListener('click', function () {
                    editDepartment(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-department').forEach(button => {
                button.addEventListener('click', function () {
                    deleteDepartment(this.getAttribute('data-id'));
                });
            });
        }

        // Admin functions
        function saveAdmin(e) {
            e.preventDefault();

            const id = document.getElementById('admin-id').value;
            const name = document.getElementById('admin-name').value;
            const email = document.getElementById('admin-email').value;
            const role = document.getElementById('admin-role').value;
            const permissions = Array.from(document.getElementById('admin-permissions').selectedOptions).map(option => option.value);
            const phone = document.getElementById('admin-phone').value;
            const address = document.getElementById('admin-address').value;

            if (id) {
                // Update existing admin
                const index = database.admins.findIndex(a => a.id === id);
                if (index !== -1) {
                    database.admins[index] = {
                        ...database.admins[index],
                        name, email, role, permissions, phone, address
                    };
                    showNotification('Admin updated successfully!', 'success');
                }
            } else {
                // Add new admin
                const newId = 'AD' + String(database.admins.length + 1).padStart(3, '0');
                database.admins.push({
                    id: newId,
                    name,
                    email,
                    role,
                    permissions,
                    lastLogin: new Date().toISOString().split('T')[0],
                    phone,
                    address,
                    status: 'Active'
                });
                showNotification('Admin added successfully!', 'success');

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'New Admin Added',
                    user: 'Admin',
                    details: `${name} as ${role}`
                });
                loadActivities();
            }

            loadAdmins();
            adminModal.classList.remove('active');
        }

        function editAdmin(id) {
            const admin = database.admins.find(a => a.id === id);
            if (admin) {
                document.getElementById('admin-id').value = admin.id;
                document.getElementById('admin-name').value = admin.name;
                document.getElementById('admin-email').value = admin.email;
                document.getElementById('admin-role').value = admin.role;
                document.getElementById('admin-phone').value = admin.phone;
                document.getElementById('admin-address').value = admin.address;

                // Set permissions
                const permissionsSelect = document.getElementById('admin-permissions');
                Array.from(permissionsSelect.options).forEach(option => {
                    option.selected = admin.permissions.includes(option.value);
                });

                showModal(adminModal, 'Edit Admin');
            }
        }

        function deleteAdmin(id) {
            if (confirm('Are you sure you want to delete this admin?')) {
                database.admins = database.admins.filter(a => a.id !== id);
                loadAdmins();
                showNotification('Admin deleted successfully!', 'success');

                // Add activity
                database.activities.unshift({
                    date: new Date().toISOString().split('T')[0],
                    activity: 'Admin Deleted',
                    user: 'Admin',
                    details: `Admin ID: ${id}`
                });
                loadActivities();
            }
        }

        function searchAdmins() {
            const query = adminSearch.value.toLowerCase();
            const filteredAdmins = database.admins.filter(admin =>
                admin.name.toLowerCase().includes(query) ||
                admin.email.toLowerCase().includes(query) ||
                admin.role.toLowerCase().includes(query) ||
                admin.id.toLowerCase().includes(query)
            );

            adminBody.innerHTML = '';
            if (filteredAdmins.length === 0) {
                adminBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-state">
                            <i class="fas fa-search"></i>
                            <p>No admins match your search</p>
                        </td>
                    </tr>
                `;
                return;
            }

            filteredAdmins.forEach(admin => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${admin.id}</td>
                    <td>${admin.name}</td>
                    <td>${admin.email}</td>
                    <td>${admin.role}</td>
                    <td><span class="badge badge-success">${admin.status}</span></td>
                    <td class="action-buttons">
                        <button class="btn-warning edit-admin" data-id="${admin.id}">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button class="btn-danger delete-admin" data-id="${admin.id}">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </td>
                `;
                adminBody.appendChild(row);
            });

            // Reattach event listeners
            document.querySelectorAll('.edit-admin').forEach(button => {
                button.addEventListener('click', function () {
                    editAdmin(this.getAttribute('data-id'));
                });
            });

            document.querySelectorAll('.delete-admin').forEach(button => {
                button.addEventListener('click', function () {
                    deleteAdmin(this.getAttribute('data-id'));
                });
            });
        }

        // Chatbot functions
        function toggleChatbot() {
            chatbotContainer.classList.toggle('active');
            chatbotToggle.style.display = chatbotContainer.classList.contains('active') ? 'none' : 'flex';
        }

        function sendChatMessage() {
            const message = chatbotInput.value.trim();
            if (message === '') return;

            // Add user message
            addMessage(message, 'user');
            chatbotInput.value = '';

            // Process and generate response
            setTimeout(() => {
                const response = generateResponse(message);
                addMessage(response, 'bot');
            }, 500);
        }

        function addMessage(text, sender) {
            const messageDiv = document.createElement('div');
            messageDiv.classList.add('message', `${sender}-message`);
            messageDiv.textContent = text;
            chatbotBody.appendChild(messageDiv);
            chatbotBody.scrollTop = chatbotBody.scrollHeight;
        }

        function generateResponse(message) {
            const lowerMessage = message.toLowerCase();

            // Student queries
            if (lowerMessage.includes('student') || lowerMessage.includes('students')) {
                if (lowerMessage.includes('all') || lowerMessage.includes('list') || lowerMessage.includes('show')) {
                    let response = `There are ${database.students.length} students:\n`;
                    database.students.forEach(student => {
                        response += `- ${student.name} (${student.id}), ${student.department}\n`;
                    });
                    return response;
                } else if (lowerMessage.includes('count') || lowerMessage.includes('how many')) {
                    return `There are ${database.students.length} students in the system.`;
                } else if (lowerMessage.includes('add') || lowerMessage.includes('create')) {
                    return "To add a new student, go to the Students tab and click the 'Add Student' button.";
                }
            }

            // Staff queries
            else if (lowerMessage.includes('staff') || lowerMessage.includes('teacher') || lowerMessage.includes('faculty')) {
                if (lowerMessage.includes('all') || lowerMessage.includes('list') || lowerMessage.includes('show')) {
                    let response = `There are ${database.staff.length} staff members:\n`;
                    database.staff.forEach(staff => {
                        response += `- ${staff.name} (${staff.id}), ${staff.position} in ${staff.department}\n`;
                    });
                    return response;
                } else if (lowerMessage.includes('count') || lowerMessage.includes('how many')) {
                    return `There are ${database.staff.length} staff members in the system.`;
                } else if (lowerMessage.includes('add') || lowerMessage.includes('create')) {
                    return "To add a new staff member, go to the Staff tab and click the 'Add Staff' button.";
                }
            }

            // Department queries
            else if (lowerMessage.includes('department') || lowerMessage.includes('departments')) {
                if (lowerMessage.includes('all') || lowerMessage.includes('list') || lowerMessage.includes('show')) {
                    let response = `There are ${database.departments.length} departments:\n`;
                    database.departments.forEach(dept => {
                        response += `- ${dept.name} (Head: ${dept.head}), ${dept.studentCount} students, $${dept.budget.toLocaleString()} budget\n`;
                    });
                    return response;
                } else if (lowerMessage.includes('count') || lowerMessage.includes('how many')) {
                    return `There are ${database.departments.length} departments in the system.`;
                } else if (lowerMessage.includes('add') || lowerMessage.includes('create')) {
                    return "To add a new department, go to the Departments tab and click the 'Add Department' button.";
                }
            }

            // Admin queries
            else if (lowerMessage.includes('admin') || lowerMessage.includes('admins')) {
                if (lowerMessage.includes('all') || lowerMessage.includes('list') || lowerMessage.includes('show')) {
                    let response = `There are ${database.admins.length} admins:\n`;
                    database.admins.forEach(admin => {
                        response += `- ${admin.name} (${admin.id}), ${admin.role}\n`;
                    });
                    return response;
                } else if (lowerMessage.includes('count') || lowerMessage.includes('how many')) {
                    return `There are ${database.admins.length} admins in the system.`;
                }
            }

            // General help
            else if (lowerMessage.includes('help') || lowerMessage.includes('what can you do')) {
                return "I can help you with information about students, staff, departments, and admins. Try asking me things like 'How many students are there?' or 'Show all staff members'.";
            }

            // Default response
            return "I'm not sure I understand. You can ask me about students, staff, departments, or admins. For example, try 'How many students are there?' or 'Show all staff members'.";
        }

        // Utility functions
        function showNotification(message, type) {
            const notificationIcon = notification.querySelector('i');
            const notificationText = notification.querySelector('span');

            notificationText.textContent = message;

            if (type === 'success') {
                notification.className = 'notification';
                notificationIcon.className = 'fas fa-check-circle';
            } else {
                notification.className = 'notification error';
                notificationIcon.className = 'fas fa-exclamation-circle';
            }

            notification.style.display = 'flex';

            setTimeout(() => {
                notification.style.display = 'none';
            }, 3000);
        }
    </script>
</body>

</html>