<?php
// Server Debug Script - Check what's happening with server detection
// Access this file on your server to see what's detected

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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Server Debug Information</title>
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
        .fix-btn { 
            display: inline-block; 
            background: #007bff; 
            color: white; 
            padding: 10px 20px; 
            text-decoration: none; 
            border-radius: 5px; 
            margin: 10px 5px 10px 0;
        }
        .fix-btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>🔍 Server Debug Information</h1>
    
    <?php
    // Server Detection Test
    $isServer = false;
    $detectionMethod = 'none';
    $detectionDetails = [];
    
    // Check APP_ENV
    $appEnv = getenv('APP_ENV');
    $detectionDetails[] = ['Method' => 'APP_ENV', 'Value' => $appEnv ?: 'Not set', 'Result' => ($appEnv === 'production' || $appEnv === 'server') ? '✅ Server detected' : '❌ Not server'];
    if ($appEnv === 'production' || $appEnv === 'server') {
        $isServer = true;
        $detectionMethod = 'APP_ENV';
    }
    
    // Check HTTP_HOST
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'Not set';
    $httpHostClean = preg_replace('/:\d+$/', '', $httpHost);
    $httpHostMatch = (strpos($httpHostClean, '169.239.251.102') !== false || 
                     strpos($httpHostClean, 'joycelyn.allan') !== false ||
                     (strpos($httpHostClean, 'localhost') === false && 
                      strpos($httpHostClean, '127.0.0.1') === false &&
                      $httpHostClean !== '::1'));
    $detectionDetails[] = [
        'Method' => 'HTTP_HOST', 
        'Value' => $httpHost . ' (cleaned: ' . $httpHostClean . ')', 
        'Result' => $httpHostMatch ? '✅ Server detected' : '❌ Not server'
    ];
    if (!$isServer && $httpHostMatch) {
        $isServer = true;
        $detectionMethod = 'HTTP_HOST';
    }
    
    // Check SERVER_NAME
    $serverName = $_SERVER['SERVER_NAME'] ?? 'Not set';
    $serverNameMatch = ($serverName !== 'localhost' && 
                       $serverName !== '127.0.0.1' && 
                       $serverName !== '::1');
    $detectionDetails[] = [
        'Method' => 'SERVER_NAME', 
        'Value' => $serverName, 
        'Result' => $serverNameMatch ? '✅ Server detected' : '❌ Not server'
    ];
    if (!$isServer && $serverNameMatch) {
        $isServer = true;
        $detectionMethod = 'SERVER_NAME';
    }
    
    // Check server config
    $hasServerConfig = false;
    $dbHostServer = getenv('DB_HOST_SERVER');
    if ($dbHostServer !== false && $dbHostServer !== '') {
        $hasServerConfig = true;
    }
    
    // Determine which config will be used
    $willUseServerConfig = ($isServer && $hasServerConfig);
    ?>
    
    <div class="card">
        <h2>Server Detection Status</h2>
        <table>
            <tr><th>Detection Method</th><th>Value</th><th>Result</th></tr>
            <?php foreach ($detectionDetails as $detail): ?>
            <tr>
                <td><code><?php echo htmlspecialchars($detail['Method']); ?></code></td>
                <td><code><?php echo htmlspecialchars($detail['Value']); ?></code></td>
                <td><?php echo $detail['Result']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <div style="margin-top: 15px;">
            <strong>Final Detection:</strong> 
            <?php if ($isServer): ?>
                <span style="color: green; font-weight: bold;">✅ DETECTED AS SERVER</span> (via <?php echo $detectionMethod; ?>)
            <?php else: ?>
                <span style="color: red; font-weight: bold;">❌ NOT DETECTED AS SERVER</span>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="card">
        <h2>Database Configuration</h2>
        <table>
            <tr><th>Setting</th><th>Value</th></tr>
            <tr><td>Has Server Config?</td><td><?php echo $hasServerConfig ? '✅ Yes' : '❌ No'; ?></td></tr>
            <tr><td>Will Use Server Config?</td><td><?php echo $willUseServerConfig ? '✅ Yes' : '❌ No'; ?></td></tr>
            <tr><td>APP_ENV</td><td><code><?php echo htmlspecialchars($appEnv ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_HOST_SERVER</td><td><code><?php echo htmlspecialchars($dbHostServer ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_NAME_SERVER</td><td><code><?php echo htmlspecialchars(getenv('DB_NAME_SERVER') ?: 'Not set'); ?></code></td></tr>
            <tr><td>DB_USER_SERVER</td><td><code><?php echo htmlspecialchars(getenv('DB_USER_SERVER') ?: 'Not set'); ?></code></td></tr>
        </table>
    </div>
    
    <div class="card">
        <h2>What Database Will Be Used?</h2>
        <?php
        if ($willUseServerConfig) {
            $host = getenv('DB_HOST_SERVER') ?: 'localhost';
            $port = getenv('DB_PORT_SERVER') ?: '3306';
            $dbname = getenv('DB_NAME_SERVER') ?: 'webtech_2025A_joycelyn_allan';
            $username = getenv('DB_USER_SERVER') ?: 'root';
            
            echo '<div class="success">';
            echo '<strong>✅ Will use SERVER database:</strong><br>';
            echo "Host: <code>$host</code><br>";
            echo "Port: <code>$port</code><br>";
            echo "Database: <code>$dbname</code><br>";
            echo "User: <code>$username</code><br>";
            echo '</div>';
        } else {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '3306';
            $dbname = getenv('DB_NAME') ?: 'attendancemanagement';
            $username = getenv('DB_USER') ?: 'root';
            
            echo '<div class="warning">';
            echo '<strong>⚠️ Will use LOCALHOST database:</strong><br>';
            echo "Host: <code>$host</code><br>";
            echo "Port: <code>$port</code><br>";
            echo "Database: <code>$dbname</code><br>";
            echo "User: <code>$username</code><br>";
            echo '</div>';
            
            if ($isServer && !$hasServerConfig) {
                echo '<div class="error" style="margin-top: 10px;">';
                echo '<strong>❌ Problem Found!</strong><br>';
                echo 'Server is detected, but DB_HOST_SERVER is not configured in .env file.<br>';
                echo 'This means it will try to connect to the localhost database on the server, which is likely empty.';
                echo '</div>';
            } elseif (!$isServer) {
                echo '<div class="error" style="margin-top: 10px;">';
                echo '<strong>❌ Problem Found!</strong><br>';
                echo 'Server is NOT being detected. Check your .env file and set: <code>APP_ENV=server</code>';
                echo '</div>';
            }
        }
        ?>
    </div>
    
    <div class="card">
        <h2>Quick Fixes</h2>
        <?php if (!$isServer || !$hasServerConfig): ?>
            <div class="info">
                <strong>To fix this issue:</strong>
                <ol>
                    <li>Make sure your .env file on the server has <code>APP_ENV=server</code></li>
                    <li>Make sure your .env file has all the server database credentials:
                        <ul>
                            <li><code>DB_HOST_SERVER=localhost</code></li>
                            <li><code>DB_PORT_SERVER=3306</code></li>
                            <li><code>DB_NAME_SERVER=webtech_2025A_joycelyn_allan</code></li>
                            <li><code>DB_USER_SERVER=joycelyn.allan</code></li>
                            <li><code>DB_PASS_SERVER=Jalla@123</code></li>
                        </ul>
                    </li>
                    <li>Or use the automatic fix script: <a href="fix_server_config.php" class="fix-btn">Run Fix Script</a></li>
                </ol>
            </div>
        <?php else: ?>
            <div class="success">
                <strong>✅ Configuration looks correct!</strong><br>
                If you're still not seeing data, check:
                <ul>
                    <li>Does the database <code><?php echo htmlspecialchars(getenv('DB_NAME_SERVER') ?: 'webtech_2025A_joycelyn_allan'); ?></code> exist?</li>
                    <li>Does it contain the data (users, courses, sessions, etc.)?</li>
                    <li>Check <a href="db_diagnostic.php">db_diagnostic.php</a> for connection details</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <p><a href="index.php">← Back to Home</a> | <a href="db_diagnostic.php">Database Diagnostic</a></p>
    </div>
</body>
</html>

