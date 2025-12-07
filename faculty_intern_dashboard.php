<?php
// faculty_intern_dashboard.php - Faculty Intern dashboard
require_once 'auth_check.php';

if ($_SESSION['role'] !== 'faculty_intern') {
    header('Location: index.php');
    exit;
}

$intern_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Intern Dashboard - Attendance Management</title>
    <link rel="stylesheet" href="requests/css/style.css">
    <link rel="stylesheet" href="requests/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h2>📚 Attendance Management - Faculty Intern</h2>
        </div>
        <div class="nav-menu">
            <span>Welcome, <?php echo htmlspecialchars($first_name); ?>!</span>
            <button class="btn btn-secondary logout-btn">Logout</button>
        </div>
    </nav>
    
    <div class="container">
        <header class="dashboard-header">
            <h1>Faculty Intern Dashboard</h1>
            <nav class="section-nav">
                <button class="nav-link active" data-section="courses">Course List</button>
                <button class="nav-link" data-section="sessions">Sessions</button>
                <button class="nav-link" data-section="reports">Reports</button>
            </nav>
        </header>
        
        <!-- Course List Section -->
        <section id="courses-section" class="dashboard-section active">
            <div class="card">
                <header class="card-header">
                    <h3>Available Courses</h3>
                </header>
                <div id="courseList">
                    <p class="loading">Loading courses...</p>
                </div>
            </div>
        </section>
        
        <!-- Sessions Section -->
        <section id="sessions-section" class="dashboard-section">
            <div class="card">
                <header class="card-header">
                    <h3>Session Management</h3>
                    <select id="sessionCourseSelect" class="btn btn-sm btn-secondary" style="margin-left: 10px;">
                        <option value="">Select a course...</option>
                    </select>
                </header>
                <div id="sessionList">
                    <p>Select a course to view and manage sessions.</p>
                </div>
            </div>
        </section>
        
        <!-- Reports Section -->
        <section id="reports-section" class="dashboard-section">
            <div class="card">
                <header class="card-header">
                    <h3>My Reports</h3>
                </header>
                <div id="reportList">
                    <p>Reports feature coming soon. You will be able to:</p>
                    <ul>
                        <li>Create attendance reports</li>
                        <li>Create performance reports</li>
                        <li>View report history</li>
                    </ul>
                </div>
            </div>
        </section>
    </div>
    
    <script src="requests/js/logout.js"></script>
    <script src="requests/js/faculty_intern_dashboard.js"></script>
    <script src="requests/js/session_management.js"></script>
</body>
</html>