<?php
// get_courses.php - Get courses with faculty AND intern information

session_start();
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    error_log("get_courses.php - No session found");
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login']);
    exit;
}

require_once 'db_connect.php';

// Log which database we're connected to
$currentDb = 'unknown';
$dbConnectionInfo = [];
try {
    $stmt = $conn->query("SELECT DATABASE() as dbname");
    $dbInfo = $stmt->fetch();
    $currentDb = $dbInfo['dbname'];
    
    // Get course count for debugging
    $stmt = $conn->query("SELECT COUNT(*) as count FROM courses");
    $courseCount = $stmt->fetch()['count'];
    
    error_log("get_courses.php - Connected to database: $currentDb (Total courses: $courseCount)");
    $dbConnectionInfo = [
        'database' => $currentDb,
        'total_courses' => $courseCount
    ];
} catch(PDOException $e) {
    error_log("get_courses.php - Could not get database name: " . $e->getMessage());
    $dbConnectionInfo = ['error' => $e->getMessage()];
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

error_log("get_courses.php - User: $user_id, Role: $role, Type: $type");

try {
    if ($role === 'faculty') {
        // Faculty: Get courses created by this faculty
        error_log("Fetching faculty courses for user_id: $user_id");
        
        $stmt = $conn->prepare("
            SELECT c.course_id, c.course_code, c.course_name, c.description, 
                   c.credit_hours, c.semester,
                   u_faculty.first_name as faculty_first_name,
                   u_faculty.last_name as faculty_last_name,
                   u_faculty.email as faculty_email,
                   u_intern.first_name as intern_first_name,
                   u_intern.last_name as intern_last_name,
                   u_intern.email as intern_email,
                   COUNT(CASE WHEN e.status = 'approved' THEN 1 END) as enrolled_count
            FROM courses c
            LEFT JOIN Enrollment e ON c.course_id = e.course_id
            INNER JOIN users u_faculty ON c.faculty_id = u_faculty.user_id
            LEFT JOIN users u_intern ON c.intern_id = u_intern.user_id
            WHERE c.faculty_id = ?
            GROUP BY c.course_id
            ORDER BY c.course_code ASC
        ");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($courses) . " faculty courses");
        
    } elseif ($role === 'student') {
        if ($type === 'enrolled') {
            // Student: Get approved enrolled courses
            error_log("Fetching enrolled courses for student: $user_id");
            
            $stmt = $conn->prepare("
                SELECT c.course_id, c.course_code, c.course_name, c.description, 
                       c.credit_hours, c.semester,
                       u_faculty.first_name as faculty_first_name, 
                       u_faculty.last_name as faculty_last_name,
                       u_faculty.email as faculty_email,
                       u_intern.first_name as intern_first_name,
                       u_intern.last_name as intern_last_name,
                       u_intern.email as intern_email,
                       e.requested_at
                FROM Enrollment e
                INNER JOIN courses c ON e.course_id = c.course_id
                INNER JOIN users u_faculty ON c.faculty_id = u_faculty.user_id
                LEFT JOIN users u_intern ON c.intern_id = u_intern.user_id
                WHERE e.student_id = ? AND e.status = 'approved'
                ORDER BY e.requested_at DESC
            ");
            $stmt->execute([$user_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($courses) . " enrolled courses");
            
        } elseif ($type === 'pending') {
            // Student: Get pending enrollment requests (ONLY pending, not approved or rejected)
            error_log("Fetching pending courses for student: $user_id");
            
            $stmt = $conn->prepare("
                SELECT c.course_id, c.course_code, c.course_name, c.description,
                       u_faculty.first_name as faculty_first_name, 
                       u_faculty.last_name as faculty_last_name,
                       u_faculty.email as faculty_email,
                       u_intern.first_name as intern_first_name,
                       u_intern.last_name as intern_last_name,
                       u_intern.email as intern_email,
                       e.enrollment_type, e.requested_at, e.status
                FROM Enrollment e
                INNER JOIN courses c ON e.course_id = c.course_id
                INNER JOIN users u_faculty ON c.faculty_id = u_faculty.user_id
                LEFT JOIN users u_intern ON c.intern_id = u_intern.user_id
                WHERE e.student_id = ? AND e.status = 'pending'
                ORDER BY e.requested_at DESC
            ");
            $stmt->execute([$user_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($courses) . " pending courses (status='pending' only)");
            
        } else {
            // Student: Get available courses (not enrolled or pending)
            error_log("Fetching available courses for student: $user_id");
            
            $stmt = $conn->prepare("
                SELECT c.course_id, c.course_code, c.course_name, c.description, 
                       c.credit_hours, c.semester,
                       u_faculty.first_name as faculty_first_name, 
                       u_faculty.last_name as faculty_last_name,
                       u_intern.first_name as intern_first_name,
                       u_intern.last_name as intern_last_name
                FROM courses c
                INNER JOIN users u_faculty ON c.faculty_id = u_faculty.user_id
                LEFT JOIN users u_intern ON c.intern_id = u_intern.user_id
                WHERE c.course_id NOT IN (
                    SELECT course_id 
                    FROM Enrollment 
                    WHERE student_id = ?
                )
                ORDER BY c.course_code ASC
            ");
            $stmt->execute([$user_id]);
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($courses) . " available courses");
        }
        
    } elseif ($role === 'faculty_intern') {
        // Faculty Intern: Get all courses with indication if they're assigned
        error_log("Fetching all courses for faculty intern");
        
            $stmt = $conn->prepare("
                SELECT c.course_id, c.course_code, c.course_name, c.description,
                   c.credit_hours, c.semester,
                   u_faculty.first_name as faculty_first_name, 
                   u_faculty.last_name as faculty_last_name,
                   u_faculty.email as faculty_email,
                   u_intern.first_name as intern_first_name,
                   u_intern.last_name as intern_last_name,
                   u_intern.email as intern_email,
                   c.intern_id,
                   (c.intern_id = ?) as is_my_course,
                   COUNT(CASE WHEN e.status = 'approved' THEN 1 END) as enrolled_count
            FROM courses c
            INNER JOIN users u_faculty ON c.faculty_id = u_faculty.user_id
            LEFT JOIN users u_intern ON c.intern_id = u_intern.user_id
            LEFT JOIN Enrollment e ON c.course_id = e.course_id
            GROUP BY c.course_id
            ORDER BY c.course_code ASC
        ");
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($courses) . " total courses");
        
    } else {
        error_log("Invalid role: $role");
        echo json_encode(['success' => false, 'message' => 'Invalid role']);
        exit;
    }
    
    error_log("Returning " . count($courses) . " courses");
    
    // Include debug info in response if no courses found
    $response = [
        'success' => true,
        'courses' => $courses,
        'count' => count($courses)
    ];
    
    // Add debug info if no courses found to help diagnose
    if (count($courses) === 0) {
        $response['debug'] = [
            'database' => $currentDb,
            'user_id' => $user_id,
            'role' => $role,
            'type' => $type,
            'db_info' => $dbConnectionInfo
        ];
        error_log("get_courses.php - WARNING: No courses found for user_id=$user_id, role=$role, type=$type, database=$currentDb");
    }
    
    echo json_encode($response);
    
} catch(PDOException $e) {
    error_log("get_courses.php ERROR: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error' => $e->getMessage()
    ]);
}
?>