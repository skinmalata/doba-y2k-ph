<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$first_name = trim($input['first_name'] ?? '');
$last_name = trim($input['last_name'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    jsonResponse(['success' => false, 'message' => 'Required fields missing.']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'message' => 'Invalid email.']);
}

if (strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
}

$stmt = $pdo->prepare('SELECT id FROM members WHERE username = ? OR email = ?');
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Username or email already taken.']);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt = $pdo->prepare(
    'INSERT INTO members (username, email, password, first_name, last_name, status)
     VALUES (?, ?, ?, ?, ?, \'pending\')'
);
$stmt->execute([$username, $email, $hash, $first_name, $last_name]);

jsonResponse(['success' => true, 'message' => 'Registration submitted! An admin must approve your account.']);
