<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($authMember['role'] !== 'admin') {
    jsonResponse(['success' => false, 'message' => 'Admin only'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $input['action'] ?? '';
    $txnId = (int)($input['id'] ?? 0);

    if ($action === 'edit') {
        $type = $input['type'] ?? '';
        $category = trim($input['category'] ?? '');
        $description = trim($input['description'] ?? '');
        $amount = (float)($input['amount'] ?? 0);
        $memberId = $input['member_id'] ?? null;

        if (!in_array($type, ['income', 'expense']) || $description === '' || $amount <= 0) {
            jsonResponse(['success' => false, 'message' => 'Invalid fields'], 400);
        }

        $stmt = $pdo->prepare(
            'UPDATE transactions SET type = ?, category = ?, description = ?, amount = ?, member_id = ? WHERE id = ?'
        );
        $stmt->execute([$type, $category, $description, $amount, $memberId ? (int)$memberId : null, $txnId]);
        jsonResponse(['success' => true, 'message' => 'Transaction updated']);
    }

    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$txnId]);
        jsonResponse(['success' => true, 'message' => 'Transaction deleted']);
    }
}

$stmt = $pdo->query(
    'SELECT t.*, m.first_name AS member_first, m.last_name AS member_last,
            r.first_name AS recorded_first, r.last_name AS recorded_last
     FROM transactions t
     LEFT JOIN members m ON m.id = t.member_id
     JOIN members r ON r.id = t.recorded_by
     ORDER BY t.created_at DESC
     LIMIT 50'
);
$transactions = $stmt->fetchAll();

$totalIncome = 0;
$totalExpense = 0;
$result = array_map(function($t) use (&$totalIncome, &$totalExpense) {
    $amt = (float)$t['amount'];
    if ($t['type'] === 'income') $totalIncome += $amt;
    else $totalExpense += $amt;
    return [
        'id' => $t['id'],
        'type' => $t['type'],
        'category' => $t['category'],
        'description' => $t['description'],
        'amount' => $amt,
        'member_id' => $t['member_id'],
        'member_name' => $t['member_first'] ? $t['member_first'] . ' ' . $t['member_last'] : null,
        'recorded_by_name' => $t['recorded_first'] . ' ' . $t['recorded_last'],
        'created_at' => $t['created_at'],
    ];
}, $transactions);

jsonResponse([
    'success' => true,
    'transactions' => $result,
    'total_income' => $totalIncome,
    'total_expense' => $totalExpense,
]);
