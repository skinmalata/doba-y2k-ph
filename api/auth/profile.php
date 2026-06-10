<?php
require_once __DIR__ . '/../config.php';
$member = requireAuth($pdo);

jsonResponse([
    'success' => true,
    'member' => $member,
]);
