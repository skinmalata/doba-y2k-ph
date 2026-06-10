<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $_SESSION['error'] = 'Please enter your email address.';
        header('Location: ' . url('/auth/forgot.php'));
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, first_name FROM members WHERE email = ?');
    $stmt->execute([$email]);
    $member = $stmt->fetch();

    if ($member) {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $pdo->prepare(
            'INSERT INTO password_resets (member_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$member['id'], $token, $expires]);

        $resetLink = 'https://dobay2k.unaux.com' . url('/auth/reset.php?token=' . $token);

        $subject = 'Password Reset — DOBA Y2k (Port Harcourt Branch)';
        $message = "Hi {$member['first_name']},\n\n"
                 . "Click the link below to reset your password:\n"
                 . "$resetLink\n\n"
                 . "This link expires in 1 hour.\n\n"
                 . "If you didn't request this, ignore this email.\n";
        $headers = 'From: noreply@dobay2k.unaux.com';

        @mail($email, $subject, $message, $headers);

        $_SESSION['reset_link'] = $resetLink;
        $_SESSION['success'] = 'If that email is registered, a reset link has been generated.';
    } else {
        $_SESSION['error'] = 'No account found with that email.';
    }

    header('Location: ' . url('/auth/forgot.php'));
    exit;
}
?>

<div class="row justify-content-center">
    <div class="col-md-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h2 class="card-title text-center mb-4">Forgot Password</h2>

                <?php if (isset($_SESSION['reset_link'])): ?>
                <div class="alert alert-info">
                    <strong>Reset link (copy this):</strong><br>
                    <a href="<?= e($_SESSION['reset_link']) ?>"><?= e($_SESSION['reset_link']) ?></a>
                    <?php unset($_SESSION['reset_link']); ?>
                </div>
                <?php endif; ?>

                <form method="post">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
                </form>
                <p class="text-center mt-3 mb-0">
                    <a href="<?= url('/auth/login.php') ?>">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
