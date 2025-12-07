<?php
// fix_enrollment_id.php - Fix missing AUTO_INCREMENT on server
session_start();
header('Content-Type: text/html; charset=utf-8');
require_once 'db_connect.php';

echo "<h1>Database Repair Tool</h1>";

try {
    // 1. Check if table exists
    $check = $conn->query("SHOW TABLES LIKE 'enrollment'");
    if ($check->rowCount() == 0) {
        die("<h2 style='color:red'>Error: Table 'enrollment' not found! (Did you rename it?)</h2>");
    }

    // 2. Attempt to add AUTO_INCREMENT
    echo "<p>Attempting to add AUTO_INCREMENT to enrollment_id...</p>";
    
    // We need to modify the column definition. 
    // Assuming it's the primary key (which it should be).
    $sql = "ALTER TABLE enrollment MODIFY COLUMN enrollment_id INT(11) NOT NULL AUTO_INCREMENT";
    
    $conn->exec($sql);
    
    echo "<h2 style='color:green'>Success! Fixed 'enrollment_id' auto-increment.</h2>";
    echo "<p>You should now be able to join courses.</p>";
    echo "<p><a href='student_dashboard.php'>Go back to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Repair Failed</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
