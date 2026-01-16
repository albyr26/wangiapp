<?php
// admin/index.php - PERBAIKAN: Gunakan tabel "orders" bukan "order_customer"
require_once "header.php";

// Gunakan tabel "orders" yang sesuai dengan screenshot Anda
$mainTable = "orders";

// Get stats dari tabel "orders"
$orderStats = supabase($mainTable, "GET", null, ["select" => "count"]);
$revenueResult = supabase($mainTable, "GET", null, [
    "select" => "total_price, status, order_date, created_at, customer_name, product_name, customer_phone"
]);

// Inisialisasi variabel
$productCount = 0;
$orderCount = 0;
$totalRevenue = 0;
$pendingOrders = 0;
$deliveredOrders = 0;
$customerCount = 0;
$monthlyRevenue = 0;
$todayRevenue = 0;
$todayCount = 0;

// Handle order count
if (isset($orderStats["data"]) && is_array($orderStats["data"]) && isset($orderStats["data"][0]["count"])) {
    $orderCount = intval($orderStats["data"][0]["count"]);
}

// Proses data orders
if (isset($revenueResult["data"]) && is_array($revenueResult["data"])) {
    $today = date('Y-m-d');
    $currentMonth = date('Y-m');
    $uniqueCustomers = [];
    
    foreach ($revenueResult["data"] as $order) {
        if (is_array($order)) {
            // Total price - perhatikan field name sesuai database
            $totalPrice = 0;
            if (isset($order['total_price'])) {
                $totalPrice = floatval($order['total_price']);
            } elseif (isset($order['total_amount'])) {
                $totalPrice = floatval($order['total_amount']);
            } elseif (isset($order['price'])) {
                $totalPrice = floatval($order['price']);
            }
            
            $totalRevenue += $totalPrice;
            
            // Status orders
            $status = strtolower($order['status'] ?? 'pending');
            if ($status == 'pending') {
                $pendingOrders++;
            } elseif ($status == 'delivered' || $status == 'completed' || $status == 'success') {
                $deliveredOrders++;
            }
            
            // Hitung customer unik
            $customerName = trim($order['customer_name'] ?? '');
            $customerPhone = trim($order['customer_phone'] ?? '');
            $customerKey = $customerName . '_' . $customerPhone;
            
            if (!empty($customerName) && !in_array($customerKey, $uniqueCustomers)) {
                $uniqueCustomers[] = $customerKey;
                $customerCount++;
            }
            
            // Hitung bulan ini dan hari ini
            $orderDate = $order['order_date'] ?? $order['created_at'] ?? '';
            if (!empty($orderDate)) {
                // Format: 1/10/2026 1:11 atau Y-m-d
                if (strpos($orderDate, date('Y-m')) !== false || 
                    strpos($orderDate, date('m/Y')) !== false ||
                    strpos($orderDate, date('Y')) !== false) {
                    $monthlyRevenue += $totalPrice;
                }
                
                // Cek hari ini
                if (strpos($orderDate, $today) !== false || 
                    strpos($orderDate, date('d/m/Y')) !== false ||
                    strpos($orderDate, date('m/d/Y')) !== false) {
                    $todayRevenue += $totalPrice;
                    $todayCount++;
                }
            }
        }
    }
}

// Get product count
$products = supabase("cust_products", "GET", null, ["select" => "count"]);
if (isset($products["data"][0]["count"])) {
    $productCount = intval($products["data"][0]["count"]);
}

// Get low stock products - dari tabel cust_products
$lowStockProducts = supabase("cust_products", "GET", null, [
    "select" => "id, product_name, product_code, stock_quantity, main_image_url",
    "stock_quantity" => "lte.5",
    "limit" => "5"
]);

$lowStockCount = 0;
if (isset($lowStockProducts["data"]) && is_array($lowStockProducts["data"])) {
    $lowStockCount = count($lowStockProducts["data"]);
}

