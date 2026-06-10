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
    header('Location: /auth/login.php');
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
        header('Location: /members/edit.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? AND id != ?');
    $stmt->execute([$email, $_SESSION['member_id']]);
    if ($stmt->fetch()) {
        $_SESSION['error'] = 'Email already in use by another member.';
        header('Location: /members/edit.php');
        exit;
    }

    $photo = $member['photo'];
    if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $_SESSION['error'] = 'Photo must be JPG, PNG, GIF, or WebP.';
            header('Location: /members/edit.php');
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
    redirectWith('/members/profile.php?id=' . $_SESSION['member_id'], 'Profile updated.');
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
                            <img src="/uploads/<?= e($member['photo']) ?>" alt=""
                                 width="80" height="80" style="object-fit:cover;border-radius:50%">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="photo" class="form-control" accept="image/*">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
                <a href="/members/profile.php?id=<?= $_SESSION['member_id'] ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
