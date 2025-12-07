<?php
// Check which database is being used for read/write operations
// This helps diagnose if reads and writes are using different databases

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db_connect.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            padding: 20px;
            margin: 15px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection Check</h1>
    
    <?php
    // Get current database
    try {
        $stmt = $conn->query("SELECT DATABASE() as dbname");
        $result = $stmt->fetch();
        $currentDb = $result['dbname'];
        
        // Get course count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM courses");
        $courseCount = $stmt->fetch()['count'];
        
        // Get recent courses
        $stmt = $conn->query("SELECT course_id, course_code, course_name, faculty_id FROM courses ORDER BY course_id DESC LIMIT 5");
        $recentCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get user count
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $userCount = $stmt->fetch()['count'];
        
        // Check if this is the server database
        $isServerDb = ($currentDb === 'webtech_2025A_joycelyn_allan');
        $isLocalDb = ($currentDb === 'attendancemanagement');
        
        ?>
        
        <div class="card">
            <h2>Current Database Connection</h2>
            <table>
                <tr><th>Property</th><th>Value</th></tr>
                <tr>
                    <td>Connected Database</td>
                    <td>
                        <code><?php echo htmlspecialchars($currentDb); ?></code>
                        <?php if ($isServerDb): ?>
                            <span style="color: green;">✅ Server Database</span>
                        <?php elseif ($isLocalDb): ?>
                            <span style="color: orange;">⚠️ Local Database</span>
                        <?php else: ?>
                            <span style="color: red;">❓ Unknown Database</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr><td>Courses Count</td><td><strong><?php echo $courseCount; ?></strong></td></tr>
                <tr><td>Users Count</td><td><strong><?php echo $userCount; ?></strong></td></tr>
            </table>
        </div>
        
        <div class="card">
            <h2>Recent Courses (Last 5)</h2>
            <?php if (count($recentCourses) > 0): ?>
                <table>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Code</th>
                        <th>Course Name</th>
                        <th>Faculty ID</th>
                    </tr>
                    <?php foreach ($recentCourses as $course): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($course['course_id']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                        <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($course['faculty_id']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <div class="warning">No courses found in this database.</div>
            <?php endif; ?>
        </div>
        
        <?php if (!$isServerDb): ?>
            <div class="card">
                <div class="error">
                    <strong>❌ Problem Detected!</strong><br>
                    You're connected to <code><?php echo htmlspecialchars($currentDb); ?></code> but should be connected to <code>webtech_2025A_joycelyn_allan</code>.<br><br>
                    <strong>This means:</strong>
                    <ul>
                        <li>When you create a course, it might be saved to a different database</li>
                        <li>When you read courses, it's reading from this database (which might be empty)</li>
                        <li>This causes the "course created but not showing" issue</li>
                    </ul>
                    <br>
                    <strong>Solution:</strong>
                    <ol>
                        <li>Check your .env file on the server</li>
                        <li>Make sure <code>APP_ENV=server</code> (not development)</li>
                        <li>Make sure all <code>DB_*_SERVER</code> variables are set</li>
                        <li>Visit <a href="server_debug.php">server_debug.php</a> to verify detection</li>
                        <li>Or run <a href="fix_server_config.php">fix_server_config.php</a> to auto-fix</li>
                    </ol>
                </div>
            </div>
        <?php else: ?>
            <div class="card">
                <div class="success">
                    <strong>✅ Connected to Correct Database!</strong><br>
                    You're connected to the server database <code>webtech_2025A_joycelyn_allan</code>.<br>
                    If courses still don't show, check:
                    <ul>
                        <li>Browser console for JavaScript errors</li>
                        <li>Network tab to see if API calls are successful</li>
                        <li>Check if the course was created with the correct faculty_id</li>
                    </ul>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Test Read Operation</h2>
            <p>Testing if get_courses.php would see the same data...</p>
            <?php
            if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                $user_id = $_SESSION['user_id'];
                $role = $_SESSION['role'];
                
                echo "<p><strong>Logged in as:</strong> User ID $user_id, Role: $role</p>";
                
                if ($role === 'faculty') {
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM courses WHERE faculty_id = ?");
                    $stmt->execute([$user_id]);
                    $myCourses = $stmt->fetch()['count'];
                    
                    echo "<p><strong>Your courses in this database:</strong> $myCourses</p>";
                    
                    if ($myCourses > 0) {
                        $stmt = $conn->prepare("SELECT course_id, course_code, course_name FROM courses WHERE faculty_id = ? ORDER BY course_id DESC LIMIT 5");
                        $stmt->execute([$user_id]);
                        $myCourseList = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo "<ul>";
                        foreach ($myCourseList as $course) {
                            echo "<li>" . htmlspecialchars($course['course_code']) . " - " . htmlspecialchars($course['course_name']) . "</li>";
                        }
                        echo "</ul>";
                    }
                }
            } else {
                echo "<p>Not logged in. <a href='login.html'>Login</a> to see your courses.</p>";
            }
            ?>
        </div>
        
        <div class="card">
            <p>
                <a href="index.php">← Back to Home</a> | 
                <a href="server_debug.php">Server Debug</a> | 
                <a href="db_diagnostic.php">Database Diagnostic</a>
            </p>
        </div>
        
    <?php
    } catch(PDOException $e) {
        echo '<div class="error">';
        echo '<strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
    }
    ?>
</body>
</html>

