<?php
// signup.php - User registration for Faculty, Faculty Intern, and Student

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);
error_log("=== Signup attempt started ===");

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON decode error: " . json_last_error_msg());
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
$required_fields = ['first_name', 'last_name', 'email', 'username', 'password', 'role'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

// Sanitize inputs
$first_name = trim($data['first_name']);
$last_name = trim($data['last_name']);
$email = trim($data['email']);
$username = trim($data['username']);
$password = $data['password'];
$role = trim($data['role']);
$dob = isset($data['dob']) && !empty($data['dob']) ? $data['dob'] : null;

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// Validate role (only 3 roles allowed)
if (!in_array($role, ['student', 'faculty', 'faculty_intern'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role. Must be student, faculty, or faculty_intern']);
    exit;
}

// Validate password
if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit;
}

try {
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        error_log("Email already exists: $email");
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        error_log("Username already exists: $username");
        echo json_encode(['success' => false, 'message' => 'Username already taken']);
        exit;
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    error_log("Password hashed successfully");
    
    // Insert user into database
    $stmt = $conn->prepare("
        INSERT INTO users (first_name, last_name, email, password, role, dob, username) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $hashed_password,
        $role,
        $dob,
        $username
    ]);
    
    $user_id = $conn->lastInsertId();
    error_log("User created with ID: $user_id");
    
    // Insert into role-specific tables
    if ($role === 'student') {
        $stmt = $conn->prepare("INSERT INTO students (student_id) VALUES (?)");
        $stmt->execute([$user_id]);
        error_log("Student record created");
    } elseif ($role === 'faculty') {
        $stmt = $conn->prepare("INSERT INTO faculty (faculty_id) VALUES (?)");
        $stmt->execute([$user_id]);
        error_log("Faculty record created");
    } elseif ($role === 'faculty_intern') {
        $stmt = $conn->prepare("INSERT INTO faculty_interns (intern_id) VALUES (?)");
        $stmt->execute([$user_id]);
        error_log("Faculty Intern record created");
    }
    
    error_log("=== Signup successful ===");
    
    echo json_encode([
        'success' => true,
        'message' => 'Registration successful! Redirecting to login...',
        'user_id' => $user_id
    ]);
    
} catch(PDOException $e) {
    error_log("Signup error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed. Please try again.',
        'error' => $e->getMessage()
    ]);
}
?>