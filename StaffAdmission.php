<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('staff');

// Fetch all students
$stmt = $pdo->query("SELECT * FROM students ORDER BY name");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch document types (the five columns)
$stmt = $pdo->query("SELECT * FROM document_types ORDER BY id");
$docTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all documents (to build status matrix)
$stmt = $pdo->query("SELECT * FROM documents");
$allDocs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Parse filename to match student USN and type ID: filename like "doc_USN_TYPEID_timestamp.ext"
$docMatrix = [];
foreach ($allDocs as $doc) {
    if (preg_match('/doc_(.+?)_(\d+)_\d+\./', $doc['file_name'], $matches)) {
        $usn = stripslashes($matches[1]);
        $typeId = (int)$matches[2];
        $key = $usn . '_' . $typeId;
        $docMatrix[$key] = $doc; // latest overwrites
    }
    // Fallback DB match
    if ($doc['student_id'] && $doc['document_type_id']) {
        $key = $doc['student_id'] . '_' . $doc['document_type_id'];
        $docMatrix[$key] = $doc;
    }
}

// Calculate stats for sidebar
$totalStudents = count($students);
$totalDocs = count($allDocs);
$completeCount = 0;
$incompleteCount = 0;

foreach ($students as $student) {
    $missingCount = 0;
    $hasDocs = false;
    foreach ($docTypes as $type) {
        $key = $student['usn'] . '_' . $type['id'];
        if (!isset($docMatrix[$key]) || !file_exists($docMatrix[$key]['file_path'])) {
            $missingCount++;
        } else {
            $hasDocs = true;
        }
    }
    if ($missingCount === 0 && $hasDocs) {
        $completeCount++;
    } elseif ($missingCount > 0) {
        $incompleteCount++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard · Maroon text theme</title>
    <link rel="stylesheet" href="assets/staffstyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <!-- HEADER (maroon) -->
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Document Management System (Staff)</h1>
            <div class="user-info">
                <span><i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($_SESSION['name'] ?? 'Staff User'); ?></span>
                <span class="badge">Admissions Staff</span>
                <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <!-- MAIN CONTENT -->
        <div class="main-content">
            <!-- SIDEBAR (maroon details) -->
            <aside class="sidebar">
                <div class="staff-profile">
                    <div class="staff-avatar">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="staff-name"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Admissions Officer'); ?></div>
                    <div class="staff-role">Admissions Officer</div>
                </div>

                <div class="info-card">
                    <h3><i class="fas fa-chart-pie"></i> Overview</h3>
                    <div class="stats">
                        <div class="stat-item">
                            <div class="stat-number"><?= $totalStudents ?></div>
                            <div class="stat-label">Students</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?= $totalDocs ?></div>
                            <div class="stat-label">Docs</div>
                        </div>
                    </div>
                    <div style="margin-top:15px;">
                        <p>Complete: <strong><?= $completeCount ?></strong></p>
                        <p>Incomplete: <strong><?= $incompleteCount ?></strong></p>
                    </div>
                </div>

                <div class="filter-section">
                    <h4><i class="fas fa-filter"></i> Filter by Status</h4>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-all" checked>
                        <label for="filter-all">All Students</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-complete">
                        <label for="filter-complete">Complete</label>
                    </div>
                    <div class="filter-option">
                        <input type="checkbox" id="filter-incomplete">
                        <label for="filter-incomplete">Incomplete</label>
                    </div>
                </div>
            </aside>

            <!-- MAIN PANEL -->
            <main class="panel">
                <div class="panel-header">
                    <h2><i class="fas fa-table"></i> Student Document Checklist</h2>
                    <button class="toggle-table-btn" id="toggleFullTable" type="button">Show Full Table</button>
                </div>

                <!-- SEARCH -->
                <div class="search-container">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
<input type="text" id="searchInput" placeholder="Search by USN or student name...">
                    </div>
<button class="search-btn" id="searchBtn"><i class="fas fa-search"></i> Search</button>
                </div>

                <!-- DOCUMENT MATRIX (all students) -->
                <div style="overflow-x: auto;">
                    <table class="matrix-table">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <?php foreach ($docTypes as $type): ?>
                                    <th><?= htmlspecialchars($type['name']) ?></th>
                                <?php endforeach; ?>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td class="student-cell">
                                    <div class="student-name"><?= htmlspecialchars($student['name']) ?></div>
                                    <div class="student-usn"><?= htmlspecialchars($student['usn']) ?></div>
                                </td>

                                <?php foreach ($docTypes as $type): ?>
                                    <?php 
                                    $key = $student['usn'] . '_' . $type['id'];
                                    $doc = $docMatrix[$key] ?? null;
                                    $status = $doc ? ($doc['status'] ?? 'submitted') : 'missing';
                                    $docId = $doc ? $doc['id'] : 0;
                                    ?>
                                    <td>
                                        <div class="doc-status">
                                            <span class="status-badge status-<?= $status ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                            <div class="doc-actions">
                                                <?php if ($doc && file_exists($doc['file_path'])): ?>
                                                    <a href="view.php?id=<?= $docId ?>" class="doc-view-link" target="_blank">
                                                        <i class="fas fa-eye"></i><span>View</span>
                                                    </a>
                                                    <button type="button" class="delete-btn" data-doc-id="<?= $docId ?>" data-student="<?= $student['usn'] ?>" data-type="<?= $type['id'] ?>">
                                                        <i class="fas fa-trash"></i><span>Delete</span>
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <!-- Upload button (always present, but hidden when a document exists) -->
                                                <button type="button" class="upload-btn <?= ($doc && file_exists($doc['file_path'])) ? 'hidden' : '' ?>" data-student="<?= $student['usn'] ?>" data-type="<?= $type['id'] ?>">
                                                    <i class="fas fa-cloud-upload-alt"></i><span>Upload</span>
                                                </button>
                                                <input type="file" id="file-<?= $student['usn'] ?>-<?= $type['id'] ?>" style="display: none;" accept=".pdf,.doc,.docx,.txt,.jpg,.png">
                                            </div>
                                        </div>
                                    </td>
                                <?php endforeach; ?>

                                <td>
                                    <div class="action-buttons">
                                        <!-- Reminder button removed as requested -->
                                        <a href="student_docs.php?student_id=<?= $student['usn'] ?>" class="action-btn" title="View all documents">
                                            <i class="fas fa-folder-open"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mobile-table-fallback"></div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('click', function(e) {
            const uploadBtn = e.target.closest('.upload-btn');
            if (uploadBtn && !uploadBtn.disabled && !uploadBtn.classList.contains('hidden')) {
                const studentId = uploadBtn.dataset.student;
                const typeId = uploadBtn.dataset.type;
                const fileInput = document.getElementById(`file-${studentId}-${typeId}`);
                
                fileInput.click();
                
                fileInput.onchange = function() {
                    const file = fileInput.files[0];
                    if (!file) return;
                    
                    const formData = new FormData();
                    formData.append('student_id', studentId);
                    formData.append('type_id', typeId);
                    formData.append('document', file);
                    
                    uploadBtn.disabled = true;
                    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
                    
                    fetch('upload.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON:', text);
                            throw new Error('Server error');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            // Reload page to show new file
                            location.reload();
                        } else {
                            alert('Upload failed: ' + data.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Upload error: ' + error.message);
                    })
                    .finally(() => {
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><span>Upload</span>';
                        fileInput.value = '';
                    });
                };
                return;
            }
            
// Delete logic
            const deleteBtn = e.target.closest('.delete-btn');
            if (deleteBtn) {
                if (confirm('Are you sure you want to delete this document? This cannot be undone.')) {
                    const docId = deleteBtn.dataset.docId;
                    const formData = new FormData();
                    formData.append('id', docId);
                    
                    deleteBtn.disabled = true;
                    deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
                    
                    fetch('delete.php', {
                        method: 'POST',
                        body: formData,
                    })
                    .then(async response => {
                        const text = await response.text();
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON:', text);
                            throw new Error('Server error');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Delete failed: ' + (data.error || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Delete error: ' + error.message);
                    })
                    .finally(() => {
                        deleteBtn.disabled = false;
                        deleteBtn.innerHTML = '<i class="fas fa-trash"></i><span>Delete</span>';
                    });
                }
            }
        });

        // SEARCH FUNCTIONALITY
        function filterTable(searchTerm) {
            const rows = document.querySelectorAll('.matrix-table tbody tr');
            let visibleCount = 0;
            let noResultsRow = document.querySelector('.no-results-row');
            
            rows.forEach(row => {
                const nameCell = row.querySelector('.student-name');
                const usnCell = row.querySelector('.student-usn');
                const text = (nameCell ? nameCell.textContent : '') + ' ' + (usnCell ? usnCell.textContent : '');
                const matches = text.toLowerCase().includes(searchTerm.toLowerCase());
                
                if (matches) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show/hide no results message
            if (!noResultsRow) {
                noResultsRow = document.createElement('tr');
                noResultsRow.className = 'no-results-row';
                noResultsRow.innerHTML = '<td colspan="' + (document.querySelectorAll('.matrix-table thead th').length) + '">No students match your search.</td>';
                document.querySelector('.matrix-table tbody').appendChild(noResultsRow);
            }
            noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
        }

        // Event listeners
        document.getElementById('searchInput').addEventListener('input', function() {
            filterTable(this.value);
        });
        
        document.getElementById('searchBtn').addEventListener('click', function() {
            filterTable(document.getElementById('searchInput').value);
        });

        // Enter key on input
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                filterTable(this.value);
            }
        });
