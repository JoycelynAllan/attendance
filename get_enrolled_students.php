<?php
// get_enrolled_students.php - Get enrolled students for a course (for faculty/intern)

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!in_array($_SESSION['role'], ['faculty', 'faculty_intern'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

try {
    // Verify user has access to this course
    if ($role === 'faculty') {
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND faculty_id = ?");
        $stmt->execute([$course_id, $user_id]);
    } else {
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND intern_id = ?");
        $stmt->execute([$course_id, $user_id]);
    }
    
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Get enrolled students (only students, exclude faculty and faculty_intern)
    $stmt = $conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, u.email, u.username,
               e.enrollment_type, e.requested_at
        FROM enrollment e
        INNER JOIN users u ON e.student_id = u.user_id
        WHERE e.course_id = ? AND e.status = 'approved' AND u.role = 'student'
        ORDER BY u.last_name ASC, u.first_name ASC
    ");
    
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'students' => $students,
        'count' => count($students)
    ]);
    
} catch(PDOException $e) {
    error_log("get_enrolled_students.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch students: ' . $e->getMessage()
    ]);
}
?>

