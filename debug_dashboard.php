<?php
// debug_dashboard.php - Diagnostic script for dashboard issues
session_start();
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Debugger</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .box { background: white; padding: 15px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        h2 { margin-top: 0; color: #333; border-bottom: 2px solid #eee; padding-bottom: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f9f9f9; }
        pre { background: #eee; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Dashboard Diagnostic Tool</h1>
    <p>Run this tool to diagnose why courses/data are not showing up on the dashboard.</p>

    <div class="box">
        <h2>1. Session Information</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
            echo "<div class='success'>Session Active</div>";
            echo "User ID: " . htmlspecialchars($_SESSION['user_id']) . "<br>";
            echo "Role: " . htmlspecialchars($_SESSION['role'] ?? 'Not Set') . "<br>";
            echo "Name: " . htmlspecialchars($_SESSION['first_name'] ?? '') . " " . htmlspecialchars($_SESSION['last_name'] ?? '') . "<br>";
        } else {
            echo "<div class='error'>No Active Session</div>";
            echo "Please log in first, then return to this page.";
        }
        ?>
    </div>

    <div class="box">
        <h2>2. Database Connection</h2>
        <?php
        $conn = null;
        try {
            require_once 'db_connect.php';
            if ($conn) {
                echo "<div class='success'>Database Connected Successfully</div>";
                
                // Get current database name
                $stmt = $conn->query("SELECT DATABASE()");
                $dbName = $stmt->fetchColumn();
                echo "Current Database: <strong>" . htmlspecialchars($dbName) . "</strong><br>";
                
                // Get connection info (host) via attribute if possible
                try {
                    $hostInfo = $conn->getAttribute(PDO::ATTR_CONNECTION_STATUS);
                    echo "Connection Status: " . htmlspecialchars($hostInfo) . "<br>";
                } catch (Exception $e) {
                    echo "Connection Status: (Could not retrieve)<br>";
                }
            } else {
                echo "<div class='error'>Connection object is null despite no exception!</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error'>Connection Failed: " . htmlspecialchars($e->getMessage()) . "</div>";
            echo "<h3>Environment Variables (.env check):</h3>";
            echo "<pre>";
            // Safely print env vars
            $vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PORT', 'DB_HOST_SERVER', 'DB_NAME_SERVER', 'DB_USER_SERVER'];
            foreach ($vars as $var) {
                $val = getenv($var);
                echo "$var: " . ($val === false ? "(not set)" : htmlspecialchars($val)) . "\n";
            }
            echo "</pre>";
        }
        ?>
    </div>

    <?php if ($conn): ?>
    <div class="box">
        <h2>3. Data Counts (Database: <?php echo htmlspecialchars($dbName); ?>)</h2>
        <table>
            <tr><th>Table</th><th>Count</th><th>Last ID</th></tr>
            <?php
            $tables = ['courses', 'users', 'Enrollment', 'sessions'];
            foreach ($tables as $table) {
                try {
                    // Check if table exists first
                    $check = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($check->rowCount() == 0) {
                        echo "<tr><td>$table</td><td colspan='2' class='error'>TABLE MISSING</td></tr>";
                        continue;
                    }

                    $stmt = $conn->query("SELECT COUNT(*) as c FROM $table");
                    $count = $stmt->fetchColumn();
                    
                    // Get last inserted ID if possible (assuming standard id columns)
                    $idCol = 'id';
                    if ($table == 'courses') $idCol = 'course_id';
                    if ($table == 'users') $idCol = 'user_id';
                    if ($table == 'Enrollment') $idCol = 'enrollment_id';
                    if ($table == 'sessions') $idCol = 'session_id';
                    
                    try {
                        $last = $conn->query("SELECT $idCol FROM $table ORDER BY $idCol DESC LIMIT 1")->fetchColumn();
                        echo "<tr><td>$table</td><td>$count</td><td>$last</td></tr>";
                    } catch (Exception $e) {
                         echo "<tr><td>$table</td><td>$count</td><td>(no id col?)</td></tr>";
                    }
                } catch (Exception $e) {
                    echo "<tr><td>$table</td><td colspan='2' class='error'>Error: " . $e->getMessage() . "</td></tr>";
                }
            }
            ?>
        </table>
    </div>

    <div class="box">
        <h2>4. User's Data Check</h2>
        <?php
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $role = $_SESSION['role'] ?? 'unknown';
            
            echo "<h3>Logic for Role: $role</h3>";
            
            if ($role === 'faculty') {
                $sql = "SELECT course_id, course_code, course_name FROM courses WHERE faculty_id = ?";
                echo "<p>Running Query: <code>$sql</code> with ID: $user_id</p>";
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$user_id]);
                    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($courses) > 0) {
                        echo "<div class='success'>Found " . count($courses) . " courses for this faculty.</div>";
                        echo "<table><tr><th>ID</th><th>Code</th><th>Name</th></tr>";
                        foreach ($courses as $c) {
                            echo "<tr><td>{$c['course_id']}</td><td>{$c['course_code']}</td><td>{$c['course_name']}</td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "<div class='warning'>No courses found for this faculty ID ($user_id).</div>";
                        // Check if any courses exist at all
                        $any = $conn->query("SELECT COUNT(*) FROM courses")->fetchColumn();
                        if ($any > 0) {
                             echo "<p>However, there are $any courses in the table. Maybe `faculty_id` mismatch?</p>";
                             echo "<p>Showing first 5 courses and their faculty_ids:</p>";
                             $debug = $conn->query("SELECT course_id, course_code, faculty_id FROM courses LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
                             echo "<pre>";
                             print_r($debug);
                             echo "</pre>";
                        }
                    }
                } catch (Exception $e) {
                    echo "<div class='error'>Query Error: " . $e->getMessage() . "</div>";
                }
            } elseif ($role === 'student') {
                // Check Enrollment
                $sql = "SELECT e.*, c.course_code FROM Enrollment e JOIN courses c ON e.course_id = c.course_id WHERE e.student_id = ?";
                echo "<p>Running Enrollment Query: <code>$sql</code> with ID: $user_id</p>";
                try {
                    $stmt = $conn->prepare($sql);
                    $stmt->execute([$user_id]);
                    $enrollments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($enrollments) > 0) {
                         echo "<div class='success'>Found " . count($enrollments) . " enrollments.</div>";
                         echo "<pre>" . print_r($enrollments, true) . "</pre>";
                    } else {
                        echo "<div class='warning'>No enrollments found.</div>";
                    }
                } catch (Exception $e) {
                     echo "<div class='error'>Query Error: " . $e->getMessage() . "</div>";
                }
            } else {
                echo "Unknown role logic.";
            }
        }
        ?>
    </div>
    
    <div class="box">
        <h2>5. Recent Logs (Last 5 lines of error log if readable)</h2>
        <pre>
        <?php
        $logFile = ini_get('error_log');
        if ($logFile && file_exists($logFile) && is_readable($logFile)) {
             $lines = file($logFile);
             $last5 = array_slice($lines, -5);
             foreach ($last5 as $l) echo htmlspecialchars($l);
        } elseif ($logFile) {
            echo "Log file ($logFile) not readable or empty.";
        } else {
             echo "Error logging not configured to file.";
        }
        ?>
        </pre>
    </div>
    <?php endif; ?>

</body>
</html>
