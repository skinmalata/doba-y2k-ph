<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $txnId = (int)($_POST['id'] ?? 0);
    if (isset($_POST['delete'])) {
        $pdo->prepare('DELETE FROM transactions WHERE id = ?')->execute([$txnId]);
        redirectWith('/accounts/index.php', 'Transaction deleted.');
    }
}

$pageTitle = 'Accounts';
include __DIR__ . '/../includes/header.php';

$income = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'income'"
)->fetchColumn();

$expenses = $pdo->query(
    "SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE type = 'expense'"
)->fetchColumn();

$balance = $income - $expenses;

$stmt = $pdo->query(
    'SELECT t.*, m.first_name, m.last_name,
            pm.first_name AS member_first, pm.last_name AS member_last
     FROM transactions t
     JOIN members m ON m.id = t.recorded_by
     LEFT JOIN members pm ON pm.id = t.member_id
     ORDER BY t.created_at DESC
     LIMIT 20'
);
$transactions = $stmt->fetchAll();
?>

<h1 class="mb-4"><i class="bi bi-wallet2"></i> Accounts</h1>

<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid #28a745 !important;">
            <div class="card-body">
                <p class="text-muted mb-1">Total Income</p>
                <h3 class="text-success mb-0">&#8358;<?= number_format($income, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid #dc3545 !important;">
            <div class="card-body">
                <p class="text-muted mb-1">Total Expenses</p>
                <h3 class="text-danger mb-0">&#8358;<?= number_format($expenses, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid <?= $balance >= 0 ? '#0d6efd' : '#dc3545' ?> !important;">
            <div class="card-body">
                <p class="text-muted mb-1">Balance</p>
                <h3 class="<?= $balance >= 0 ? 'text-primary' : 'text-danger' ?> mb-0">
                    &#8358;<?= number_format($balance, 2) ?>
                </h3>
            </div>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
    <div class="mb-3">
        <a href="<?= url('/accounts/create.php') ?>" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> Record Transaction
        </a>
    </div>
<?php endif; ?>

<?php if (empty($transactions)): ?>
    <div class="alert alert-info">No transactions recorded yet.</div>
<?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Recent Transactions</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Member</th>
                            <th>Recorded By</th>
                            <?php if (isAdmin()): ?><th>Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><?= date('j M Y', strtotime($t['created_at'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $t['type'] === 'income' ? 'success' : 'danger' ?>">
                                    <?= e(ucfirst($t['type'])) ?>
                                </span>
                            </td>
                            <td><?= e(ucfirst($t['category'])) ?></td>
                            <td><?= e($t['description']) ?></td>
                            <td class="<?= $t['type'] === 'income' ? 'text-success' : 'text-danger' ?> fw-bold">
                                <?= $t['type'] === 'income' ? '+' : '-' ?>&#8358;<?= number_format($t['amount'], 2) ?>
                            </td>
                            <td><?= $t['member_first'] ? e($t['member_first'] . ' ' . $t['member_last']) : '—' ?></td>
                            <td><?= e($t['first_name'] . ' ' . $t['last_name']) ?></td>
                            <?php if (isAdmin()): ?>
                            <td class="text-nowrap">
                                <a href="<?= url('/accounts/edit.php?id=' . $t['id']) ?>" class="btn btn-sm btn-outline-primary"
                                   title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this transaction?')">
                                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                                    <button type="submit" name="delete" class="btn btn-sm btn-outline-danger"
                                            title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
