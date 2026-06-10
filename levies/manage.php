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
        $stmt = $pdo->prepare(
            'INSERT INTO levy_payments (levy_id, member_id, amount) VALUES (?, ?, ?)'
        );
        $stmt->execute([$levyId, $member_id, $levy['amount']]);

        $nameStmt = $pdo->prepare('SELECT first_name, last_name FROM members WHERE id = ?');
        $nameStmt->execute([$member_id]);
        $name = $nameStmt->fetch();
        $nameStr = $name['first_name'] . ' ' . $name['last_name'];

        $stmt = $pdo->prepare(
            'INSERT INTO transactions (type, category, description, amount, recorded_by) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute(['income', 'levy', "{$levy['title']} payment from $nameStr ({$levy['amount']})", $levy['amount'], $_SESSION['member_id']]);

        redirectWith('/levies/manage.php?id=' . $levyId, "Payment recorded for $nameStr.");
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

$payments = $pdo->prepare('SELECT member_id FROM levy_payments WHERE levy_id = ?');
$payments->execute([$levyId]);
$paidIds = $payments->fetchAll(PDO::FETCH_COLUMN);

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
            <?= count($paidIds) ?> / <?= count($members) ?> paid
        </span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Member</th>
                        <th>Username</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($members as $member): 
                        $isPaid = in_array($member['id'], $paidIds);
                    ?>
                    <tr>
                        <td><?= e($member['first_name'] . ' ' . $member['last_name']) ?></td>
                        <td><?= e($member['username']) ?></td>
                        <td>
                            <span class="badge bg-<?= $isPaid ? 'success' : 'warning' ?>">
                                <?= $isPaid ? 'Paid' : 'Owing' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($levy['status'] === 'active'): ?>
                            <form method="post" class="d-inline">
                                <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                <?php if ($isPaid): ?>
                                    <input type="hidden" name="action" value="unpay">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('Mark this member as unpaid?')">
                                        <i class="bi bi-x-circle"></i> Remove
                                    </button>
                                <?php else: ?>
                                    <input type="hidden" name="action" value="pay">
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check-circle"></i> Mark Paid
                                    </button>
                                <?php endif; ?>
                            </form>
                            <?php else: ?>
                                <small class="text-muted">Closed</small>
                            <?php endif; ?>
                        </td>
                    </tr>
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
