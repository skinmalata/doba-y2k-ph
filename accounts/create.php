<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = str_replace(',', '', $_POST['amount'] ?? '');
    $member_id = $_POST['type'] === 'income' ? ((int)($_POST['member_id'] ?? 0)) : null;

    if (!in_array($type, ['income', 'expense']) || $description === '' || !is_numeric($amount) || (float)$amount <= 0) {
        $_SESSION['error'] = 'Please fill all fields with valid values.';
        header('Location: ' . url('/accounts/create.php'));
        exit;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO transactions (type, category, description, amount, member_id, recorded_by) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$type, $category, $description, $amount, $member_id ?: null, $_SESSION['member_id']]);

    redirectWith('/accounts/index.php', 'Transaction recorded.');
}

$members = $pdo->query(
    'SELECT id, first_name, last_name FROM members ORDER BY first_name, last_name'
)->fetchAll();

$pageTitle = 'Record Transaction';
include __DIR__ . '/../includes/header.php';
?>

<h1>Record Transaction</h1>

<div class="row">
    <div class="col-lg-6">
        <form method="post" class="card shadow-sm">
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Type <span class="text-danger">*</span></label>
                    <select name="type" id="typeSelect" class="form-select" required>
                        <option value="">— Select —</option>
                        <option value="income">Income</option>
                        <option value="expense">Expense</option>
                    </select>
                </div>
                <div class="mb-3" id="memberField" style="display:none;">
                    <label class="form-label">From Member <span class="text-danger">*</span></label>
                    <select name="member_id" class="form-select">
                        <option value="">— Select Member —</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= e($m['first_name'] . ' ' . $m['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="general">General</option>
                        <option value="levy">Levy</option>
                        <option value="donation">Donation</option>
                        <option value="event">Event</option>
                        <option value="project">Project</option>
                        <option value="dues">Dues</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description <span class="text-danger">*</span></label>
                    <input type="text" name="description" class="form-control" required
                           placeholder="What is this for?">
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount (&#8358;) <span class="text-danger">*</span></label>
                    <input type="text" name="amount" class="form-control" required
                           placeholder="e.g. 10000">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Record
                </button>
                <a href="<?= url('/accounts/index.php') ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('typeSelect').addEventListener('change', function() {
    document.getElementById('memberField').style.display = this.value === 'income' ? 'block' : 'none';
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
