<?php
// get_sessions.php - Get sessions for a course

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    } elseif ($role === 'faculty_intern') {
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND intern_id = ?");
        $stmt->execute([$course_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    } elseif ($role === 'student') {
        // Check if student is enrolled
        $stmt = $conn->prepare("
            SELECT enrollment_id FROM Enrollment 
            WHERE course_id = ? AND student_id = ? AND status = 'approved'
        ");
        $stmt->execute([$course_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    }
    
    // Check if new columns exist, if not use basic query
    $checkColumns = $conn->query("SHOW COLUMNS FROM sessions LIKE 'code_expires_at'");
    $hasNewColumns = $checkColumns->rowCount() > 0;
    
    if ($hasNewColumns) {
        // Get sessions for the course (with new columns)
        $stmt = $conn->prepare("
            SELECT s.session_id, s.course_id, s.date, s.start_time, s.end_time, 
                   s.topic, s.location, s.attendance_code, s.code_expires_at,
                   c.course_code, c.course_name,
                   COUNT(a.attendance_id) as attendance_count
            FROM sessions s
            INNER JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN attendance a ON s.session_id = a.session_id
            WHERE s.course_id = ?
            GROUP BY s.session_id
            ORDER BY s.date DESC, s.start_time DESC
        ");
    } else {
        // Get sessions for the course (without new columns - fallback)
        $stmt = $conn->prepare("
            SELECT s.session_id, s.course_id, s.date, s.start_time, s.end_time, 
                   s.topic, s.location,
                   NULL as attendance_code, NULL as code_expires_at,
                   c.course_code, c.course_name,
                   COUNT(a.attendance_id) as attendance_count
            FROM sessions s
            INNER JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN attendance a ON s.session_id = a.session_id
            WHERE s.course_id = ?
            GROUP BY s.session_id
            ORDER BY s.date DESC, s.start_time DESC
        ");
    }
    
    $stmt->execute([$course_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For students, hide attendance code if expired
    if ($role === 'student') {
        $now = new DateTime();
        foreach ($sessions as &$session) {
            if ($session['code_expires_at']) {
                $expires = new DateTime($session['code_expires_at']);
                if ($expires < $now) {
                    $session['attendance_code'] = null; // Hide expired codes
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'sessions' => $sessions,
        'count' => count($sessions)
    ]);
    
} catch(PDOException $e) {
    error_log("get_sessions.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch sessions: ' . $e->getMessage()
    ]);
}
?>

