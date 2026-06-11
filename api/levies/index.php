<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $levyId = (int)($input['levy_id'] ?? 0);
    $stmt = $pdo->prepare('SELECT id, status, amount AS levy_amount FROM levies WHERE id = ?');
    $stmt->execute([$levyId]);
    $levy = $stmt->fetch();
    if (!$levy) jsonResponse(['success' => false, 'message' => 'Levy not found']);
    if ($levy['status'] === 'closed') jsonResponse(['success' => false, 'message' => 'Levy is closed']);

    $payAmount = (float)($input['amount'] ?? $levy['levy_amount']);
    if ($payAmount <= 0) jsonResponse(['success' => false, 'message' => 'Invalid amount'], 400);

    $pdo->prepare('INSERT INTO levy_payments (levy_id, member_id, amount) VALUES (?, ?, ?)')
        ->execute([$levyId, $authMember['id'], $payAmount]);

    jsonResponse(['success' => true, 'message' => "Payment of &#8358;$payAmount recorded"]);
}

$stmt = $pdo->prepare(
    'SELECT l.id, l.title, l.amount, l.status, l.due_date, l.description,
            COALESCE(SUM(lp.amount), 0) AS user_paid
     FROM levies l
     LEFT JOIN levy_payments lp ON lp.levy_id = l.id AND lp.member_id = ?
     GROUP BY l.id
     ORDER BY l.created_at DESC'
);
$stmt->execute([$authMember['id']]);
$levies = $stmt->fetchAll();

$totalPaid = 0;
$totalOwing = 0;
foreach ($levies as &$l) {
    $l['amount'] = (float)$l['amount'];
    $l['user_paid'] = (float)$l['user_paid'];
    $remaining = max(0, $l['amount'] - $l['user_paid']);
    $l['remaining'] = $remaining;
    $totalPaid += $l['user_paid'];
    if ($l['status'] === 'active') $totalOwing += $remaining;
    $l['is_fully_paid'] = $l['user_paid'] >= $l['amount'] && $l['amount'] > 0;
}
unset($l);

jsonResponse([
    'success' => true,
    'levies' => $levies,
    'total_paid' => $totalPaid,
    'total_owing' => $totalOwing,
]);
