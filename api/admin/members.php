<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($authMember['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Admin only'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? ($_POST['action'] ?? '');
$memberId = (int)($input['member_id'] ?? ($_POST['member_id'] ?? 0));

if ($action === 'toggle_admin') {
    $stmt = $pdo->prepare('SELECT role FROM members WHERE id = ?');
    $stmt->execute([$memberId]);
    $m = $stmt->fetch();
    if ($m) {
        $newRole = $m['role'] === 'admin' ? 'member' : 'admin';
        $stmt = $pdo->prepare('UPDATE members SET role = ? WHERE id = ?');
        $stmt->execute([$newRole, $memberId]);
    }
}

if ($action === 'delete') {
    $stmt = $pdo->prepare('DELETE FROM members WHERE id = ? AND role != ?');
    $stmt->execute([$memberId, 'admin']);
}

$stmt = $pdo->query(
    'SELECT id, username, email, first_name, last_name, role, status, graduation_year, created_at
     FROM members ORDER BY status ASC, role DESC, first_name, last_name'
);
$members = $stmt->fetchAll();

jsonResponse(['success' => true, 'members' => $members]);
