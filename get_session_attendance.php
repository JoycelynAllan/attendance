<?php
// get_session_attendance.php - Get attendance for a specific session

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
$session_id = isset($_GET['session_id']) ? intval($_GET['session_id']) : 0;

if ($session_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid session ID']);
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
    
    // Get attendance for this session
    $stmt = $conn->prepare("
        SELECT a.attendance_id, a.student_id, a.status, a.check_in_time, a.checked_in_at, a.check_in_method,
               u.first_name, u.last_name, u.email, u.username
        FROM attendance a
        INNER JOIN users u ON a.student_id = u.user_id
        WHERE a.session_id = ?
        ORDER BY u.last_name ASC, u.first_name ASC
    ");
    
    $stmt->execute([$session_id]);
    $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'attendance' => $attendance,
        'count' => count($attendance)
    ]);
    
} catch(PDOException $e) {
    error_log("get_session_attendance.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch attendance: ' . $e->getMessage()
    ]);
}
?>

