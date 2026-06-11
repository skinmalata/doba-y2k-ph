<?php
session_start();
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';

$memberCount = $pdo->query('SELECT COUNT(*) FROM members')->fetchColumn();
$minutesCount = $pdo->query('SELECT COUNT(*) FROM minutes')->fetchColumn();

$latest = $pdo->query(
    'SELECT m.*, mb.first_name, mb.last_name
     FROM minutes m
     JOIN members mb ON mb.id = m.author_id
     ORDER BY m.meeting_date DESC
     LIMIT 3'
)->fetchAll();
?>

<div class="hero-wrapper">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <p class="lead fs-4">Keeping our brotherhood alive — one meeting at a time.</p>
    </div>
</div>

<div class="text-center mb-5">
    <h1 class="display-4 fw-bold mb-0" style="font-family:'Playfair Display',serif;color:var(--primary)">DOBA Millenium Set</h1>
    <p class="lead text-muted mt-1" style="font-family:'Playfair Display',serif">(Port Harcourt Branch)</p>
</div>

<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card stat-card text-center h-100">
            <div class="card-body">
                <i class="bi bi-people-fill display-4"></i>
                <h3><?= $memberCount ?></h3>
                <p>Members</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center h-100">
            <div class="card-body">
                <i class="bi bi-file-text-fill display-4"></i>
                <h3><?= $minutesCount ?></h3>
                <p>Meeting Minutes</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card text-center h-100">
            <div class="card-body">
                <i class="bi bi-calendar-event-fill display-4"></i>
                <h3>Since 2000</h3>
                <p>Years of Brotherhood</p>
            </div>
        </div>
    </div>
</div>

<div class="text-center mb-5">
    <div class="card shadow-sm border-gold d-inline-block mx-auto" style="max-width:500px">
        <div class="card-body py-3">
            <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                <i class="bi bi-phone display-6" style="color:var(--gold)"></i>
                <div class="text-start">
                    <strong class="d-block">Get the Mobile App</strong>
                    <small class="text-muted">Install as Progressive Web App</small>
                </div>
                <button id="installPwaBtn" class="btn btn-dark btn-sm d-none">
                    <i class="bi bi-download"></i> Install App
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        document.getElementById('installPwaBtn').classList.remove('d-none');
    });
    document.getElementById('installPwaBtn')?.addEventListener('click', async () => {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            const result = await deferredPrompt.userChoice;
            if (result.outcome === 'accepted') deferredPrompt = null;
        }
    });
</script>

<?php if (isLoggedIn()):
    $stmt = $pdo->prepare(
        'SELECT l.id, l.title, l.amount, l.due_date, l.status,
                COALESCE(SUM(lp.amount), 0) AS user_paid
         FROM levies l
         LEFT JOIN levy_payments lp ON lp.levy_id = l.id AND lp.member_id = ?
         GROUP BY l.id, l.title, l.amount, l.due_date, l.status, l.created_at
         ORDER BY l.created_at DESC'
    );
    $stmt->execute([$_SESSION['member_id']]);
    $memberLevies = $stmt->fetchAll();

    $totalLevies = 0;
    $totalPaid = 0;
    $totalOwing = 0;
    $paidLevies = [];
    $owingLevies = [];

    foreach ($memberLevies as $ml) {
        $totalLevies += $ml['amount'];
        $totalPaid += $ml['user_paid'];
        $remaining = max(0, $ml['amount'] - $ml['user_paid']);
        if ($ml['status'] === 'active') $totalOwing += $remaining;
        if ($ml['user_paid'] >= $ml['amount']) {
            $paidLevies[] = $ml;
        } elseif ($ml['user_paid'] > 0) {
            $owingLevies[] = $ml;
        } else {
            $owingLevies[] = $ml;
        }
    }

    if ($memberLevies):
?>
<h2 class="section-title"><i class="bi bi-receipt"></i> My Levy Dashboard</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid #0d6efd !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-0 small">Total Levies</p>
                <h4 class="text-primary mb-0">&#8358;<?= number_format($totalLevies, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid #28a745 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-0 small">Paid</p>
                <h4 class="text-success mb-0">&#8358;<?= number_format($totalPaid, 2) ?></h4>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center border-0 shadow-sm" style="border-left: 5px solid #dc3545 !important;">
            <div class="card-body py-3">
                <p class="text-muted mb-0 small">Owing</p>
                <h4 class="text-danger mb-0">&#8358;<?= number_format($totalOwing, 2) ?></h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php if ($paidLevies): ?>
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-success">
            <div class="card-header bg-success text-white d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-2"></i>
                <span class="fw-bold">Paid Levies (<?= count($paidLevies) ?>)</span>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($paidLevies as $pl): ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= e($pl['title']) ?></strong>
                        <br><small class="text-muted">Due: <?= $pl['due_date'] ? e($pl['due_date']) : 'N/A' ?></small>
                    </div>
                    <span class="text-success fw-bold">&#8358;<?= number_format($pl['amount'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($owingLevies): ?>
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-warning">
            <div class="card-header bg-warning text-dark d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <span class="fw-bold">Owing Levies (<?= count($owingLevies) ?>)</span>
            </div>
            <div class="list-group list-group-flush">
                <?php foreach ($owingLevies as $ol):
                    $olPct = $ol['amount'] > 0 ? min(100, round(100 * $ol['user_paid'] / $ol['amount'])) : 0;
                ?>
                <div class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong><?= e($ol['title']) ?></strong>
                        <br><small class="text-muted">Due: <?= $ol['due_date'] ? e($ol['due_date']) : 'N/A' ?></small>
                        <?php if ($ol['user_paid'] > 0): ?>
                        <div class="progress mt-1" style="height:6px;max-width:150px;">
                            <div class="progress-bar bg-warning" style="width:<?= $olPct ?>%"></div>
                        </div>
                        <small class="text-muted">&#8358;<?= number_format($ol['user_paid']) ?> paid</small>
                        <?php endif; ?>
                    </div>
                    <span class="text-danger fw-bold">&#8358;<?= number_format(max(0, $ol['amount'] - $ol['user_paid']), 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="text-center mb-4">
    <a href="<?= url('/levies/index.php') ?>" class="btn btn-primary">View All Levies</a>
</div>
<?php endif; endif; ?>

<?php if ($latest): ?>
<h2 class="section-title"><i class="bi bi-journal-text"></i> Latest Minutes</h2>
<div class="row g-4">
    <?php foreach ($latest as $minute): ?>
    <div class="col-md-4">
        <div class="card minute-card h-100">
            <div class="card-body">
                <div class="small text-muted mb-1">
                    <i class="bi bi-calendar"></i> <?= e($minute['meeting_date']) ?>
                </div>
                <h5 class="card-title"><?= e($minute['title']) ?></h5>
                <p class="card-text"><?= e(excerpt($minute['content'], 150)) ?></p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        by <?= e($minute['first_name'] . ' ' . $minute['last_name']) ?>
                    </small>
                    <a href="<?= url('/minutes/view.php?id=' . $minute['id']) ?>" class="btn btn-sm btn-outline-primary">Read</a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<div class="text-center mt-3">
    <a href="<?= url('/minutes/index.php') ?>" class="btn btn-primary">View All Minutes</a>
</div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
