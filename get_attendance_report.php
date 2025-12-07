<?php
// get_attendance_report.php - Get attendance reports for students

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
$report_type = isset($_GET['type']) ? $_GET['type'] : 'overall'; // 'daily' or 'overall'

if ($course_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid course ID']);
    exit;
}

try {
    // Verify student is enrolled
    if ($role === 'student') {
        $stmt = $conn->prepare("
            SELECT enrollment_id FROM enrollment 
            WHERE course_id = ? AND student_id = ? AND status = 'approved'
        ");
        $stmt->execute([$course_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit;
        }
    } elseif (in_array($role, ['faculty', 'faculty_intern'])) {
        // Faculty/intern can view reports for their courses
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
    }
    
    if ($report_type === 'daily') {
        // Daily attendance report
        $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
        
        $stmt = $conn->prepare("
            SELECT s.session_id, s.date, s.start_time, s.end_time, s.topic, s.location,
                   a.status, a.check_in_time, a.checked_in_at, a.check_in_method,
                   c.course_code, c.course_name
            FROM sessions s
            INNER JOIN courses c ON s.course_id = c.course_id
            LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
            WHERE s.course_id = ? AND s.date = ?
            ORDER BY s.start_time ASC
        ");
        $stmt->execute([$user_id, $course_id, $date]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'type' => 'daily',
            'date' => $date,
            'sessions' => $sessions,
            'count' => count($sessions)
        ]);
        
    } else {
        // Overall attendance report
        $stmt = $conn->prepare("
            SELECT 
                COUNT(s.session_id) as total_sessions,
                COUNT(a.attendance_id) as attended_sessions,
                SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_count,
                ROUND(COUNT(a.attendance_id) * 100.0 / NULLIF(COUNT(s.session_id), 0), 2) as attendance_percentage
            FROM sessions s
            LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
            WHERE s.course_id = ?
        ");
        $stmt->execute([$user_id, $course_id]);
        $overall = $stmt->fetch();
        
        // Get detailed session list
        $stmt = $conn->prepare("
            SELECT s.session_id, s.date, s.start_time, s.end_time, s.topic,
                   a.status, a.check_in_time, a.checked_in_at, a.check_in_method
            FROM sessions s
            LEFT JOIN attendance a ON s.session_id = a.session_id AND a.student_id = ?
            WHERE s.course_id = ?
            ORDER BY s.date DESC, s.start_time DESC
        ");
        $stmt->execute([$user_id, $course_id]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'type' => 'overall',
            'summary' => $overall,
            'sessions' => $sessions,
            'count' => count($sessions)
        ]);
    }
    
} catch(PDOException $e) {
    error_log("get_attendance_report.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to fetch attendance report: ' . $e->getMessage()
    ]);
}
?>

