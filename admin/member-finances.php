<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$memberId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT id, username, first_name, last_name, photo, graduation_year, email, phone
     FROM members WHERE id = ?'
);
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    redirectWith('/admin/members.php', 'Member not found.', 'error');
}

$pageTitle = $member['first_name'] . ' ' . $member['last_name'] . ' — Finances';
include __DIR__ . '/../includes/header.php';

$memberLevies = [];
$totalPaid = 0;
$totalOwing = 0;
$memberPayments = [];
$memberTransactions = [];

try {
    $levies = $pdo->prepare(
        'SELECT l.id, l.title, l.amount, l.due_date,
                COALESCE(lp.id, 0) AS is_paid, lp.paid_at
         FROM levies l
         LEFT JOIN levy_payments lp ON lp.levy_id = l.id AND lp.member_id = ?
         ORDER BY l.created_at DESC'
    );
    $levies->execute([$memberId]);
    $memberLevies = $levies->fetchAll();

    foreach ($memberLevies as $ml) {
        if ($ml['is_paid']) $totalPaid += $ml['amount'];
        else $totalOwing += $ml['amount'];
    }
} catch (Exception $e) {
    echo '<div class="alert alert-warning">Could not load levy data: ' . e($e->getMessage()) . '</div>';
}

try {
    $payments = $pdo->prepare(
        'SELECT lp.*, l.title AS levy_title
         FROM levy_payments lp
         JOIN levies l ON l.id = lp.levy_id
         WHERE lp.member_id = ?
         ORDER BY lp.paid_at DESC'
    );
    $payments->execute([$memberId]);
    $memberPayments = $payments->fetchAll();
} catch (Exception $e) {
    echo '<div class="alert alert-warning">Could not load payment records: ' . e($e->getMessage()) . '</div>';
}

try {
    $transactions = $pdo->prepare(
        'SELECT t.*, m.first_name, m.last_name
         FROM transactions t
         JOIN members m ON m.id = t.recorded_by
         WHERE t.member_id = ?
         ORDER BY t.created_at DESC'
    );
    $transactions->execute([$memberId]);
    $memberTransactions = $transactions->fetchAll();
} catch (Exception $e) {
    echo '<div class="alert alert-warning">Could not load transaction data: ' . e($e->getMessage()) . '</div>';
}
?>

<div class="mb-3">
    <a href="<?= url('/admin/members.php') ?>" class="text-decoration-none">&larr; Manage Members</a>
</div>

<div class="d-flex align-items-center gap-3 mb-4">
    <?php if ($member['photo']): ?>
        <img src="<?= url('/uploads/' . e($member['photo'])) ?>" alt="" class="rounded-circle"
             width="60" height="60" style="object-fit:cover">
    <?php else: ?>
        <i class="bi bi-person-circle text-secondary" style="font-size:3rem"></i>
    <?php endif; ?>
    <div>
        <h2 class="mb-0"><?= e($member['first_name'] . ' ' . $member['last_name']) ?></h2>
        <small class="text-muted"><?= e($member['username']) ?> &middot; <?= e($member['email']) ?>
            <?php if ($member['graduation_year']): ?> &middot; Class of <?= e($member['graduation_year']) ?><?php endif; ?>
        </small>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left:5px solid #0d6efd">
            <div class="card-body py-3">
                <p class="text-muted mb-0 small">Total Levies</p>
                <h4 class="text-primary mb-0">&#8358;<?= number_format($totalPaid + $totalOwing, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left:5px solid #28a745">
            <div class="card-body py-3">
                <p class="text-muted mb-0 small">Total Paid</p>
                <h4 class="text-success mb-0">&#8358;<?= number_format($totalPaid, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left:5px solid #dc3545">
            <div class="card-body py-3">
                <p class="text-muted mb-0 small">Outstanding</p>
                <h4 class="text-danger mb-0">&#8358;<?= number_format($totalOwing, 2) ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white">
        <h5 class="mb-0"><i class="bi bi-receipt"></i> Levy History</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Levy</th>
                        <th>Amount</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th>Paid On</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($memberLevies)): ?>
                        <tr><td colspan="5" class="text-center text-muted">No levies found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($memberLevies as $ml): ?>
                        <tr>
                            <td><?= e($ml['title']) ?></td>
                            <td>&#8358;<?= number_format($ml['amount'], 2) ?></td>
                            <td><?= $ml['due_date'] ? e($ml['due_date']) : 'N/A' ?></td>
                            <td>
                                <?php if ($ml['is_paid']): ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Owing</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $ml['paid_at'] ? date('j M Y', strtotime($ml['paid_at'])) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($memberPayments): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0"><i class="bi bi-check-circle"></i> Payment Records</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Levy</th>
                        <th>Amount</th>
                        <th>Date Paid</th>
                        <th>Receipt Ref</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($memberPayments as $p): ?>
                    <tr>
                        <td><?= e($p['levy_title']) ?></td>
                        <td>&#8358;<?= number_format($p['amount'], 2) ?></td>
                        <td><?= date('j M Y g:ia', strtotime($p['paid_at'])) ?></td>
                        <td><?= $p['receipt_ref'] ? e($p['receipt_ref']) : '—' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($memberTransactions): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="bi bi-wallet2"></i> Other Transactions</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Recorded By</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($memberTransactions as $t): ?>
                    <tr>
                        <td><?= date('j M Y', strtotime($t['created_at'])) ?></td>
                        <td><span class="badge bg-<?= $t['type'] === 'income' ? 'success' : 'danger' ?>"><?= e(ucfirst($t['type'])) ?></span></td>
                        <td><?= e($t['description']) ?></td>
                        <td class="fw-bold <?= $t['type'] === 'income' ? 'text-success' : 'text-danger' ?>">&#8358;<?= number_format($t['amount'], 2) ?></td>
                        <td><?= e($t['first_name'] . ' ' . $t['last_name']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
