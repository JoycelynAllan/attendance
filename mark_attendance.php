<?php
// mark_attendance.php - Mark attendance (for faculty/intern)

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only faculty and faculty_intern can mark attendance manually
if (!in_array($_SESSION['role'], ['faculty', 'faculty_intern'])) {
    echo json_encode(['success' => false, 'message' => 'Only faculty and faculty interns can mark attendance']);
    exit;
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['session_id']) || !isset($data['student_id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$session_id = intval($data['session_id']);
$student_id = intval($data['student_id']);
$status = $data['status']; // 'present', 'absent', 'late'
$remarks = isset($data['remarks']) ? trim($data['remarks']) : null;

// Validate status
if (!in_array($status, ['present', 'absent', 'late'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    // Verify session exists and user has permission
    $stmt = $conn->prepare("
        SELECT s.session_id, s.course_id, c.faculty_id, c.intern_id
        FROM sessions s
        INNER JOIN courses c ON s.course_id = c.course_id
        WHERE s.session_id = ?
    ");
    $stmt->execute([$session_id]);
    $session = $stmt->fetch();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Session not found']);
        exit;
    }
    
    // Check permissions
    if ($role === 'faculty' && $session['faculty_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    if ($role === 'faculty_intern' && $session['intern_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Verify student is enrolled in the course
    $stmt = $conn->prepare("
        SELECT enrollment_id FROM Enrollment 
        WHERE course_id = ? AND student_id = ? AND status = 'approved'
    ");
    $stmt->execute([$session['course_id'], $student_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Student is not enrolled in this course']);
        exit;
    }
    
    // Check if attendance already exists
    $stmt = $conn->prepare("
        SELECT attendance_id FROM attendance 
        WHERE session_id = ? AND student_id = ?
    ");
    $stmt->execute([$session_id, $student_id]);
    $existing = $stmt->fetch();
    
    $check_in_time = ($status === 'present' || $status === 'late') ? date('H:i:s') : null;
    
    if ($existing) {
        // Update existing attendance
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET status = ?, check_in_time = ?, remarks = ?, check_in_method = 'manual'
            WHERE attendance_id = ?
        ");
        $stmt->execute([$status, $check_in_time, $remarks, $existing['attendance_id']]);
    } else {
        // Insert new attendance
        $stmt = $conn->prepare("
            INSERT INTO attendance (session_id, student_id, status, check_in_time, remarks, check_in_method)
            VALUES (?, ?, ?, ?, ?, 'manual')
        ");
        $stmt->execute([$session_id, $student_id, $status, $check_in_time, $remarks]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Attendance marked successfully'
    ]);
    
} catch(PDOException $e) {
    error_log("mark_attendance.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to mark attendance: ' . $e->getMessage()
    ]);
}
?>

