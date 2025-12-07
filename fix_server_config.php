<?php
// Helper script to fix server database configuration
// This script will update your .env file to use server database settings

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die("Error: .env file not found at: $envFile\n");
}

// Read current .env file
$lines = file($envFile, FILE_IGNORE_NEW_LINES);

$updated = false;
$newLines = [];

foreach ($lines as $line) {
    $originalLine = $line;
    $line = trim($line);
    
    // Skip empty lines and comments (we'll add them back)
    if (empty($line) || strpos($line, '#') === 0) {
        $newLines[] = $originalLine;
        continue;
    }
    
    // Update APP_ENV to force server mode
    if (preg_match('/^APP_ENV\s*=/i', $line)) {
        $newLines[] = 'APP_ENV=server';
        $updated = true;
        continue;
    }
    
    // Keep all other lines as-is
    $newLines[] = $originalLine;
}

// If APP_ENV wasn't found, add it
if (!$updated) {
    // Find where to insert it (after the last config line)
    $inserted = false;
    $finalLines = [];
    foreach ($newLines as $line) {
        $finalLines[] = $line;
        // Insert after server config section
        if (!$inserted && preg_match('/^DB_PASS_SERVER=/i', $line)) {
            $finalLines[] = '';
            $finalLines[] = '# Force server mode';
            $finalLines[] = 'APP_ENV=server';
            $inserted = true;
        }
    }
    if (!$inserted) {
        // Add at the end if we couldn't find a good spot
        $finalLines[] = '';
        $finalLines[] = '# Force server mode';
        $finalLines[] = 'APP_ENV=server';
    }
    $newLines = $finalLines;
    $updated = true;
}

// Backup original file
$backupFile = $envFile . '.backup.' . date('Y-m-d_H-i-s');
copy($envFile, $backupFile);

// Write updated file
file_put_contents($envFile, implode("\n", $newLines) . "\n");

echo "✅ Configuration updated successfully!\n";
echo "📁 Backup created at: $backupFile\n";
echo "🔧 APP_ENV set to 'server' to force server database connection\n";
echo "\n";
echo "Next steps:\n";
echo "1. Refresh your browser and try accessing your dashboard\n";
echo "2. Visit db_diagnostic.php to verify the connection\n";
echo "3. If you still don't see data, check that your data is in the 'webtech_2025A_joycelyn_allan' database\n";
?>
