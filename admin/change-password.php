<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

$memberId = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT id, username, first_name, last_name FROM members WHERE id = ?');
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    redirectWith('/admin/members.php', 'Member not found.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($new_password === '') {
        $_SESSION['error'] = 'New password cannot be empty.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE members SET password = ? WHERE id = ?')
            ->execute([$hash, $memberId]);
        $_SESSION['success'] = 'Password changed for ' . e($member['first_name'] . ' ' . $member['last_name']) . '.';
        header('Location: ' . url('/admin/members.php'));
        exit;
    }
}

$pageTitle = 'Change Password — ' . $member['first_name'] . ' ' . $member['last_name'];
include __DIR__ . '/../includes/header.php';
?>

<div class="mb-3">
    <a href="<?= url('/admin/members.php') ?>" class="text-decoration-none">&larr; Manage Members</a>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <form method="post" class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-key"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <p class="mb-3">Member: <strong><?= e($member['first_name'] . ' ' . $member['last_name']) ?></strong> (<?= e($member['username']) ?>)</p>

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="6" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-check-lg"></i> Change Password
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
