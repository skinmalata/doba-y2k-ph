<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Meeting Minutes';
include __DIR__ . '/../includes/header.php';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$total = $pdo->query('SELECT COUNT(*) FROM minutes')->fetchColumn();
$totalPages = (int)ceil($total / $perPage);

$stmt = $pdo->prepare(
    'SELECT m.*, mb.first_name, mb.last_name
     FROM minutes m
     JOIN members mb ON mb.id = m.author_id
     ORDER BY m.meeting_date DESC
     LIMIT ? OFFSET ?'
);
$stmt->execute([$perPage, $offset]);
$minutes = $stmt->fetchAll();
?>

<h1 class="mb-4"><i class="bi bi-journal-text"></i> Meeting Minutes</h1>

<?php if (isLoggedIn() && isAdmin()): ?>
    <div class="mb-3">
        <a href="<?= url('/minutes/create.php') ?>" class="btn btn-success">
            <i class="bi bi-plus-lg"></i> New Minute
        </a>
    </div>
<?php endif; ?>

<?php if (empty($minutes)): ?>
    <div class="alert alert-info">No minutes have been published yet.</div>
<?php else: ?>
    <div class="list-group mb-4">
        <?php foreach ($minutes as $m): ?>
        <a href="<?= url('/minutes/view.php?id=' . $m['id']) ?>" class="list-group-item list-group-item-action">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h5 class="mb-1"><?= e($m['title']) ?></h5>
                    <p class="mb-1 text-muted"><?= e(excerpt($m['content'], 200)) ?></p>
                </div>
                <small class="text-nowrap ms-3 text-muted">
                    <i class="bi bi-calendar"></i> <?= e($m['meeting_date']) ?>
                </small>
            </div>
            <small class="text-muted">
                by <?= e($m['first_name'] . ' ' . $m['last_name']) ?>
                &middot; <?= timeAgo($m['created_at']) ?>
            </small>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
