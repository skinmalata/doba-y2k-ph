<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($authMember['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Admin only'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$memberId = (int)($input['member_id'] ?? 0);
$password = $input['password'] ?? '';

if ($password === '' || strlen($password) < 6) {
    jsonResponse(['success' => false, 'message' => 'Password must be at least 6 characters.']);
}

$hash = password_hash($password, PASSWORD_BCRYPT);
$pdo->prepare('UPDATE members SET password = ? WHERE id = ?')
    ->execute([$hash, $memberId]);

jsonResponse(['success' => true, 'message' => 'Password changed.']);
