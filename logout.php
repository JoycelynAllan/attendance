<?php
// logout.php - User logout handler

session_start();
header('Content-Type: application/json');

// Destroy all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

echo json_encode([
    'logout' => true,
    'message' => 'Logged out successfully'
]);
?>