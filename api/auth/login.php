<?php
require_once __DIR__ . '/../config.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';

if ($username === '' || $password === '') {
    jsonResponse(['success' => false, 'message' => 'Please fill in all fields.']);
}

$stmt = $pdo->prepare('SELECT * FROM members WHERE (username = ? OR email = ?) AND status = ?');
$stmt->execute([$username, $username, 'active']);
$member = $stmt->fetch();

if (!$member || !password_verify($password, $member['password'])) {
    jsonResponse(['success' => false, 'message' => 'Invalid credentials or account not approved.']);
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+30 days'));

$stmt = $pdo->prepare('INSERT INTO auth_tokens (member_id, token, expires_at) VALUES (?, ?, ?)');
$stmt->execute([$member['id'], $token, $expires]);

jsonResponse([
    'success' => true,
    'token' => $token,
    'member_id' => (int)$member['id'],
    'member_name' => $member['first_name'] ?: $member['username'],
    'role' => $member['role'],
]);
