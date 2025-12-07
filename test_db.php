<?php
// Simple database connection test
// Upload this to your server and access it via browser

require_once 'db_connect.php';

echo "<h1>Database Connection Test</h1>";
echo "<style>body{font-family:Arial;max-width:800px;margin:20px auto;padding:20px;}";
echo "table{border-collapse:collapse;width:100%;margin:10px 0;}";
echo "th,td{padding:8px;text-align:left;border:1px solid #ddd;}";
echo "th{background:#f2f2f2;}";
echo ".success{color:green;background:#d4edda;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".error{color:red;background:#f8d7da;padding:10px;border-radius:5px;margin:10px 0;}";
echo ".info{color:blue;background:#cce5ff;padding:10px;border-radius:5px;margin:10px 0;}";
echo "</style>";

// Get current database name
try {
    $stmt = $conn->query("SELECT DATABASE() as dbname");
    $result = $stmt->fetch();
    $currentDb = $result['dbname'];
    
    echo "<div class='success'>";
    echo "<strong>✅ Connected to database:</strong> " . htmlspecialchars($currentDb);
    echo "</div>";
    
    // Check for data
    $tables = ['users', 'courses', 'sessions', 'Enrollment', 'attendance'];
    echo "<h2>Data Check</h2>";
    echo "<table>";
    echo "<tr><th>Table</th><th>Record Count</th></tr>";
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SELECT COUNT(*) as count FROM `$table`");
            $result = $stmt->fetch();
            $count = $result['count'];
            $color = $count > 0 ? 'green' : 'orange';
            echo "<tr><td>$table</td><td style='color:$color;font-weight:bold;'>$count</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>$table</td><td style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>";
        }
    }
    echo "</table>";
    
    // Show server detection info
    echo "<h2>Server Detection Info</h2>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>HTTP_HOST</td><td>" . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Not set') . "</td></tr>";
    echo "<tr><td>SERVER_NAME</td><td>" . htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Not set') . "</td></tr>";
    echo "<tr><td>APP_ENV</td><td>" . htmlspecialchars(getenv('APP_ENV') ?: 'Not set') . "</td></tr>";
    echo "</table>";
    
    if ($currentDb === 'webtech_2025A_joycelyn_allan') {
        echo "<div class='success'>";
        echo "<strong>✅ Correct Database!</strong> You're connected to the server database.";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<strong>⚠️ Wrong Database!</strong> You're connected to '$currentDb' but should be connected to 'webtech_2025A_joycelyn_allan'.";
        echo "<br><br><strong>Solution:</strong> Update your .env file on the server and set: <code>APP_ENV=server</code>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}
?>
