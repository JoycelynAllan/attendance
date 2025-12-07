<?php
// Database Diagnostic Script
// This script helps identify database connection issues

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Load environment variables
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }
    
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
    return true;
}

loadEnv(__DIR__ . '/.env');

// Detect server
$isServer = false;
$detectionMethod = 'none';

if (isset($_SERVER['HTTP_HOST'])) {
    $httpHost = $_SERVER['HTTP_HOST'];
    if (strpos($httpHost, '169.239.251.102') !== false || 
        strpos($httpHost, 'joycelyn.allan') !== false ||
        (strpos($httpHost, 'localhost') === false && strpos($httpHost, '127.0.0.1') === false)) {
        $isServer = true;
        $detectionMethod = 'HTTP_HOST';
    }
}

if (!$isServer && isset($_SERVER['SERVER_NAME'])) {
    $serverName = $_SERVER['SERVER_NAME'];
    if ($serverName !== 'localhost' && $serverName !== '127.0.0.1') {
        $isServer = true;
        $detectionMethod = 'SERVER_NAME';
    }
}

$appEnv = getenv('APP_ENV');
if ($appEnv === 'production' || $appEnv === 'server') {
    $isServer = true;
    $detectionMethod = 'APP_ENV';
}

// Get database config
if ($isServer && getenv('DB_HOST_SERVER') !== false) {
    $host = getenv('DB_HOST_SERVER') ?: 'localhost';
    $port = getenv('DB_PORT_SERVER') ?: '3306';
    $dbname = getenv('DB_NAME_SERVER') ?: 'webtech_2025A_joycelyn_allan';
    $username = getenv('DB_USER_SERVER') ?: 'root';
    $password = getenv('DB_PASS_SERVER') ?: '';
    $configSource = 'SERVER';
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $port = getenv('DB_PORT') ?: '3306';
    $dbname = getenv('DB_NAME') ?: 'attendancemanagement';
    $username = getenv('DB_USER') ?: 'root';
    $password = getenv('DB_PASS');
    $configSource = 'LOCALHOST';
}

if ($password === false || $password === '') {
    $password = '';
}

