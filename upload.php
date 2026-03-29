<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('staff');

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id']) && isset($_POST['type_id']) && isset($_FILES['document'])) {
        $studentId = $_POST['student_id']; // USN as string
        $typeId = (int)$_POST['type_id'];
        $file = $_FILES['document'];

        // Validation
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedExts = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'txt'];
        $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $file['error']);
        }
        if ($file['size'] > $maxSize) {
            throw new Exception('File too large. Max 5MB.');
        }
        if (!in_array($fileExt, $allowedExts)) {
            throw new Exception('Invalid file type. Allowed: ' . implode(', ', $allowedExts));
        }

        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileName = 'doc_' . $studentId . '_' . $typeId . '_' . time() . '.' . $fileExt;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO documents (title, description, file_name, file_path, file_size, user_id, student_id, document_type_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'submitted', NOW())");
            $stmt->execute([
                $file['name'],
                "Staff upload for student {$studentId} (type {$typeId})",
                $file['name'],
                $filePath,
                $file['size'],
                (int)$_SESSION['user_id'],
                $studentId,
                $typeId,
            ]);
            $docId = $pdo->lastInsertId();

            echo json_encode(['success' => true, 'doc_id' => $docId]);
        } else {
            throw new Exception('Failed to move uploaded file');
        }
    } else {
        throw new Exception('Invalid request: Missing required fields');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
?>

