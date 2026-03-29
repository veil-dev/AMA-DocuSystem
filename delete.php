<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('staff');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $docId = (int)$_POST['id'];
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT file_path FROM documents WHERE id = ?");
    $stmt->execute([$docId]);
    $doc = $stmt->fetch();
    
    if ($doc) {
        if (file_exists($doc['file_path'])) {
            unlink($doc['file_path']);
        }
        
        $stmt = $pdo->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->execute([$docId]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Document not found']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}
?>
