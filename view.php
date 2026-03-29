<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

$id = (int)$_GET['id'] ?? 0;
if (!$id) {
    die('Invalid document ID.');
}

$stmt = $pdo->prepare("SELECT d.* FROM documents d WHERE d.id = ?");
$stmt->execute([$id]);
$doc = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$doc || !file_exists($doc['file_path'])) {
    die('Document not found.');
}

// Access check
if (!isset($_SESSION['role'])) {
    die('Please login.');
}
if ($_SESSION['role'] === 'staff' || ($_SESSION['role'] === 'student' && ($doc['student_id'] == $_SESSION['student_id'] || preg_match('/^doc_' . preg_quote($_SESSION['student_id'], '/') . '_/', $doc['file_name'])))) {
    // Allowed
} else {
    die('Access denied to this document.');
}

$ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
$mime = match($ext) {
    'pdf' => 'application/pdf',
    'jpg','jpeg' => 'image/jpeg',
    'png' => 'image/png',
    'doc','docx' => 'application/msword',
    default => 'application/octet-stream'
};
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Document</title>
</head>
<body style="margin:0;">
    <?php if (in_array($ext, ['pdf','jpg','jpeg','png'])): ?>
        <iframe src="<?php echo htmlspecialchars($doc['file_path']); ?>" style="width:100vw;height:100vh;border:none;"></iframe>
    <?php else: ?>
        <p>Preview not supported for this file type. <a href="download.php?id=<?php echo $id; ?>">Download</a></p>
    <?php endif; ?>
</body>
</html>

