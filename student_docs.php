<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('staff');

$studentUsn = $_GET['student_id'] ?? '';
if (!$studentUsn) {
    die('No student USN.');
}

// Fetch student by USN (since param is USN)
$stmt = $pdo->prepare("SELECT * FROM students WHERE usn = ?");
$stmt->execute([$studentUsn]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    die('Student not found.');
}

// Fetch all doc types (fallback if not exist)
$docTypes = []; 
try {
    $stmt = $pdo->query("SELECT * FROM document_types ORDER BY id");
    $docTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // No document_types table
}

// Fetch student's documents w/ types
try {
    $stmt = $pdo->prepare("SELECT d.*, dt.name as type_name FROM documents d LEFT JOIN document_types dt ON d.document_type_id = dt.id WHERE d.student_id = ? ORDER BY d.uploaded_at DESC");
    $stmt->execute([$studentUsn]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback old query
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE description LIKE ? ORDER BY uploaded_at DESC");
    $stmt->execute(["%{$studentUsn}%"]);
    $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Build matrix for completeness
$studentDocTypes = [];
foreach ($docs as $d) {
    $typeId = $d['document_type_id'] ?? 0;
    $studentDocTypes[$typeId][] = $d; // array per type
}
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($student['name']); ?> - Docs</title>
    <link rel="stylesheet" href="assets/staffstyles.css">
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1><?php echo htmlspecialchars($student['name']); ?> (<?php echo htmlspecialchars($student['usn']); ?>)</h1>
            <a href="StaffAdmission.php">Back</a>
        </div>
        <table>
            <thead>
                <tr><th>Type</th><th>File</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
                <?php foreach ($docTypes as $type): ?>
                    <?php $docList = $studentDocTypes[$type['id']] ?? []; $doc = end($docList); // latest ?>
                    <tr>
                        <td><?php echo htmlspecialchars($type['name']); ?></td>
                        <td><?php echo $doc ? htmlspecialchars($doc['file_name'] ?? 'N/A') : 'Missing'; ?></td>
                        <td><?php echo $doc ? ucfirst($doc['status'] ?? 'submitted') : 'missing'; ?></td>
                        <td>
                            <?php if ($doc && isset($doc['id']) && file_exists($doc['file_path'])): ?>
                                <a href="view.php?id=<?php echo $doc['id']; ?>" target="_blank">View</a> |
                                <a href="download.php?id=<?php echo $doc['id']; ?>">Download</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>

