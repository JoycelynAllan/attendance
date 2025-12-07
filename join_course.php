<?php
// join_course.php - Student request to join a course

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
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
$student_id = $_SESSION['user_id'];
$enrollment_type = isset($data['enrollment_type']) ? $data['enrollment_type'] : 'regular';

// Validate enrollment type
if (!in_array($enrollment_type, ['regular', 'auditor', 'observer'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid enrollment type']);
    exit;
}

try {
    // Check if course exists
    $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Course not found']);
        exit;
    }
    
    // Check if already enrolled or has pending request
    $stmt = $conn->prepare("SELECT status FROM enrollment WHERE student_id = ? AND course_id = ?");
    $stmt->execute([$student_id, $course_id]);
    
    if ($stmt->rowCount() > 0) {
        $enrollment = $stmt->fetch();
        if ($enrollment['status'] == 'approved') {
            echo json_encode(['success' => false, 'message' => 'Already enrolled in this course']);
        } elseif ($enrollment['status'] == 'pending') {
            echo json_encode(['success' => false, 'message' => 'Request already pending']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Previous request was rejected']);
        }
        exit;
    }
    
    // Create enrollment request (pending faculty approval)
    $stmt = $conn->prepare("
        INSERT INTO enrollment (student_id, course_id, enrollment_type, status, requested_at) 
        VALUES (?, ?, ?, 'pending', NOW())
    ");
    $stmt->execute([$student_id, $course_id, $enrollment_type]);
    
    echo json_encode([
        'success' => true,
        'message' => "Join request submitted as {$enrollment_type}. Waiting for faculty approval."
    ]);
    
} catch(PDOException $e) {
    error_log("Join course error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database Error: ' . $e->getMessage()
    ]);
}
?>