<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

$stmt = $pdo->prepare('SELECT * FROM members WHERE id = ?');
$stmt->execute([$_SESSION['member_id']]);
$member = $stmt->fetch();

if (!$member) {
    session_destroy();
    header('Location: ' . url('/auth/login.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $graduation_year = $_POST['graduation_year'] ?? '';
    $bio = trim($_POST['bio'] ?? '');

    if ($email === '') {
        $_SESSION['error'] = 'Email is required.';
        header('Location: ' . url('/members/edit.php'));
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? AND id != ?');
    $stmt->execute([$email, $_SESSION['member_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email already in use by another member.';
        header('Location: ' . url('/members/edit.php'));
        exit;
    }

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($current_password !== '' || $new_password !== '' || $confirm_password !== '') {
        if (!password_verify($current_password, $member['password'])) {
            $_SESSION['error'] = 'Current password is incorrect.';
            header('Location: ' . url('/members/edit.php'));
            exit;
        }
        if ($new_password === '') {
            $_SESSION['error'] = 'New password cannot be empty.';
            header('Location: ' . url('/members/edit.php'));
            exit;
        }
        if ($new_password !== $confirm_password) {
            $_SESSION['error'] = 'New passwords do not match.';
            header('Location: ' . url('/members/edit.php'));
            exit;
        }
        if (strlen($new_password) < 6) {
            $_SESSION['error'] = 'New password must be at least 6 characters.';
            header('Location: ' . url('/members/edit.php'));
            exit;
        }
        $hash = password_hash($new_password, PASSWORD_BCRYPT);
        $pdo->prepare('UPDATE members SET password = ? WHERE id = ?')
            ->execute([$hash, $_SESSION['member_id']]);
        $password_changed = true;
    }

    $photo = $member['photo'];
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = 'Photo must be JPG, PNG, GIF, or WebP.';
            header('Location: ' . url('/members/edit.php'));
            exit;
        }
        $filename = 'member_' . $_SESSION['member_id'] . '_' . time() . ".$ext";
        move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__ . '/../uploads/' . $filename);
        if ($member['photo'] && file_exists(__DIR__ . '/../uploads/' . $member['photo'])) {
            unlink(__DIR__ . '/../uploads/' . $member['photo']);
        }
        $photo = $filename;
    }

    $stmt = $pdo->prepare(
        'UPDATE members SET first_name = ?, last_name = ?, email = ?, phone = ?,
         graduation_year = ?, bio = ?, photo = ? WHERE id = ?'
    );
    $stmt->execute([$first_name, $last_name, $email, $phone, $graduation_year ?: null, $bio, $photo, $_SESSION['member_id']]);

    $_SESSION['member_name'] = $first_name ?: $member['username'];
    $msg = isset($password_changed) ? 'Profile and password updated.' : 'Profile updated.';
    redirectWith('/members/profile.php?id=' . $_SESSION['member_id'], $msg);
}

$pageTitle = 'Edit Profile';
include __DIR__ . '/../includes/header.php';
?>

<h1>Edit Profile</h1>

<div class="row">
    <div class="col-lg-8">
        <form method="post" enctype="multipart/form-data" class="card shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= e($member['first_name']) ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= e($member['last_name']) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Email <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control" required
                           value="<?= e($member['email']) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= e($member['phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Graduation Year</label>
                    <select name="graduation_year" class="form-select">
                        <option value="">— Select —</option>
                        <?php for ($y = date('Y'); $y >= 1960; $y--): ?>
                            <option value="<?= $y ?>" <?= $member['graduation_year'] == $y ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Bio</label>
                    <textarea name="bio" class="form-control" rows="4"><?= e($member['bio'] ?? '') ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profile Photo</label>
                    <?php if ($member['photo']): ?>
                        <div class="mb-2">
                            <img src="<?= url('/uploads/' . e($member['photo'])) ?>" alt=""
                                 width="80" height="80" style="object-fit:cover;border-radius:50%">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
                <a href="<?= url('/members/profile.php?id=' . $_SESSION['member_id']) ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <form method="post" class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="bi bi-key"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" minlength="6">
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control">
                </div>
                <p class="small text-muted mb-0">Leave blank to keep current password.</p>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
