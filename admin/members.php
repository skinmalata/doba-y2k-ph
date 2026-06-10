<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $memberId = (int)($_POST['member_id'] ?? 0);

    if ($action === 'toggle_admin') {
        $stmt = $pdo->prepare('SELECT role FROM members WHERE id = ?');
        $stmt->execute([$memberId]);
        $m = $stmt->fetch();
        if ($m) {
            $newRole = $m['role'] === 'admin' ? 'member' : 'admin';
            $stmt = $pdo->prepare('UPDATE members SET role = ? WHERE id = ?');
            $stmt->execute([$newRole, $memberId]);
            redirectWith('/admin/members.php', 'Member role updated.');
        }
    }

    if ($action === 'approve') {
        $stmt = $pdo->prepare('UPDATE members SET status = ? WHERE id = ?');
        $stmt->execute(['active', $memberId]);
        redirectWith('/admin/members.php', 'Member approved.');
    }

    if ($action === 'delete') {
        $stmt = $pdo->prepare('DELETE FROM members WHERE id = ? AND role != ?');
        $stmt->execute([$memberId, 'admin']);
        redirectWith('/admin/members.php', 'Member removed.');
    }
}

$stmt = $pdo->query(
    'SELECT id, username, email, first_name, last_name, role, status, graduation_year, created_at
     FROM members ORDER BY status ASC, role DESC, first_name, last_name'
);
$members = $stmt->fetchAll();

$pageTitle = 'Manage Members';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="mb-4"><i class="bi bi-gear-fill"></i> Manage Members</h1>

<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Class</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($members as $m): ?>
            <tr>
                <td>
                    <a href="<?= url('/members/profile.php?id=' . $m['id']) ?>">
                        <?= e($m['first_name'] . ' ' . $m['last_name']) ?>
                    </a>
                </td>
                <td><?= e($m['username']) ?></td>
                <td><?= e($m['email']) ?></td>
                <td><?= $m['graduation_year'] ? e($m['graduation_year']) : '—' ?></td>
                <td>
                    <span class="badge bg-<?= $m['role'] === 'admin' ? 'danger' : 'secondary' ?>">
                        <?= e(ucfirst($m['role'])) ?>
                    </span>
                </td>
                <td>
                    <?php if ($m['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Pending</span>
                    <?php else: ?>
                        <span class="badge bg-success">Active</span>
                    <?php endif; ?>
                </td>
                <td><?= date('j M Y', strtotime($m['created_at'])) ?></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="<?= url('/admin/member-finances.php?id=' . $m['id']) ?>" class="btn btn-sm btn-outline-info"
                           title="Financial History">
                            <i class="bi bi-cash-stack"></i>
                        </a>
                        <?php if ($m['status'] === 'pending'): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="approve">
                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-success" title="Approve member">
                                <i class="bi bi-check-lg"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                        <a href="<?= url('/admin/change-password.php?id=' . $m['id']) ?>" class="btn btn-sm btn-outline-secondary"
                           title="Change Password">
                            <i class="bi bi-key"></i>
                        </a>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="action" value="toggle_admin">
                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-warning"
                                    title="Toggle admin role">
                                <i class="bi bi-shield-<?= $m['role'] === 'admin' ? 'slash' : 'check' ?>"></i>
                            </button>
                        </form>
                        <?php if ($m['role'] !== 'admin'): ?>
                        <form method="post" class="d-inline"
                              onsubmit="return confirm('Remove <?= e(addslashes($m['first_name'] . ' ' . $m['last_name'])) ?>?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="member_id" value="<?= $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete member">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
