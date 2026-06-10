<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="<?= url('/index.php') ?>">
            <img src="<?= url('/favicon.jpg') ?>" alt="" width="30" height="30" style="object-fit:cover;border-radius:50%">
            Doba Y2K
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= url('/index.php') ?>">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/minutes/index.php') ?>">Minutes</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/levies/index.php') ?>">Levies</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/accounts/index.php') ?>">Accounts</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('/members/index.php') ?>">Members</a></li>
                <?php if (isLoggedIn() && isAdmin()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Admin</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= url('/minutes/create.php') ?>">New Minute</a></li>
                            <li><a class="dropdown-item" href="<?= url('/levies/create.php') ?>">New Levy</a></li>
                            <li><a class="dropdown-item" href="<?= url('/accounts/create.php') ?>">Record Transaction</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('/admin/members.php') ?>">Manage Members</a></li>
                        </ul>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= e($_SESSION['member_name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= url('/members/profile.php?id=' . $_SESSION['member_id']) ?>">My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= url('/members/edit.php') ?>">Edit Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= url('/auth/logout.php') ?>">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/auth/login.php') ?>">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('/auth/register.php') ?>">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
