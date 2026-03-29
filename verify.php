<?php
require_once 'includes/config.php';
$username_or_email = 'test';
$password = 'test123';

$stmt = $pdo->prepare("SELECT usn, name, password FROM students WHERE name = ? LIMIT 1");
$stmt->execute(['test']);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if ($student) {
    $stored_pass = $student['password'];
    $match_plain = $stored_pass === $password;
    $match_verify = password_verify($password, $stored_pass);
    
    echo "USN: " . $student['usn'] . "\n";
    echo "Name: " . $student['name'] . "\n";
    echo "Stored hash starts: " . substr($stored_pass, 0, 30) . "\n";
    echo "Plain match: " . ($match_plain ? 'YES' : 'NO') . "\n";
    echo "Verify match: " . ($match_verify ? 'YES' : 'NO') . "\n";
} else {
    echo "No student 'test'\n";
}
?>

