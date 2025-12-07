<?php
// join_course_intern.php - Faculty Intern auto-join and assign to course

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty_intern') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['course_id'])) {
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

$course_id = (int)$data['course_id'];
$intern_id = $_SESSION['user_id'];

try {
    // Check if course exists
    $stmt = $conn->prepare("SELECT course_id, intern_id FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit;
    }
    
    $course = $stmt->fetch();
    
    // Check if course already has an intern assigned
    if ($course['intern_id'] !== null && $course['intern_id'] != $intern_id) {
        echo json_encode(['success' => false, 'message' => 'This course already has a faculty intern assigned']);
        exit;
    }
    
    // Check if already enrolled
    $stmt = $conn->prepare("SELECT enrollment_id FROM Enrollment WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$intern_id, $course_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Already enrolled in this course']);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    // 1. Assign intern to course
    $stmt = $conn->prepare("UPDATE courses SET intern_id = ? WHERE course_id = ?");
    $stmt->execute([$intern_id, $course_id]);
    
    // 2. Auto-enroll with 'approved' status (no faculty approval needed)
    $stmt = $conn->prepare("
        INSERT INTO Enrollment (student_id, course_id, enrollment_type, status, requested_at, reviewed_at) 
        VALUES (?, ?, 'observer', 'approved', NOW(), NOW())
    ");
    $stmt->execute([$intern_id, $course_id]);
    
    // Commit transaction
    $conn->commit();
    
    error_log("Faculty Intern $intern_id successfully joined and assigned to course $course_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Successfully enrolled in course as Faculty Intern Observer!'
    ]);
    
} catch(PDOException $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Faculty Intern join error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to join course: ' . $e->getMessage()
    ]);
}
?>