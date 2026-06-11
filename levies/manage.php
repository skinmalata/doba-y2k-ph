<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$levyId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM levies WHERE id = ?');
$stmt->execute([$levyId]);
$levy = $stmt->fetch();

if (!$levy) {
    redirectWith('/levies/index.php', 'Levy not found.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($levy['status'] === 'closed') {
        redirectWith('/levies/manage.php?id=' . $levyId, 'This levy has ended. Payments cannot be recorded.', 'error');
    }

    if ($action === 'pay') {
        $amount = str_replace(',', '', $_POST['amount'] ?? '');
        $amount = (float)$amount;
        if ($amount <= 0) {
            redirectWith('/levies/manage.php?id=' . $levyId, 'Invalid payment amount.', 'error');
        }

        $stmt = $pdo->prepare(
            'INSERT INTO levy_payments (levy_id, member_id, amount) VALUES (?, ?, ?)'
        );
        $stmt->execute([$levyId, $member_id, $amount]);

        $nameStmt = $pdo->prepare('SELECT first_name, last_name FROM members WHERE id = ?');
        $nameStmt->execute([$member_id]);
        $name = $nameStmt->fetch();
        $nameStr = $name['first_name'] . ' ' . $name['last_name'];

        $stmt = $pdo->prepare(
            'INSERT INTO transactions (type, category, description, amount, recorded_by) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['income', 'levy', "{$levy['title']} payment from $nameStr ($amount)", $amount, $_SESSION['member_id']]);

        redirectWith('/levies/manage.php?id=' . $levyId, "Payment of &#8358;$amount recorded for $nameStr.");
    }

    if ($action === 'edit_payment') {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $amount = str_replace(',', '', $_POST['amount'] ?? '');
        $amount = (float)$amount;
        if ($amount <= 0) {
            redirectWith('/levies/manage.php?id=' . $levyId, 'Invalid payment amount.', 'error');
        }
        $pdo->prepare('UPDATE levy_payments SET amount = ? WHERE id = ?')->execute([$amount, $paymentId]);
        redirectWith('/levies/manage.php?id=' . $levyId, 'Payment updated.');
    }

    if ($action === 'delete_payment') {
        $paymentId = (int)($_POST['payment_id'] ?? 0);
        $pdo->prepare('DELETE FROM levy_payments WHERE id = ?')->execute([$paymentId]);
        redirectWith('/levies/manage.php?id=' . $levyId, 'Payment deleted.');
    }

    if ($action === 'unpay') {
        $stmt = $pdo->prepare('DELETE FROM levy_payments WHERE levy_id = ? AND member_id = ?');
        $stmt->execute([$levyId, $member_id]);
        redirectWith('/levies/manage.php?id=' . $levyId, 'Payment removed.');
    }
}

$members = $pdo->query(
    'SELECT id, first_name, last_name, username FROM members ORDER BY first_name, last_name'
)->fetchAll();

$payments = $pdo->prepare(
    'SELECT lp.id, lp.member_id, lp.amount, lp.paid_at,
            m.first_name, m.last_name
     FROM levy_payments lp
     JOIN members m ON m.id = lp.member_id
     WHERE lp.levy_id = ?
     ORDER BY m.first_name, lp.paid_at'
);
$payments->execute([$levyId]);
$paymentRows = $payments->fetchAll();

$paidTotals = [];
foreach ($paymentRows as $pr) {
    $mid = $pr['member_id'];
    if (!isset($paidTotals[$mid])) {
        $paidTotals[$mid] = ['total' => 0, 'name' => $pr['first_name'] . ' ' . $pr['last_name']];
    }
    $paidTotals[$mid]['total'] += $pr['amount'];
}
$paidMemberCount = count($paidTotals);

$pageTitle = 'Manage Payments';
include __DIR__ . '/../includes/header.php';
?>

<h1><i class="bi bi-cash-coin"></i> <?= e($levy['title']) ?></h1>
<p class="text-muted mb-4">
    Amount: <strong>&#8358;<?= number_format($levy['amount'], 2) ?></strong>
    <?php if ($levy['due_date']): ?> &middot; Due: <?= e($levy['due_date']) ?><?php endif; ?>
    &middot; Status: <span class="badge bg-<?= $levy['status'] === 'active' ? 'success' : 'secondary' ?>"><?= e(ucfirst($levy['status'])) ?></span>
</p>

<div class="card shadow-sm">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <span>Members</span>
        <span class="badge bg-light text-dark">
            <?= $paidMemberCount ?> / <?= count($members) ?> paid
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
                    <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Member</th>
                        <th>Username</th>
                        <th>Progress</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member):
                        $mid = $member['id'];
                        $paid = $paidTotals[$mid]['total'] ?? 0;
                        $remaining = max(0, $levy['amount'] - $paid);
                        $pct = $levy['amount'] > 0 ? min(100, round(100 * $paid / $levy['amount'])) : 0;
                        $isFullyPaid = $paid >= $levy['amount'];
                    ?>
                    <tr>
                        <td><?= e($member['first_name'] . ' ' . $member['last_name']) ?></td>
                        <td><?= e($member['username']) ?></td>
                        <td style="min-width:200px;">
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height:10px;">
                                    <div class="progress-bar bg-<?= $isFullyPaid ? 'success' : ($pct > 0 ? 'warning' : 'secondary') ?>"
                                         style="width:<?= $pct ?>%"></div>
                                </div>
                                <small class="text-nowrap">
                                    &#8358;<?= number_format($paid) ?> / &#8358;<?= number_format($levy['amount']) ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <?php if ($levy['status'] === 'active'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="member_id" value="<?= $mid ?>">
                                <input type="hidden" name="action" value="pay">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8358;</span>
                                    <input type="number" name="amount" class="form-control" style="width:80px;"
                                           value="<?= $remaining ?>" min="1" step="0.01" required>
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </form>
                            <?php else: ?>
                                <small class="text-muted">Closed</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($paid > 0 && $levy['status'] === 'active'): ?>
                    <tr class="table-light">
                        <td colspan="2"></td>
                        <td colspan="2">
                            <?php foreach ($paymentRows as $pr): if ($pr['member_id'] != $mid) continue; ?>
                            <form method="post" class="d-inline-block me-2 mb-1">
                                <input type="hidden" name="action" value="edit_payment">
                                <input type="hidden" name="payment_id" value="<?= $pr['id'] ?>">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">&#8358;</span>
                                    <input type="number" name="amount" class="form-control" style="width:70px;"
                                           value="<?= $pr['amount'] ?>" min="0.01" step="0.01">
                                    <button type="submit" class="btn btn-sm btn-outline-primary"
                                            title="Update"><i class="bi bi-pencil"></i></button>
                                </div>
                            </form>
                            <form method="post" class="d-inline-block mb-1">
                                <input type="hidden" name="action" value="delete_payment">
                                <input type="hidden" name="payment_id" value="<?= $pr['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Delete this payment?')"
                                        title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    <a href="<?= url('/levies/index.php') ?>" class="btn btn-secondary">&larr; Back to Levies</a>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