// Try to connect
$connectionStatus = 'Unknown';
$connectionError = '';
$tableCount = 0;
$coursesCount = 0;
$sessionsCount = 0;
$usersCount = 0;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT => 10
    ]);
    
    $connectionStatus = 'SUCCESS';
    
    // Get table count
    $stmt = $conn->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $tableCount = count($tables);
    
    // Get data counts
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM courses");
        $result = $stmt->fetch();
        $coursesCount = $result['count'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM sessions");
        $result = $stmt->fetch();
        $sessionsCount = $result['count'] ?? 0;
    } catch (Exception $e) {}
    
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        $usersCount = $result['count'] ?? 0;
    } catch (Exception $e) {}
    
} catch(PDOException $e) {
    $connectionStatus = 'FAILED';
    $connectionError = $e->getMessage();
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 900px;
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
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 5px; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 5px; }
        .info { color: #004085; background: #cce5ff; padding: 10px; border-radius: 5px; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 5px; }
        h2 { margin-top: 0; color: #333; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection Diagnostic</h1>
    
    <div class="card">
        <h2>Server Detection</h2>
        <table>
            <tr><th>Property</th><th>Value</th></tr>
            <tr><td>HTTP_HOST</td><td><code><?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Not set'); ?></code></td></tr>
            <tr><td>SERVER_NAME</td><td><code><?php echo htmlspecialchars($_SERVER['SERVER_NAME'] ?? 'Not set'); ?></code></td></tr>
            <tr><td>APP_ENV</td><td><code><?php echo htmlspecialchars($appEnv ?: 'Not set'); ?></code></td></tr>
            <tr><td>Detected as Server?</td><td><strong><?php echo $isServer ? 'YES' : 'NO'; ?></strong></td></tr>
            <tr><td>Detection Method</td><td><code><?php echo $detectionMethod; ?></code></td></tr>
        </table>
    </div>
    
    <div class="card">
        <h2>Database Configuration</h2>
        <table>
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Configuration Source</td><td><strong><?php echo $configSource; ?></strong></td></tr>
            <tr><td>Host</td><td><code><?php echo htmlspecialchars($host); ?></code></td></tr>
            <tr><td>Port</td><td><code><?php echo htmlspecialchars($port); ?></code></td></tr>
            <tr><td>Database Name</td><td><code><?php echo htmlspecialchars($dbname); ?></code></td></tr>
            <tr><td>Username</td><td><code><?php echo htmlspecialchars($username); ?></code></td></tr>
            <tr><td>Password</td><td><code><?php echo $password ? '***' : '(empty)'; ?></code></td></tr>
        </table>
    </div>
    
    <div class="card">
        <h2>Connection Status</h2>
        <?php if ($connectionStatus === 'SUCCESS'): ?>
            <div class="success">
                <strong>✅ Connection Successful!</strong>
            </div>
            <table>
                <tr><th>Metric</th><th>Value</th></tr>
                <tr><td>Tables Found</td><td><strong><?php echo $tableCount; ?></strong></td></tr>
                <tr><td>Users Count</td><td><strong><?php echo $usersCount; ?></strong></td></tr>
                <tr><td>Courses Count</td><td><strong><?php echo $coursesCount; ?></strong></td></tr>
                <tr><td>Sessions Count</td><td><strong><?php echo $sessionsCount; ?></strong></td></tr>
            </table>
            
            <?php if ($coursesCount === 0 && $sessionsCount === 0): ?>
                <div class="warning" style="margin-top: 15px;">
                    <strong>⚠️ Warning:</strong> Database is connected but contains no courses or sessions. 
                    This might mean you're connected to the wrong database, or the data hasn't been imported yet.
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="error">
                <strong>❌ Connection Failed!</strong><br>
                Error: <?php echo htmlspecialchars($connectionError); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Environment Variables</h2>
        <table>
            <tr><th>Variable</th><th>Value</th></tr>
            <tr><td>DB_HOST</td><td><code><?php echo htmlspecialchars(getenv('DB_HOST') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_HOST_SERVER</td><td><code><?php echo htmlspecialchars(getenv('DB_HOST_SERVER') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_NAME</td><td><code><?php echo htmlspecialchars(getenv('DB_NAME') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_NAME_SERVER</td><td><code><?php echo htmlspecialchars(getenv('DB_NAME_SERVER') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_USER</td><td><code><?php echo htmlspecialchars(getenv('DB_USER') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_USER_SERVER</td><td><code><?php echo htmlspecialchars(getenv('DB_USER_SERVER') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_PASS_SERVER</td><td><code><?php echo getenv('DB_PASS_SERVER') ? '***' : 'Not set'; ?></code></td></tr>
        </table>
    </div>
    
    <div class="card">
        <h2>Recommendations</h2>
        <?php if ($connectionStatus === 'FAILED'): ?>
            <div class="error">
                <strong>Fix the connection error first:</strong>
                <ul>
                    <li>Check your .env file has correct credentials</li>
                    <li>Verify database server is running</li>
                    <li>Check firewall/network settings</li>
                </ul>
            </div>
        <?php elseif ($isServer && $configSource === 'LOCALHOST'): ?>
            <div class="warning">
                <strong>⚠️ Server detected but using localhost config!</strong>
                <ul>
                    <li>Your server is detected, but DB_HOST_SERVER is not set in .env</li>
                    <li>Add server database credentials to .env file</li>
                    <li>Or set APP_ENV=server in .env to force server mode</li>
                </ul>
            </div>
        <?php elseif (!$isServer && $configSource === 'SERVER'): ?>
            <div class="warning">
                <strong>⚠️ Localhost detected but using server config!</strong>
                <ul>
                    <li>You're on localhost but server config is being used</li>
                    <li>This might be intentional, but verify it's correct</li>
                </ul>
            </div>
        <?php elseif ($coursesCount === 0): ?>
            <div class="info">
                <strong>ℹ️ Database connected but empty:</strong>
                <ul>
                    <li>If you expect to see data, verify you're connected to the correct database</li>
                    <li>Check if data exists in the database using phpMyAdmin</li>
                    <li>If data is in a different database, update DB_NAME_SERVER or DB_NAME in .env</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ Everything looks good!</strong>
                <ul>
                    <li>Connection is working</li>
                    <li>Data is present in the database</li>
                    <li>If you still don't see data in the app, check browser console for JavaScript errors</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <p><a href="index.php">← Back to Home</a></p>
    </div>
</body>
</html>
