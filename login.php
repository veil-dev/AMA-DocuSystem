<?php
require_once 'includes/config.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username_or_email = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $error = 'Please fill all fields.';
    } else {
        // Staff
        if ($username_or_email === 'staff' && $password === 'staff123') {
            $_SESSION['user_id'] = 999;
            $_SESSION['role'] = 'staff';
            $_SESSION['name'] = 'Staff User';
            header('Location: StaffAdmission.php');
            exit;
        }

        // Student - no id alias, use usn
        try {
            $stmt = $pdo->prepare("SELECT usn, name, email, password FROM students WHERE email = ? OR usn = ? OR name = ? LIMIT 1");
            $stmt->execute([$username_or_email, $username_or_email, $username_or_email]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student && $student['password']) {
                $stored_pass = $student['password'];
                if (password_verify($password, $stored_pass)) {
                    $_SESSION['user_id'] = $student['usn'];
                    $_SESSION['role'] = 'student';
                    $_SESSION['student_id'] = $student['usn'];
                    $_SESSION['name'] = $student['name'];
                    header('Location: StudentPortal.php');
                    exit;
                }
            }
            $error = 'Invalid credentials.';
        } catch (PDOException $e) {
            $error = 'DB error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Document Management System</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
        }

        /* Top Header */
        .header {
            background-color: #8A0E0E;
            padding: 18px 30px;
            color: white;
            font-size: 22px;
            font-weight: bold;
        }

        /* Centered Login Box */
        .login-container {
            width: 100%;
            margin-top: 70px;
            display: flex;
            justify-content: center;
        }

        .login-box {
            background: white;
            width: 350px;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            text-align: center;
        }

        .login-box h2 {
            color: #8A0E0E;
            margin-bottom: 20px;
        }

        .error {
            background-color: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .input-group {
            text-align: left;
            margin-bottom: 15px;
        }

        .input-group label {
            font-weight: bold;
            font-size: 14px;
            display: block;
            margin-bottom: 5px;
        }

        .input-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #d1d1d1;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }

        .login-btn {
            width: 100%;
            padding: 12px;
            background-color: #8A0E0E;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }

        .login-btn:hover {
            background-color: #A61A1A;
        }

        .footer-text {
            margin-top: 15px;
            font-size: 13px;
            color: #555;
        }

        .footer-text a {
            color: #8A0E0E;
            text-decoration: none;
        }

        .footer-text a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <div class="header">Document Management System</div>

    <div class="login-container">
        <div class="login-box">
            <h2>Login</h2>

            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <label for="username">Email / USN / Name / staff</label>
                    <input type="text" id="username" name="username" placeholder="Enter your email, USN, name or 'staff'" required>
                </div>

                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="login-btn">Login</button>

                <div class="footer-text">
                    Don't have an account? <a href="register.php">Register</a>
                </div>

                <div class="footer-text">
                    Staff login? Use 'staff' / 'staff123'
                </div>
            </form>
        </div>
    </div>

</body>
</html>
