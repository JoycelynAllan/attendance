<?php
// create_course.php - Course creation handler

session_start();
header('Content-Type: application/json');

// Check if user is logged in and is faculty
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

require_once 'db_connect.php';

// Log which database we're connected to
try {
    $stmt = $conn->query("SELECT DATABASE() as dbname");
    $dbInfo = $stmt->fetch();
    $currentDb = $dbInfo['dbname'];
    error_log("create_course.php - Connected to database: $currentDb");
} catch(PDOException $e) {
    error_log("create_course.php - Could not get database name: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Validate input
$required_fields = ['course_code', 'course_name'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

$course_code = trim($data['course_code']);
$course_name = trim($data['course_name']);
$description = isset($data['description']) ? trim($data['description']) : null;
$credit_hours = isset($data['credit_hours']) ? (int)$data['credit_hours'] : null;
$faculty_id = $_SESSION['user_id'];

try {
    // Check if course code already exists
    $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_code = ?");
    $stmt->execute([$course_code]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Course code already exists']);
        exit;
    }
    
    // Insert course
    $stmt = $conn->prepare("
        INSERT INTO courses (course_code, course_name, description, credit_hours, faculty_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $course_code,
        $course_name,
        $description,
        $credit_hours,
        $faculty_id
    ]);
    
    $course_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Course created successfully',
        'course_id' => $course_id
    ]);
    
} catch(PDOException $e) {
    error_log("Create course error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create course. Please try again.'
    ]);
}
?>