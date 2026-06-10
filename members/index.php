<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Members';
include __DIR__ . '/../includes/header.php';

$search = trim($_GET['search'] ?? '');

if ($search) {
    $stmt = $pdo->prepare(
        "SELECT id, username, first_name, last_name, photo, graduation_year, bio
         FROM members
         WHERE status = 'active' AND (first_name LIKE ? OR last_name LIKE ? OR username LIKE ?)
         ORDER BY first_name, last_name"
    );
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query(
        'SELECT id, username, first_name, last_name, photo, graduation_year, bio
         FROM members WHERE status = \'active\'
         ORDER BY first_name, last_name'
    );
}
$members = $stmt->fetchAll();
?>

<h1 class="mb-4"><i class="bi bi-people-fill"></i> Our Members</h1>

<form method="get" class="row g-2 mb-4">
    <div class="col-auto flex-grow-1">
        <input type="text" name="search" class="form-control" placeholder="Search by name..."
               value="<?= e($search) ?>">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i> Search</button>
        <?php if ($search): ?>
            <a href="<?= url('/members/index.php') ?>" class="btn btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($members)): ?>
    <div class="alert alert-info">
        <?= $search ? 'No members match your search.' : 'No members registered yet.' ?>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($members as $member): ?>
        <div class="col-sm-6 col-md-4 col-lg-3">
            <a href="<?= url('/members/profile.php?id=' . $member['id']) ?>" class="text-decoration-none">
                <div class="card h-100 shadow-sm text-center">
                    <div class="card-body">
                        <?php if ($member['photo']): ?>
                            <img src="<?= url('/uploads/' . e($member['photo'])) ?>" alt=""
                                 class="rounded-circle mb-2" width="80" height="80"
                                 style="object-fit:cover">
                        <?php else: ?>
                            <i class="bi bi-person-circle text-secondary" style="font-size:4rem"></i>
                        <?php endif; ?>
                        <h6 class="card-title mb-0">
                            <?= e($member['first_name'] . ' ' . $member['last_name']) ?>
                        </h6>
                        <?php if ($member['graduation_year']): ?>
                            <small class="text-muted">Class of <?= e($member['graduation_year']) ?></small>
                        <?php endif; ?>
                        <?php if ($member['bio']): ?>
                            <p class="card-text small text-muted mt-1"><?= e(excerpt($member['bio'], 80)) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
