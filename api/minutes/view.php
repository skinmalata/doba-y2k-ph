<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT m.*, mb.first_name, mb.last_name AS author_last_name
     FROM minutes m
     JOIN members mb ON mb.id = m.author_id
     WHERE m.id = ?'
);
$stmt->execute([$id]);
$minute = $stmt->fetch();

if (!$minute) {
    jsonResponse(['success' => false, 'message' => 'Not found'], 404);
}

$minute['author_name'] = $minute['first_name'] . ' ' . $minute['author_last_name'];
unset($minute['first_name'], $minute['author_last_name']);

jsonResponse(['success' => true, 'minute' => $minute]);