// Combined search + status filter
function applyFilters() {
    const rows = document.querySelectorAll('.matrix-table tbody tr');
    const searchTerm = document.getElementById('searchInput').value;
    const showAll = document.getElementById('filter-all').checked;
    const showComplete = document.getElementById('filter-complete').checked;
    const showIncomplete = document.getElementById('filter-incomplete').checked;
    
    let visibleCount = 0;
    let noResultsRow = document.querySelector('.no-results-row');
    
    rows.forEach(row => {
        // Search check
        const nameCell = row.querySelector('.student-name');
        const usnCell = row.querySelector('.student-usn');
        const searchText = (nameCell ? nameCell.textContent : '') + ' ' + (usnCell ? usnCell.textContent : '');
        const searchMatch = searchTerm === '' || searchText.toLowerCase().includes(searchTerm.toLowerCase());
        
        // Status check
        const missingBadges = row.querySelectorAll('.status-missing');
        const isComplete = missingBadges.length === 0;
        const statusMatch = showAll || (showComplete && isComplete) || (showIncomplete && !isComplete);
        
        if (searchMatch && statusMatch) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // No results
    if (!noResultsRow) {
        noResultsRow = document.createElement('tr');
        noResultsRow.className = 'no-results-row';
        noResultsRow.innerHTML = '<td colspan="' + (document.querySelectorAll('.matrix-table thead th').length) + '" style="padding: 2rem; text-align: center; color: #666;">No students match your filters.</td>';
        document.querySelector('.matrix-table tbody').appendChild(noResultsRow);
    }
    noResultsRow.style.display = visibleCount === 0 ? '' : 'none';
}

// Search events
document.getElementById('searchInput').addEventListener('input', applyFilters);
document.getElementById('searchBtn').addEventListener('click', applyFilters);
document.getElementById('searchInput').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') applyFilters();
});

// Status checkboxes
document.querySelectorAll('.filter-option input[type="checkbox"]').forEach(checkbox => {
    checkbox.addEventListener('change', applyFilters);
});

// Initial filter
applyFilters();
// Mobile table code unchanged...
    </script>

</body>
</html>
