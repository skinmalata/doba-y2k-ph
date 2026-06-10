<?php
require_once __DIR__ . '/../config/database.php';

$memberCount = $pdo->query('SELECT COUNT(*) FROM members WHERE status = \'active\'')->fetchColumn();
$minutesCount = $pdo->query('SELECT COUNT(*) FROM minutes')->fetchColumn();

$latest = $pdo->query(
    'SELECT m.*, mb.first_name, mb.last_name
     FROM minutes m
     JOIN members mb ON mb.id = m.author_id
     ORDER BY m.meeting_date DESC
     LIMIT 3'
)->fetchAll();

$minutes = array_map(function($m) {
    $m['author_name'] = $m['first_name'] . ' ' . $m['last_name'];
    unset($m['first_name'], $m['last_name']);
    return $m;
}, $latest);

jsonResponse([
    'success' => true,
    'member_count' => (int)$memberCount,
    'minutes_count' => (int)$minutesCount,
    'latest_minutes' => $minutes,
]);
