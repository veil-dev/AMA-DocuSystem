<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';
requireRole('student');

$studentId = $_SESSION['student_id'];
$message = '';
$type = ''; // 'success', 'error'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $message = 'Please fill all fields.';
        $type = 'error';
    } elseif ($new !== $confirm) {
        $message = 'New passwords do not match.';
        $type = 'error';
    } elseif (strlen($new) < 6) {
        $message = 'New password must be at least 6 characters.';
        $type = 'error';
    } else {
        // Verify current
        $stmt = $pdo->prepare("SELECT password FROM students WHERE usn = ?");
        $stmt->execute([$studentId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($current, $user['password'])) {
            // Update
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE students SET password = ? WHERE usn = ?");
            $stmt->execute([$hashed, $studentId]);

            $message = 'Password changed successfully.';
            $type = 'success';
        } else {
            $message = 'Current password incorrect.';
            $type = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password · DMS</title>
    <link rel="stylesheet" href="assets/studentstyles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .change-password-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .message {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #333;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .save-btn {
            width: 100%;
            padding: 12px;
            background: #8A0E0E;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
        }
        .save-btn:hover {
            background: #A61A1A;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        .back-link a {
            color: #8A0E0E;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1><i class="fas fa-key"></i> Change Password</h1>
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <a href="StudentPortal.php" class="header-link" style="color: white; text-decoration: none;"><i class="fas fa-arrow-left" style="margin-right: 8px;"></i> Back to Portal</a>
            </div>
        </div>
    </div>

    <div class="change-password-container">
        <?php if ($message): ?>
            <div class="message <?php echo $type; ?>">
                <i class="fas <?php echo $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="current">Current Password</label>
                <input type="password" id="current" name="current" required>
            </div>
            <div class="form-group">
                <label for="new">New Password</label>
                <input type="password" id="new" name="new" minlength="6" required>
            </div>
            <div class="form-group">
                <label for="confirm">Confirm New Password</label>
                <input type="password" id="confirm" name="confirm" required>
            </div>
            <button type="submit" class="save-btn">
                <i class="fas fa-save"></i> Change Password
            </button>
        </form>

        <div class="back-link">
            <a href="StudentPortal.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
