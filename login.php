<?php
// login.php - Login handler with role-based redirect

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);
error_log("=== Login attempt started ===");

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['username']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Username and password are required']);
    exit;
}

$username = trim($data['username']);
$password = $data['password'];

if (empty($username) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Username and password cannot be empty']);
    exit;
}

try {
    // Find user by username or email
    $stmt = $conn->prepare("
        SELECT user_id, username, email, password, role, first_name, last_name 
        FROM users 
        WHERE username = ? OR email = ?
    ");
    $stmt->execute([$username, $username]);
    
    if ($stmt->rowCount() === 0) {
        error_log("User not found: $username");
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }
    
    $user = $stmt->fetch();
    error_log("User found: " . $user['user_id'] . " - Role: " . $user['role']);
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        error_log("Invalid password for user: $username");
        echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
        exit;
    }
    
    // Create session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['last_activity'] = time();
    
    // Determine dashboard URL based on role
    $dashboard_url = '';
    switch($user['role']) {
        case 'faculty':
            $dashboard_url = 'faculty_dashboard.php';
            break;
        case 'faculty_intern':
            $dashboard_url = 'faculty_intern_dashboard.php';
            break;
        case 'student':
            $dashboard_url = 'student_dashboard.php';
            break;
        default:
            $dashboard_url = 'index.php';
    }
    
    error_log("Login successful - Redirecting to: $dashboard_url");
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user_id' => $user['user_id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'dashboard_url' => $dashboard_url
    ]);
    
} catch(PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Login failed. Please try again.',
        'error' => $e->getMessage()
    ]);
}
?>