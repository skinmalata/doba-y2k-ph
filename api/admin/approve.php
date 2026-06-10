<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($authMember['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Admin only'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$memberId = (int)($input['member_id'] ?? 0);

$stmt = $pdo->prepare('UPDATE members SET status = ? WHERE id = ?');
$stmt->execute(['active', $memberId]);

jsonResponse(['success' => true, 'message' => 'Member approved.']);
