<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    'SELECT m.*, mb.first_name, mb.last_name, mb.photo AS author_photo
     FROM minutes m
     JOIN members mb ON mb.id = m.author_id
     WHERE m.id = ?'
);
$stmt->execute([$id]);
$minute = $stmt->fetch();

if (!$minute) {
    redirectWith('/minutes/index.php', 'Minute not found.', 'error');
}

$pageTitle = $minute['title'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= url('/minutes/index.php') ?>" class="text-decoration-none">&larr; Back to Minutes</a>
</div>

<article class="card shadow-sm">
    <div class="card-body p-4">
        <h1 class="card-title"><?= e($minute['title']) ?></h1>
        <div class="text-muted mb-4">
            <i class="bi bi-calendar"></i> Meeting date: <strong><?= e($minute['meeting_date']) ?></strong>
            &middot; Posted <?= timeAgo($minute['created_at']) ?>
            <?php if ($minute['updated_at'] !== $minute['created_at']): ?>
                &middot; Updated <?= timeAgo($minute['updated_at']) ?>
            <?php endif; ?>
        </div>
        <hr>
        <div class="content"><?= nl2br(e($minute['content'])) ?></div>
        <hr>
        <div class="d-flex align-items-center text-muted">
            <i class="bi bi-person-circle fs-4 me-2"></i>
            <span>Recorded by <?= e($minute['first_name'] . ' ' . $minute['last_name']) ?></span>
        </div>
    </div>
</article>

<?php if (isLoggedIn() && isAdmin()): ?>
<div class="mt-3">
    <a href="<?= url('/minutes/create.php?id=' . $minute['id']) ?>" class="btn btn-warning">
        <i class="bi bi-pencil"></i> Edit
    </a>
    <form method="post" action="<?= url('/minutes/create.php') ?>" class="d-inline"
          onsubmit="return confirm('Delete this minute permanently?');">
        <input type="hidden" name="_method" value="DELETE">
        <input type="hidden" name="id" value="<?= $minute['id'] ?>">
        <button type="submit" class="btn btn-danger">
            <i class="bi bi-trash"></i> Delete
        </button>
    </form>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
