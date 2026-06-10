<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT id, username, first_name, last_name, photo, bio, graduation_year, phone, email, role, status, created_at
     FROM members WHERE id = ?'
);
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    jsonResponse(['success' => false, 'message' => 'Not found'], 404);
}

jsonResponse(['success' => true, 'member' => $member]);
