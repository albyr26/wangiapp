<?php
// admin/sidebar.php
// This file is included in header.php, so no need to start session again
?>
<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-logo">
        <h4>ParfumStore <span class="badge">Admin</span></h4>
        <small class="text-muted">Management System</small>
    </div>

    <div class="sidebar-content">
        <ul class="nav flex-column mt-4">
            <?php $currentPage = basename($_SERVER["PHP_SELF"]); ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "index.php"
                    ? "active"
                    : ""; ?>" href="index.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "orders.php"
                    ? "active"
                    : ""; ?>" href="orders.php">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Pesanan</span>
                    <span class="badge bg-danger ms-auto"><?= $orderCount ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "customers.php"
                    ? "active"
                    : ""; ?>" href="customers.php">
                    <i class="fas fa-users"></i>
                    <span>Pelanggan</span>
                    <span class="badge bg-success ms-auto"><?= $customerCount ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "products.php"
                    ? "active"
                    : ""; ?>" href="products.php">
                    <i class="fas fa-wine-bottle"></i>
                    <span>Produk</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "categories.php"
                    ? "active"
                    : ""; ?>" href="categories.php">
                    <i class="fas fa-tags"></i>
                    <span>Kategori</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "inventory.php"
                    ? "active"
                    : ""; ?>" href="inventory.php">
                    <i class="fas fa-boxes"></i>
                    <span>Inventori</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage == "reports.php"
                    ? "active"
                    : ""; ?>" href="reports.php">
                    <i class="fas fa-chart-bar"></i>
                    <span>Laporan</span>
                </a>
            </li>
            <li class="nav-item mt-5">
                <a class="nav-link text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>
