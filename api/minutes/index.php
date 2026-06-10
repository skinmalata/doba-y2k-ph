<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

$stmt = $pdo->query(
    'SELECT m.*, mb.first_name, mb.last_name AS author_last_name
     FROM minutes m
     JOIN members mb ON mb.id = m.author_id
     ORDER BY m.meeting_date DESC'
);
$minutes = $stmt->fetchAll();

$result = array_map(function($m) {
    return [
        'id' => $m['id'],
        'title' => $m['title'],
        'content' => substr($m['content'], 0, 200),
        'meeting_date' => $m['meeting_date'],
        'author_id' => $m['author_id'],
        'author_name' => $m['first_name'] . ' ' . $m['author_last_name'],
        'created_at' => $m['created_at'],
    ];
}, $minutes);

jsonResponse(['success' => true, 'minutes' => $result]);
