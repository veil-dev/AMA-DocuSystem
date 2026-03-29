<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$id = (int)$_GET['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    die('Invalid document ID.');
}

$stmt = $pdo->prepare("SELECT d.* FROM documents d WHERE d.id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc || !file_exists($doc['file_path'])) {
    http_response_code(404);
    die('Document not found.');
}

// Access check
if (!isset($_SESSION['role'])) {
    http_response_code(401);
    die('Please login.');
}
if ($_SESSION['role'] === 'staff' || ($_SESSION['role'] === 'student' && ($doc['student_id'] == $_SESSION['student_id'] || preg_match('/^doc_' . preg_quote($_SESSION['student_id'], '/') . '_/', $doc['file_name'])))) {
    // Allowed
} else {
    http_response_code(403);
    die('Access denied to this document.');
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($doc['file_path']) . '"');
header('Content-Length: ' . filesize($doc['file_path']));
readfile($doc['file_path']);
exit;
?>

