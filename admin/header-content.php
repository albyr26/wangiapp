<?php
// admin/header-content.php
?>
<!-- Header -->
<div class="header">
    <div>
        <h3 class="mb-1">Selamat Datang, <?= htmlspecialchars(
            $_SESSION["admin_name"] ?? "Admin",
        ) ?>!</h3>
        <p class="text-muted mb-0"><?= date("l, d F Y") ?></p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="avatar">
            <?= strtoupper(substr($_SESSION["admin_name"] ?? "A", 0, 1)) ?>
        </div>
        <div>
            <div class="fw-bold"><?= htmlspecialchars(
                $_SESSION["admin_name"] ?? "Admin",
            ) ?></div>
            <small class="text-muted"><?= htmlspecialchars(
                $_SESSION["admin_role"] ?? "Super Admin",
            ) ?></small>
        </div>
    </div>
</div>
