<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $_SESSION['error'] = 'Please fill in all fields.';
        header('Location: ' . url('/auth/login.php'));
        exit;
    }

    $stmt = $pdo->prepare('SELECT * FROM members WHERE username = ? OR email = ?');
    $stmt->execute([$username, $username]);
    $member = $stmt->fetch();

    if ($member && password_verify($password, $member['password'])) {
        if ($member['status'] === 'pending') {
            $_SESSION['error'] = 'Your account is pending admin approval. Please check back later.';
            header('Location: ' . url('/auth/login.php'));
            exit;
        }
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_name'] = $member['first_name'] ?: $member['username'];
        $_SESSION['role'] = $member['role'];

        $redirect = $_SESSION['redirect_after'] ?? '/index.php';
        unset($_SESSION['redirect_after']);
        redirectWith($redirect, 'Welcome back, ' . $member['first_name'] . '!');
    }

    $_SESSION['error'] = 'Invalid username/email or password.';
    header('Location: ' . url('/auth/login.php'));
    exit;
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Login</h2>
                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Username or Email</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Login</button>
                    <p class="text-end mt-2 mb-0">
                        <a href="<?= url('/auth/forgot.php') ?>" class="small">Forgot password?</a>
                    </p>
                </form>
                <p class="text-center mt-3 mb-0">
                    Not a member? <a href="<?= url('/auth/register.php') ?>">Register here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
