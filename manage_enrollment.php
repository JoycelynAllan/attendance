<?php
// manage_enrollment.php - Faculty approve/reject enrollment requests

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'faculty') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get pending enrollment requests for faculty's courses
    try {
        $faculty_id = $_SESSION['user_id'];
        
        $stmt = $conn->prepare("
            SELECT e.*, 
                   c.course_code, c.course_name, 
                   u.first_name, u.last_name, u.email, u.username,
                   s.enrollment_type as student_enrollment_type
            FROM enrollment e
            INNER JOIN courses c ON e.course_id = c.course_id
            INNER JOIN users u ON e.student_id = u.user_id
            LEFT JOIN students s ON e.student_id = s.student_id
            WHERE c.faculty_id = ? AND e.status = 'pending'
            ORDER BY e.requested_at DESC
        ");
        $stmt->execute([$faculty_id]);
        $requests = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'requests' => $requests
        ]);
        
    } catch(PDOException $e) {
        error_log("Get enrollment requests error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to fetch requests']);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Approve or reject enrollment request
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['enrollment_id']) || !isset($data['action'])) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $enrollment_id = (int)$data['enrollment_id'];
    $action = $data['action']; // 'approve' or 'reject'
    $faculty_id = $_SESSION['user_id'];
    
    try {
        // Verify the enrollment belongs to a course owned by this faculty
        $stmt = $conn->prepare("
            SELECT e.student_id, e.course_id, e.enrollment_type
            FROM enrollment e
            INNER JOIN courses c ON e.course_id = c.course_id
            WHERE e.enrollment_id = ? AND c.faculty_id = ? AND e.status = 'pending'
        ");
        $stmt->execute([$enrollment_id, $faculty_id]);
        
        if ($stmt->rowCount() === 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found or unauthorized']);
            exit;
        }
        
        $enrollment = $stmt->fetch();
        
        if ($action === 'approve') {
            // Update status to approved
            $stmt = $conn->prepare("
                UPDATE enrollment
                SET status = 'approved', reviewed_at = NOW() 
                WHERE enrollment_id = ?
            ");
            $stmt->execute([$enrollment_id]);
            
            // Update student's enrollment type if different
            if ($enrollment['enrollment_type']) {
                $stmt = $conn->prepare("
                    UPDATE students 
                    SET enrollment_type = ? 
                    WHERE student_id = ?
                ");
                $stmt->execute([$enrollment['enrollment_type'], $enrollment['student_id']]);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Student enrollment approved'
            ]);
            
        } elseif ($action === 'reject') {
            // Update status to rejected
            $stmt = $conn->prepare("
                UPDATE enrollment
                SET status = 'rejected', reviewed_at = NOW() 
                WHERE enrollment_id = ?
            ");
            $stmt->execute([$enrollment_id]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Student enrollment rejected'
            ]);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
        
    } catch(PDOException $e) {
        error_log("Manage enrollment error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to process request']);
    }
}
?>