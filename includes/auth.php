<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'student'; // default student

// Fetch user details 
if ($role === 'staff') {
    $user = ['name' => $_SESSION['name'] ?? 'Staff User', 'role' => 'staff'];
} else {
    // student from students table
    try {
        global $pdo;
$stmt = $pdo->prepare("SELECT usn, name, email FROM students WHERE usn = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        // Note: password not fetched for security
    } catch (Exception $e) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'User fetch error']);
            exit;
        }
        $user = null;
    }
}

if (!$user) {
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }
    session_destroy();
    header('Location: login.php');
    exit;
}

$_SESSION['student_id'] = $user['usn'] ?? null; // for students
$_SESSION['name'] = $user['name'];

// Role check function
function requireRole($required) {
    global $role;
    if ($role !== $required) {
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Access denied: Insufficient role']);
            exit;
        }
        header('Location: ' . ($required === 'student' ? 'StudentPortal.php' : 'StaffAdmission.php'));
        exit;
    }
}

// For student docs fetch, get student_id
function getCurrentStudentId() {
    global $pdo;
    if (isset($_SESSION['student_id'])) {
        return $_SESSION['student_id'];
    }
    // Fallback if direct student_id param (staff view)
    if (isset($_GET['student_id'])) {
        return (int)$_GET['student_id'];
    }
    return null;
}
?>
