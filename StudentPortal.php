<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('student');

$studentId = getCurrentStudentId();
if (!$studentId) {
    die('Student ID not found. Login again.');
}

// Fetch student details - use usn since no id column
$stmt = $pdo->prepare("SELECT * FROM students WHERE usn = ?");
$stmt->execute([$studentId]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    die('Student not found.');
}

// Fetch student's documents w/ types
$stmt = $pdo->prepare("SELECT d.*, dt.name as type_name FROM documents d LEFT JOIN document_types dt ON d.document_type_id = dt.id WHERE d.student_id = ? AND (d.status != 'deleted' OR d.status IS NULL) ORDER BY d.created_at DESC, d.uploaded_at DESC");
$stmt->execute([$studentId]);
$docs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count stats
$submitted = $pending = $verified = $rejected = 0;
foreach ($docs as $doc) {
    $status = $doc['status'] ?? 'submitted';
    switch ($status) {
        case 'submitted': $submitted++; break;
        case 'pending': $pending++; break;
        case 'verified': $verified++; break;
        case 'rejected': $rejected++; break;
    }
}
$totalDocs = count($docs);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.5">
    <title>Student Dashboard · Maroon Theme</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="assets/studentstyles.css">
</head>
<body>
    <div class="dashboard">
        <!-- Header (maroon) -->
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Document Management System</h1>
            <div class="user-info">
                <span><i class="fas fa-user-graduate"></i> <?php echo htmlspecialchars($student['name']); ?></span>
                <span class="badge">USN: <?php echo htmlspecialchars($student['usn']); ?></span>
<a href="change_password.php" class="change-pw-btn" title="Change Password" style="color: white !important;"><i class="fas fa-key" style="color: white !important;"></i></a>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Sidebar (maroon accents) -->
            <aside class="sidebar">
                <div class="student-profile">
                    <div class="student-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="student-name"><?php echo htmlspecialchars($student['name']); ?></div>
                    <div class="student-usn">USN: <?php echo htmlspecialchars($student['usn']); ?></div>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-envelope"></i> Contact</h3>
                    <p><?php echo htmlspecialchars($student['email']); ?></p>
                    <small>Verified email</small>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-calendar-alt"></i> Documents Summary</h3>
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $submitted; ?></div>
                            <div class="stat-label">Submitted</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $verified; ?></div>
                            <div class="stat-label">Verified</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $rejected; ?></div>
                            <div class="stat-label">Pending/Rejected</div>
                        </div>
                    </div>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-clock"></i> Last Login</h3>
                    <p><?php echo date('M j, Y g:i A'); ?></p>
                </div>
            </aside>

            <!-- Main Panel -->
            <main class="panel">
                <div class="panel-header">
                    <h2><i class="fas fa-folder-open"></i> My Documents</h2>
                    <p class="note">Contact staff for document uploads or issues.</p>
                </div>

                <!-- Table with scroll wrapper -->
                <div class="table-wrapper">
                    <table class="documents-table">
                        <thead>
                            <tr>
                                <th>Document Name</th>
                                <th>Type</th>
                                <th>Date Uploaded</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem; color: #666;">
                                        <i class="fas fa-file fa-3x" style="opacity: 0.5;"></i>
                                        <p>No documents yet. Contact staff to upload your requirements.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($docs as $doc): ?>
                                    <tr>
                                        <td>
                                            <div class="document-name">
                                                <i class="fas fa-file-pdf"></i> <?php echo htmlspecialchars($doc['file_name']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($doc['type_name'] ?? pathinfo($doc['file_path'], PATHINFO_EXTENSION)); ?></td>
                                        <td><?php echo date('Y-m-d', strtotime($doc['created_at'] ?? $doc['uploaded_at'] ?? time())); ?></td>
                                        <td><span class="status status-<?php echo strtolower($doc['status'] ?? 'submitted'); ?>"><?php echo ucfirst($doc['status'] ?? 'Submitted'); ?></span></td>
                                        <td>
                                            <a href="download.php?id=<?php echo $doc['id']; ?>" class="action-btn"><i class="fas fa-download"></i> Download</a>
                                            <a href="view.php?id=<?php echo $doc['id']; ?>" class="action-btn" target="_blank"><i class="fas fa-eye"></i> View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="scroll-hint">← Swipe to see all columns →</div>
            </main>
        </div>
    </div>
</body>
</html>