// Ambil pesanan terbaru dari tabel "orders"
$recentOrders = supabase($mainTable, "GET", null, [
    "select" => "id, customer_name, product_name, total_price, status, order_date, created_at, customer_phone",
    "order" => "created_at.desc, order_date.desc",
    "limit" => "5"
]);
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Success Alert - Data ditemukan -->
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <strong>Data ditemukan!</strong> Mengambil <?= $orderCount ?> pesanan dari tabel: <strong><?= $mainTable ?></strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>

    <!-- Dashboard Welcome -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="welcome-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3>Selamat Datang, Admin! 👋</h3>
                        <p class="text-muted mb-0">
                            <?= $orderCount ?> pesanan siap dikelola
                        </p>
                    </div>
                    <div class="date-info">
                        <div class="date-day"><?= date('l') ?></div>
                        <div class="date-full"><?= date('d F Y') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= number_format($orderCount) ?></div>
                        <div class="stat-label">Total Pesanan</div>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4361ee, #3a0ca3);">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-success">
                        <i class="fas fa-check-circle"></i> <?= number_format($deliveredOrders) ?> selesai
                    </small>
                    <small class="text-warning d-block">
                        <i class="fas fa-clock"></i> <?= number_format($pendingOrders) ?> pending
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value">Rp <?= number_format($totalRevenue, 0, ",", ".") ?></div>
                        <div class="stat-label">Total Pendapatan</div>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #4cc9f0, #4895ef);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-success">
                        <i class="fas fa-calendar"></i> Rp <?= number_format($monthlyRevenue, 0, ",", ".") ?> bulan ini
                    </small>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= number_format($productCount) ?></div>
                        <div class="stat-label">Total Produk</div>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f72585, #b5179e);">
                        <i class="fas fa-wine-bottle"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <?php if ($lowStockCount > 0): ?>
                    <small class="text-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?= $lowStockCount ?> produk stok rendah
                    </small>
                    <?php else: ?>
                    <small class="text-success">
                        <i class="fas fa-check-circle"></i> Stok aman
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= number_format($customerCount) ?></div>
                        <div class="stat-label">Total Pelanggan</div>
                    </div>
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ff9e00, #ff9100);">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <small class="text-info">
                        <i class="fas fa-shopping-cart"></i> Rata-rata Rp <?= $orderCount > 0 ? number_format($totalRevenue / $orderCount, 0, ",", ".") : 0 ?> /order
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row mt-4">
        <div class="col-lg-8 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Pesanan Terbaru</h5>
                        <a href="orders.php" class="btn btn-sm btn-outline-primary">
                            Lihat Semua <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php 
                    if (isset($recentOrders["data"]) && is_array($recentOrders["data"]) && count($recentOrders["data"]) > 0): 
                        $ordersToShow = array_slice($recentOrders["data"], 0, 5);
                    ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ordersToShow as $order): 
                                    if (!is_array($order)) continue;
                                    
                                    // Data dari tabel orders
                                    $orderId = substr($order['id'] ?? 'N/A', 0, 8);
                                    $customerName = htmlspecialchars($order['customer_name'] ?? 'Customer');
                                    $productName = htmlspecialchars($order['product_name'] ?? 'Product');
                                    $totalPrice = floatval($order['total_price'] ?? 0);
                                    $status = strtolower($order['status'] ?? 'pending');
                                    $phone = $order['customer_phone'] ?? '';
                                    
                                    // Status colors
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'shipped' => 'secondary',
                                        'delivered' => 'success',
                                        'completed' => 'success',
                                        'success' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$status] ?? 'secondary';
                                    $statusText = ucfirst($status);
                                ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">#<?= $orderId ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= substr($customerName, 0, 12) ?></div>
                                        <?php if ($phone): ?>
                                        <small class="text-muted"><?= substr($phone, 0, 10) ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= substr($productName, 0, 15) ?>...</td>
                                    <td>
                                        <strong class="text-success">Rp <?= number_format($totalPrice, 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= $statusText ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="fas fa-shopping-cart fa-4x opacity-25"></i>
                        </div>
                        <h5 class="text-muted">Tidak ada pesanan</h5>
                        <p class="text-muted">Data order ditemukan di tabel <?= $mainTable ?></p>
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-sync"></i> Refresh Data
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <!-- Stok Menipis -->
            <div class="card card-custom">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Stok Menipis</h5>
                        <a href="inventory.php" class="btn btn-sm btn-outline-warning">
                            Kelola <i class="fas fa-box-open ms-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($lowStockCount > 0 && isset($lowStockProducts["data"])): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($lowStockProducts["data"] as $product): 
                            if (!is_array($product)) continue;
                            $stock = intval($product['stock_quantity'] ?? 0);
                            $stockClass = $stock <= 2 ? 'danger' : 'warning';
                        ?>
                        <div class="list-group-item border-0 px-0 py-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="fw-bold"><?= htmlspecialchars(substr($product['product_name'] ?? '', 0, 15)) ?></div>
                                    <small class="text-muted">Stok: <?= $stock ?></small>
                                </div>
                                <span class="badge bg-<?= $stockClass ?>"><?= $stock ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h6 class="text-success">Stok Aman</h6>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Hari Ini -->
            <div class="card card-custom mt-4">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Hari Ini (<?= date('d/m') ?>)</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-end">
                            <div class="display-5 fw-bold text-primary"><?= $todayCount ?></div>
                            <small class="text-muted d-block">Pesanan</small>
                        </div>
                        <div class="col-6">
                            <div class="display-5 fw-bold text-success">Rp <?= number_format($todayRevenue, 0, ',', '.') ?></div>
                            <small class="text-muted d-block">Pendapatan</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="card card-custom mt-4">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="orders.php?status=pending" class="btn btn-warning">
                            <i class="fas fa-check-circle me-2"></i> Konfirmasi Pesanan
                        </a>
                        <a href="add_product.php" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i> Tambah Produk
                        </a>
                        <a href="inventory.php" class="btn btn-info">
                            <i class="fas fa-boxes me-2"></i> Kelola Inventori
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<style>
.welcome-card {
    background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.date-info {
    text-align: right;
}

.date-day {
    font-size: 1.5rem;
    font-weight: bold;
}

.date-full {
    opacity: 0.9;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.card-custom {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
}

.card-header-custom {
    background: white;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
}

.table-custom th {
    border-bottom: 2px solid #dee2e6;
    font-weight: 600;
    color: #495057;
}

.table-custom td {
    vertical-align: middle;
}

.badge {
    padding: 5px 10px;
    border-radius: 20px;
    font-weight: 500;
}

.alert {
    border-radius: 10px;
    border: none;
}
</style>