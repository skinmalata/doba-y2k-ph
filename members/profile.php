<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT id, username, first_name, last_name, photo, bio, graduation_year, phone, email, role, status, created_at
     FROM members WHERE id = ?'
);
$stmt->execute([$id]);
$member = $stmt->fetch();

if (!$member) {
    redirectWith('/members/index.php', 'Member not found.', 'error');
}

$pageTitle = $member['first_name'] . ' ' . $member['last_name'];
include __DIR__ . '/../includes/header.php';

$stmt = $pdo->prepare(
    'SELECT id, title, meeting_date FROM minutes WHERE author_id = ? ORDER BY meeting_date DESC LIMIT 5'
);
$stmt->execute([$id]);
$minutes = $stmt->fetchAll();
?>

<div class="mb-3">
    <a href="<?= url('/members/index.php') ?>" class="text-decoration-none">&larr; All Members</a>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm text-center">
            <div class="card-body">
                <?php if ($member['photo']): ?>
                    <img src="<?= url('/uploads/' . e($member['photo'])) ?>" alt=""
                         class="rounded-circle mb-3" width="150" height="150"
                         style="object-fit:cover">
                <?php else: ?>
                    <i class="bi bi-person-circle text-secondary" style="font-size:8rem"></i>
                <?php endif; ?>
                <h3><?= e($member['first_name'] . ' ' . $member['last_name']) ?></h3>
                <?php if ($member['graduation_year']): ?>
                    <p class="text-muted mb-1">Class of <?= e($member['graduation_year']) ?></p>
                <?php endif; ?>
                <span class="badge bg-<?= $member['role'] === 'admin' ? 'danger' : 'secondary' ?> me-1">
                    <?= e(ucfirst($member['role'])) ?>
                </span>
                <?php if ($member['status'] === 'pending'): ?>
                    <span class="badge bg-warning text-dark">Pending Approval</span>
                <?php endif; ?>
                <?php if ($member['bio']): ?>
                    <hr>
                    <p class="text-start"><?= e($member['bio']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5>Details</h5>
                <table class="table table-borderless">
                    <tr>
                        <th class="ps-0" style="width:140px">Username</th>
                        <td><?= e($member['username']) ?></td>
                    </tr>
                    <tr>
                        <th class="ps-0">Email</th>
                        <td><?= e($member['email']) ?></td>
                    </tr>
                    <?php if ($member['phone']): ?>
                    <tr>
                        <th class="ps-0">Phone</th>
                        <td><?= e($member['phone']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="ps-0">Joined</th>
                        <td><?= date('j F Y', strtotime($member['created_at'])) ?></td>
                    </tr>
                </table>

                <?php if (isAdmin() || (isLoggedIn() && $_SESSION['member_id'] == $id)): ?>
                <div class="mt-4">
                    <a href="<?= url('/admin/member-finances.php?id=' . $id) ?>" class="btn btn-outline-primary">
                        <i class="bi bi-cash-stack"></i> View Financial History
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($minutes): ?>
                <h5 class="mt-4">Recent Minutes Authored</h5>
                <ul class="list-group">
                    <?php foreach ($minutes as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <a href="<?= url('/minutes/view.php?id=' . $m['id']) ?>"><?= e($m['title']) ?></a>
                        <span class="text-muted small"><?= e($m['meeting_date']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
