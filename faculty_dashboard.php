<?php
// faculty_dashboard.php - Faculty dashboard page

require_once 'auth_check.php';

// Check if user is faculty
if ($_SESSION['role'] !== 'faculty') {
    header('Location: index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$first_name = $_SESSION['first_name'];
$last_name = $_SESSION['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard - Attendance Management</title>
    <link rel="stylesheet" href="requests/css/style.css">
    <link rel="stylesheet" href="requests/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <nav class="navbar">
        <div class="nav-brand">
            <h2>📚 Attendance Management - Faculty</h2>
        </div>
        <div class="nav-menu">
            <span>Welcome, <?php echo htmlspecialchars($first_name); ?>!</span>
            <button class="btn btn-secondary logout-btn">Logout</button>
        </div>
    </nav>
    
    <div class="container">
        <header class="dashboard-header">
            <h1>Faculty Dashboard</h1>
            <nav class="section-nav">
                <button class="nav-link active" data-section="courses">My Courses</button>
                <button class="nav-link" data-section="enrollment">Enrollment Requests</button>
                <button class="nav-link" data-section="sessions">Sessions</button>
                <button class="nav-link" data-section="reports">Reports</button>
            </nav>
        </header>

        <!-- My Courses Section -->
        <section id="courses-section" class="dashboard-section active">
            <div class="card">
                <header class="card-header">
                    <h3>My Courses</h3>
                    <button class="btn btn-primary" id="createCourseBtn">Create New Course</button>
                </header>
                <div id="myCourses">
                    <p class="loading">Loading courses...</p>
                </div>
            </div>
        </section>

        <!-- Enrollment Requests Section -->
        <section id="enrollment-section" class="dashboard-section">
            <div class="card">
                <header class="card-header">
                    <h3>Enrollment Requests</h3>
                </header>
                <div id="enrollmentRequests">
                    <p class="loading">Loading requests...</p>
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
            <div class="dashboard-grid">
                <div class="card">
                    <h3>Attendance Reports</h3>
                    <div>
                        <p>Generate comprehensive attendance reports for your courses.</p>
                        <ul style="text-align: left; margin: 15px 0;">
                            <li>Course-wise attendance summary</li>
                            <li>Student attendance percentage</li>
                            <li>Export to PDF or Excel</li>
                        </ul>
                        <button class="btn btn-primary" onclick="generateAttendanceReport()">Generate Report</button>
                    </div>
                </div>
                
                <div class="card">
                    <h3>Performance Reports</h3>
                    <div>
                        <p>Create detailed performance analysis reports.</p>
                        <ul style="text-align: left; margin: 15px 0;">
                            <li>Student performance trends</li>
                            <li>Class participation metrics</li>
                            <li>Comparative analysis</li>
                        </ul>
                        <button class="btn btn-primary" onclick="generatePerformanceReport()">Generate Report</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
    
    <!-- Create Course Modal -->
    <div id="createCourseModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Create New Course</h2>
            <form id="createCourseForm">
                <div class="form-group">
                    <label for="course_code">Course Code</label>
                    <input type="text" id="course_code" name="course_code" required>
                </div>
                
                <div class="form-group">
                    <label for="course_name">Course Name</label>
                    <input type="text" id="course_name" name="course_name" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="credit_hours">Credit Hours</label>
                    <input type="number" id="credit_hours" name="credit_hours" min="1" max="10">
                </div>
                
                <button type="submit" class="btn btn-primary">Create Course</button>
            </form>
        </div>
    </div>
    
    <script src="requests/js/logout.js"></script>
    <script src="requests/js/faculty_dashboard.js"></script>
    <script src="requests/js/session_management.js"></script>
    <script>
    // Report generation functions
    function generateAttendanceReport() {
        Swal.fire({
            title: 'Generate Attendance Report',
            html: `
                <div style="text-align: left;">
                    <p>Select report options:</p>
                    <select class="swal2-input" id="report-course">
                        <option>All Courses</option>
                    </select>
                    <select class="swal2-input" id="report-period">
                        <option>Last 7 days</option>
                        <option>Last 30 days</option>
                        <option>This semester</option>
                    </select>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Generate',
            confirmButtonColor: '#722f37'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Report Generated!',
                    text: 'Your attendance report is ready.',
                    confirmButtonColor: '#722f37'
                });
            }
        });
    }

    function generatePerformanceReport() {
        Swal.fire({
            title: 'Generate Performance Report',
            html: `
                <div style="text-align: left;">
                    <p>Select report type:</p>
                    <select class="swal2-input" id="performance-type">
                        <option>Overall Performance</option>
                        <option>Attendance vs. Grades</option>
                        <option>Student Comparison</option>
                    </select>
                </div>
            `,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'Generate',
            confirmButtonColor: '#722f37'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    icon: 'success',
                    title: 'Report Generated!',
                    text: 'Your performance report is ready.',
                    confirmButtonColor: '#722f37'
                });
            }
        });
    }
    </script>
</body>
</html>