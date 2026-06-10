<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $levyId = (int)($_POST['levy_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, status FROM levies WHERE id = ?');
    $stmt->execute([$levyId]);
    $levy = $stmt->fetch();
    if (!$levy) jsonResponse(['success' => false, 'message' => 'Levy not found']);
    if ($levy['status'] === 'closed') jsonResponse(['success' => false, 'message' => 'Levy is closed']);

    $stmt = $pdo->prepare('SELECT id FROM levy_payments WHERE levy_id = ? AND member_id = ?');
    $stmt->execute([$levyId, $authMember['id']]);
    if ($stmt->fetch()) jsonResponse(['success' => false, 'message' => 'Already paid']);

    $stmt = $pdo->prepare('SELECT amount FROM levies WHERE id = ?');
    $stmt->execute([$levyId]);
    $amount = $stmt->fetchColumn();

    $pdo->prepare('INSERT INTO levy_payments (levy_id, member_id, amount) VALUES (?, ?, ?)')
        ->execute([$levyId, $authMember['id'], $amount]);

    $stmt = $pdo->prepare('SELECT id FROM transactions WHERE description LIKE ? AND member_id = ?');
    $like = "Levy payment: %";
    $stmt->execute([$like, $authMember['id']]);

    jsonResponse(['success' => true, 'message' => 'Payment recorded']);
}

$stmt = $pdo->prepare(
    'SELECT l.id, l.title, l.amount, l.status, l.due_date, l.description,
            COALESCE(lp.id, 0) AS is_paid, lp.paid_at
     FROM levies l
     LEFT JOIN levy_payments lp ON lp.levy_id = l.id AND lp.member_id = ?
     ORDER BY l.created_at DESC'
);
$stmt->execute([$authMember['id']]);
$levies = $stmt->fetchAll();

$totalPaid = 0;
$totalOwing = 0;
foreach ($levies as &$l) {
    if ($l['is_paid']) {
        $totalPaid += $l['amount'];
    } elseif ($l['status'] === 'active') {
        $totalOwing += $l['amount'];
    }
    $l['is_paid'] = (int)$l['is_paid'];
    $l['amount'] = (float)$l['amount'];
}
unset($l);

jsonResponse([
    'success' => true,
    'levies' => $levies,
    'total_paid' => $totalPaid,
    'total_owing' => $totalOwing,
]);
