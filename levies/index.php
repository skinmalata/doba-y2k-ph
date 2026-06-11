<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isAdmin()) {
    $levyId = (int)($_POST['levy_id'] ?? 0);

    if ($_POST['action'] === 'end') {
        $stmt = $pdo->prepare('UPDATE levies SET status = ? WHERE id = ?');
        $stmt->execute(['closed', $levyId]);
        redirectWith('/levies/index.php', 'Levy ended.');
    }

    if ($_POST['action'] === 'reopen') {
        $stmt = $pdo->prepare('UPDATE levies SET status = ? WHERE id = ?');
        $stmt->execute(['active', $levyId]);
        redirectWith('/levies/index.php', 'Levy reopened.');
    }
}

$pageTitle = 'Levies';
include __DIR__ . '/../includes/header.php';

$stmt = $pdo->query(
    'SELECT l.*, m.first_name, m.last_name
     FROM levies l
     JOIN members m ON m.id = l.created_by
     ORDER BY FIELD(l.status, "active", "closed"), l.created_at DESC'
);
$levies = $stmt->fetchAll();
?>

<h1 class="mb-4"><i class="bi bi-cash-stack"></i> Levies</h1>

<?php if (isAdmin()): ?>
    <a href="<?= url('/levies/create.php') ?>" class="btn btn-success mb-3">
        <i class="bi bi-plus-lg"></i> New Levy
    </a>
<?php endif; ?>

<?php if (empty($levies)): ?>
    <div class="alert alert-info">No levies have been created.</div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($levies as $levy):
            $memberCount = $pdo->prepare('SELECT COUNT(*) FROM members');
            $memberCount->execute();
            $totalMembers = $memberCount->fetchColumn();

            $paidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM levy_payments WHERE levy_id = ?');
            $paidStmt->execute([$levy['id']]);
            $totalPaidAmt = $paidStmt->fetchColumn();

            $userPaidStmt = $pdo->prepare('SELECT COALESCE(SUM(amount), 0) FROM levy_payments WHERE levy_id = ? AND member_id = ?');
            $userPaidStmt->execute([$levy['id'], $_SESSION['member_id']]);
            $userPaidAmt = $userPaidStmt->fetchColumn();
        ?>
        <?php
            $userPct = $levy['amount'] > 0 ? min(100, round(100 * $userPaidAmt / $levy['amount'])) : 0;
            $userFullyPaid = $userPaidAmt >= $levy['amount'];
            $memberProgress = $levy['amount'] > 0 ? min(100, round(100 * $totalPaidAmt / ($levy['amount'] * $totalMembers))) : 0;
        ?>
        <div class="col-md-6">
            <div class="card shadow-sm <?= $levy['status'] === 'closed' ? 'opacity-75' : '' ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="card-title mb-0"><?= e($levy['title']) ?></h5>
                        <div>
                            <?php if ($levy['status'] === 'closed'): ?>
                                <span class="badge bg-secondary me-1">Closed</span>
                            <?php endif; ?>
                            <span class="badge bg-<?= $userFullyPaid ? 'success' : ($userPaidAmt > 0 ? 'warning' : 'danger') ?>">
                                <?php if ($userFullyPaid): ?>Paid
                                <?php elseif ($userPaidAmt > 0): ?>Partial (<?= $userPct ?>%)
                                <?php else: ?>Owing
                                <?php endif; ?>
                            </span>
                        </div>
                    </div>
                    <p class="text-muted mb-1">
                        Amount: <strong>&#8358;<?= number_format($levy['amount'], 2) ?></strong>
                        <?php if ($userPaidAmt > 0): ?>
                            &middot; You paid: <strong>&#8358;<?= number_format($userPaidAmt, 2) ?></strong>
                        <?php endif; ?>
                    </p>
                    <?php if ($levy['due_date']): ?>
                        <p class="text-muted mb-1">Due: <?= e($levy['due_date']) ?></p>
                    <?php endif; ?>
                    <?php if ($levy['description']): ?>
                        <p class="mb-1"><?= e($levy['description']) ?></p>
                    <?php endif; ?>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Overall collection</small>
                            <small class="text-muted">&#8358;<?= number_format($totalPaidAmt) ?> / &#8358;<?= number_format($levy['amount'] * $totalMembers) ?></small>
                        </div>
                        <div class="progress" style="height:8px;">
                            <div class="progress-bar bg-info" style="width:<?= $memberProgress ?>%"></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <small class="text-muted">
                            <?= $totalPaidAmt > 0 ? number_format($totalPaidAmt, 2) : 0 ?> collected
                        </small>
                        <div>
                            <?php if (isAdmin()): ?>
                                <a href="<?= url('/levies/manage.php?id=' . $levy['id']) ?>" class="btn btn-sm btn-outline-primary">
                                    Manage Payments
                                </a>
                                <?php if ($levy['status'] === 'active'): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="end">
                                    <input type="hidden" name="levy_id" value="<?= $levy['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary"
                                            onclick="return confirm('End this levy? No further payments can be recorded.')">
                                        End Levy
                                    </button>
                                </form>
                                <?php else: ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="action" value="reopen">
                                    <input type="hidden" name="levy_id" value="<?= $levy['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-info">
                                        Reopen
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
