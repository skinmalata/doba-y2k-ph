<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$editId = (int)($_GET['id'] ?? 0);
$minute = null;

if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM minutes WHERE id = ?');
    $stmt->execute([$editId]);
    $minute = $stmt->fetch();
    if (!$minute) redirectWith('/minutes/index.php', 'Minute not found.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_method'] ?? '') === 'DELETE') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM minutes WHERE id = ?');
        $stmt->execute([$id]);
        redirectWith('/minutes/index.php', 'Minute deleted.');
    }

    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $meeting_date = $_POST['meeting_date'] ?? '';

    if ($title === '' || $content === '' || $meeting_date === '') {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: ' . url('/minutes/create.php' . ($editId ? "?id=$editId" : '')));
        exit;
    }

    if ($editId) {
        $stmt = $pdo->prepare(
            'UPDATE minutes SET title = ?, content = ?, meeting_date = ? WHERE id = ?'
        );
        $stmt->execute([$title, $content, $meeting_date, $editId]);
        redirectWith("/minutes/view.php?id=$editId", 'Minute updated.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO minutes (title, content, meeting_date, author_id) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$title, $content, $meeting_date, $_SESSION['member_id']]);
        $newId = $pdo->lastInsertId();
        redirectWith("/minutes/view.php?id=$newId", 'Minute published.');
    }
}

$pageTitle = $editId ? 'Edit Minute' : 'New Minute';
include __DIR__ . '/../includes/header.php';
?>

<h1><?= $editId ? 'Edit Minute' : 'Publish Meeting Minutes' ?></h1>

<div class="row">
    <div class="col-lg-8">
        <form method="post" class="card shadow-sm">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title</label>
                    <input type="text" name="title" class="form-control" required
                           value="<?= e($minute['title'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Meeting Date</label>
                    <input type="date" name="meeting_date" class="form-control" required
                           value="<?= e($minute['meeting_date'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Content</label>
                    <textarea name="content" class="form-control" rows="15" required><?= e($minute['content'] ?? '') ?></textarea>
                    <div class="form-text">You can use plain text. Line breaks are preserved.</div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> <?= $editId ? 'Update' : 'Publish' ?>
                </button>
                <a href="<?= url('/minutes/index.php') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
