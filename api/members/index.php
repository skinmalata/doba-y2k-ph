<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

$stmt = $pdo->query(
    'SELECT id, username, first_name, last_name, photo, graduation_year, bio
     FROM members WHERE status = \'active\'
     ORDER BY first_name, last_name'
);
$members = $stmt->fetchAll();

jsonResponse(['success' => true, 'members' => $members]);
