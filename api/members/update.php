<?php
require_once __DIR__ . '/../config.php';
$authMember = requireAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'message' => 'POST required'], 405);
}

$memberId = $authMember['id'];

$first_name = trim($_POST['first_name'] ?? $authMember['first_name']);
$last_name = trim($_POST['last_name'] ?? $authMember['last_name']);
$email = trim($_POST['email'] ?? $authMember['email']);
$phone = trim($_POST['phone'] ?? $authMember['phone']);
$bio = trim($_POST['bio'] ?? $authMember['bio']);

if ($email === '') {
    jsonResponse(['success' => false, 'message' => 'Email is required.']);
}

$stmt = $pdo->prepare('SELECT id FROM members WHERE email = ? AND id != ?');
$stmt->execute([$email, $memberId]);
if ($stmt->fetch()) {
    jsonResponse(['success' => false, 'message' => 'Email already in use.']);
}

$photo = $authMember['photo'];
if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        jsonResponse(['success' => false, 'message' => 'Photo must be JPG, PNG, GIF, or WebP.']);
    }
    $filename = 'member_' . $memberId . '_' . time() . ".$ext";
    $uploadDir = __DIR__ . '/../../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $filename);
    if ($authMember['photo'] && file_exists($uploadDir . $authMember['photo'])) {
        unlink($uploadDir . $authMember['photo']);
    }
    $photo = $filename;
}

$stmt = $pdo->prepare(
    'UPDATE members SET first_name = ?, last_name = ?, email = ?, phone = ?, bio = ?, photo = ? WHERE id = ?'
);
$stmt->execute([$first_name, $last_name, $email, $phone ?: null, $bio ?: null, $photo, $memberId]);

$stmt = $pdo->prepare(
    'SELECT id, username, first_name, last_name, photo, bio, graduation_year, phone, email, role, status, created_at FROM members WHERE id = ?'
);
$stmt->execute([$memberId]);
$member = $stmt->fetch();

jsonResponse(['success' => true, 'member' => $member]);
