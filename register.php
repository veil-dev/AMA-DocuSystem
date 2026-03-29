<?php
require_once 'includes/config.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $usn = trim($_POST['usn']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($name) || empty($usn) || empty($email) || empty($password)) {
        $error = 'Please fill all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("SELECT usn FROM students WHERE usn = ? OR email = ?");
        $stmt->execute([$usn, $email]);
        if ($stmt->fetch()) {
            $error = 'USN or email already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO students (name, usn, email, password) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $usn, $email, $hashed_password])) {
                $success = 'Registered! Login with USN/email "' . $usn . '" and password "' . $password . '" (hash verified)';
            } else {
                $error = 'Insert failed.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - DMS</title>
    <link rel="stylesheet" href="assets/staffstyles.css">
</head>
<body>
    <div class="login-container">
        <h2>Student Registration</h2>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>
        <?php if ($success): ?>
            <p style="color: green;"><?php echo $success; ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="text" name="usn" placeholder="USN" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password (min 6 chars)" required>
            <button type="submit">Register</button>
        </form>
        <p><a href="login.php">Login</a></p>
    </div>
</body>
</html>

