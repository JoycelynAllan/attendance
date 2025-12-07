<?php
// check_in_code.php - Student self-check-in using attendance code

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only students can use this
if ($_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Only students can check in using codes']);
    exit;
}

require_once 'db_connect.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['code']) || empty($data['code'])) {
    echo json_encode(['success' => false, 'message' => 'Attendance code is required']);
    exit;
}

$code = trim($data['code']);

try {
    // Find session with this code
    $stmt = $conn->prepare("
        SELECT s.session_id, s.course_id, s.date, s.start_time, s.end_time, 
               s.attendance_code, s.code_expires_at,
               c.course_code, c.course_name
        FROM sessions s
        INNER JOIN courses c ON s.course_id = c.course_id
        WHERE s.attendance_code = ?
    ");
    $stmt->execute([$code]);
    $session = $stmt->fetch();
    
    if (!$session) {
        echo json_encode(['success' => false, 'message' => 'Invalid attendance code']);
        exit;
    }
    
    // Check if code is expired
    if ($session['code_expires_at']) {
        $now = new DateTime();
        $expires = new DateTime($session['code_expires_at']);
        if ($expires < $now) {
            echo json_encode(['success' => false, 'message' => 'Attendance code has expired']);
            exit;
        }
    }
    
    // Verify student is enrolled in the course
    $stmt = $conn->prepare("
        SELECT enrollment_id FROM Enrollment 
        WHERE course_id = ? AND student_id = ? AND status = 'approved'
    ");
    $stmt->execute([$session['course_id'], $user_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'You are not enrolled in this course']);
        exit;
    }
    
    // Check if already checked in
    $stmt = $conn->prepare("
        SELECT attendance_id, status FROM attendance 
        WHERE session_id = ? AND student_id = ?
    ");
    $stmt->execute([$session['session_id'], $user_id]);
    $existing = $stmt->fetch();
    
    // Determine if late (check if current time is after start_time + 15 minutes)
    $session_datetime = new DateTime($session['date'] . ' ' . $session['start_time']);
    $late_threshold = clone $session_datetime;
    $late_threshold->modify('+15 minutes');
    $now = new DateTime();
    
    $status = ($now > $late_threshold) ? 'late' : 'present';
    $check_in_time = date('H:i:s');
    
    if ($existing) {
        // Update existing attendance
        $stmt = $conn->prepare("
            UPDATE attendance 
            SET status = ?, check_in_time = ?, checked_in_at = NOW(), check_in_method = 'code'
            WHERE attendance_id = ?
        ");
        $stmt->execute([$status, $check_in_time, $existing['attendance_id']]);
        $message = 'Attendance updated successfully';
    } else {
        // Insert new attendance
        $stmt = $conn->prepare("
            INSERT INTO attendance (session_id, student_id, status, check_in_time, checked_in_at, check_in_method)
            VALUES (?, ?, ?, ?, NOW(), 'code')
        ");
        $stmt->execute([$session['session_id'], $user_id, $status, $check_in_time]);
        $message = 'Attendance recorded successfully';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'status' => $status,
        'course' => $session['course_code'] . ' - ' . $session['course_name'],
        'session_date' => $session['date']
    ]);
    
} catch(PDOException $e) {
    error_log("check_in_code.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to check in: ' . $e->getMessage()
    ]);
}
?>

