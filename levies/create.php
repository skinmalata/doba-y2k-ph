<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $amount = str_replace(',', '', $_POST['amount'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date = $_POST['due_date'] ?? '';

    if ($title === '' || $amount === '' || !is_numeric($amount) || (float)$amount <= 0) {
        $_SESSION['error'] = 'Title and a valid amount are required.';
        header('Location: ' . url('/levies/create.php'));
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO levies (title, amount, description, due_date, created_by) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$title, $amount, $description, $due_date ?: null, $_SESSION['member_id']]);

    redirectWith('/levies/index.php', 'Levy created successfully.');
}

$pageTitle = 'New Levy';
include __DIR__ . '/../includes/header.php';
?>

<h1>Create New Levy</h1>

<div class="row">
    <div class="col-lg-6">
        <form method="post" class="card shadow-sm">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Levy Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required
                           placeholder="e.g. Annual Dues 2026">
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (&#8358;) <span class="text-danger">*</span></label>
                    <input type="text" name="amount" class="form-control" required
                           placeholder="e.g. 5000">
                </div>
                <div class="mb-3">
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"
                              placeholder="Optional description or notes about this levy"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Create Levy
                </button>
                <a href="<?= url('/levies/index.php') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
