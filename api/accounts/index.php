<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

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
