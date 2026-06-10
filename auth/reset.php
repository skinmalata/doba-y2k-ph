<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';

if ($token === '') {
    redirectWith('/auth/login.php', 'Invalid reset link.', 'error');
}

$stmt = $pdo->prepare(
    'SELECT pr.id AS reset_id, pr.member_id, pr.expires_at
     FROM password_resets pr
     WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()'
);
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    redirectWith('/auth/forgot.php', 'This reset link is invalid or expired.', 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($password === '' || $confirm === '') {
        $_SESSION['error'] = 'Please fill in all fields.';
    } elseif ($password !== $confirm) {
        $_SESSION['error'] = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $_SESSION['error'] = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE members SET password = ? WHERE id = ?')
            ->execute([$hash, $reset['member_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')
            ->execute([$reset['reset_id']]);

        redirectWith('/auth/login.php', 'Password reset successful. You can now log in.');
    }

    header('Location: ' . url('/auth/reset.php?token=' . urlencode($token)));
    exit;
}

$pageTitle = 'Reset Password';
include __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Reset Password</h2>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
