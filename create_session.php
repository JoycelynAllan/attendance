<?php
// create_session.php - Create a new class session

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only faculty and faculty_intern can create sessions
if (!in_array($_SESSION['role'], ['faculty', 'faculty_intern'])) {
    echo json_encode(['success' => false, 'message' => 'Only faculty and faculty interns can create sessions']);
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

// Validate required fields
$required = ['course_id', 'date', 'start_time', 'end_time'];
foreach ($required as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Field '$field' is required"]);
        exit;
    }
}

$course_id = intval($data['course_id']);
$date = $data['date'];
$start_time = $data['start_time'];
$end_time = $data['end_time'];
$topic = isset($data['topic']) ? trim($data['topic']) : null;
$location = isset($data['location']) ? trim($data['location']) : null;

try {
    // Verify user has permission to create session for this course
    if ($role === 'faculty') {
        // Faculty can only create sessions for their own courses
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND faculty_id = ?");
        $stmt->execute([$course_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'You can only create sessions for your own courses']);
            exit;
        }
    } elseif ($role === 'faculty_intern') {
        // Interns can create sessions for courses they're assigned to
        $stmt = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND intern_id = ?");
        $stmt->execute([$course_id, $user_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'You can only create sessions for courses you are assigned to']);
            exit;
        }
    }
    
    // Check if new columns exist
    $checkColumns = $conn->query("SHOW COLUMNS FROM sessions LIKE 'code_expires_at'");
    $hasNewColumns = $checkColumns->rowCount() > 0;
    
    // Generate attendance code (6-digit random code)
    $attendance_code = str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
    
    // Code expires 2 hours after session end time
    $session_datetime = new DateTime("$date $end_time");
    $code_expires = clone $session_datetime;
    $code_expires->modify('+2 hours');
    $code_expires_at = $code_expires->format('Y-m-d H:i:s');
    
    if ($hasNewColumns) {
        // Insert session with new columns
        $stmt = $conn->prepare("
            INSERT INTO sessions (course_id, date, start_time, end_time, topic, location, attendance_code, code_expires_at, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $course_id,
            $date,
            $start_time,
            $end_time,
            $topic,
            $location,
            $attendance_code,
            $code_expires_at,
            $user_id
        ]);
    } else {
        // Insert session without new columns (fallback)
        $stmt = $conn->prepare("
            INSERT INTO sessions (course_id, date, start_time, end_time, topic, location)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $course_id,
            $date,
            $start_time,
            $end_time,
            $topic,
            $location
        ]);
        
        // Return message to update database
        echo json_encode([
            'success' => false,
            'message' => 'Database needs to be updated. Please run: http://localhost/attendance%20system/run_database_updates.php',
            'requires_update' => true
        ]);
        exit;
    }
    
    $session_id = $conn->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Session created successfully',
        'session_id' => $session_id,
        'attendance_code' => $attendance_code,
        'code_expires_at' => $code_expires_at
    ]);
    
} catch(PDOException $e) {
    error_log("create_session.php ERROR: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create session: ' . $e->getMessage()
    ]);
}
?>

