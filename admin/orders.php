<?php
// admin/orders.php - Perbaikan untuk update status
require_once "header.php";

// Inisialisasi session message
if (!isset($_SESSION['success'])) $_SESSION['success'] = '';
if (!isset($_SESSION['error'])) $_SESSION['error'] = '';

// Tangani aksi update status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id = $_POST['order_id'] ?? '';
    $newStatus = $_POST['status'] ?? '';
    
    if (!empty($id) && !empty($newStatus)) {
        try {
            $result = supabase('orders', 'PATCH', [
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ], [
                'id' => 'eq.' . $id
            ]);
            
            // Debug log
            error_log("Update Status Result: " . print_r($result, true));
            
            if (isset($result['error'])) {
                $_SESSION['error'] = 'Gagal update status: ' . ($result['error']['message'] ?? 'Unknown error');
            } else {
                $_SESSION['success'] = 'Status pesanan berhasil diupdate!';
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Error: ' . $e->getMessage();
        }
        
        header('Location: orders.php');
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = trim($_GET['id']);
    
    if (!empty($id)) {
        if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
            echo '<script>
            if(confirm("Apakah Anda yakin ingin menghapus pesanan ini?\\n\\nTindakan ini akan menghapus data pesanan secara permanen dan tidak dapat dikembalikan.")) {
                window.location.href = "orders.php?action=delete&id=' . urlencode($id) . '&confirm=yes";
            } else {
                window.location.href = "orders.php";
            }
            </script>';
            exit;
        }
        
        $result = supabase('orders', 'DELETE', null, [
            'id' => 'eq.' . $id
        ]);
        
        // PERBAIKAN: Gunakan pengecekan yang sesuai
        if ($result['success']) {
            $_SESSION['success'] = 'Pesanan berhasil dihapus!';
        } else {
            $_SESSION['error'] = 'Gagal menghapus pesanan: ' . 
                                 (!empty($result['error']) ? $result['error'] : 'Terjadi kesalahan tidak diketahui');
        }
        
        header('Location: orders.php');
        exit;
    }
}

// Filter parameter
$statusFilter = $_GET['status'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

// Build query parameters
$params = [
    "select" => "id, customer_name, customer_phone, customer_address, product_name, total_price, status, order_date, created_at, delivery_method, shipping_type, shipping_cost",
    "order" => "created_at.desc, order_date.desc"
];

// Apply filters
if (!empty($statusFilter)) {
    $params['status'] = 'eq.' . $statusFilter;
}

if (!empty($searchQuery)) {
    // Gunakan or query dengan format yang benar
    $params['or'] = '(customer_name.ilike.%' . $searchQuery . '%,product_name.ilike.%' . $searchQuery . '%,customer_phone.ilike.%' . $searchQuery . '%)';
}

if (!empty($dateFrom)) {
    $params['order_date'] = 'gte.' . $dateFrom;
}

if (!empty($dateTo)) {
    $params['order_date'] = 'lte.' . $dateTo;
}

// Get orders
$orders = supabase('orders', 'GET', null, $params);

// **PERBAIKAN: Inisialisasi dengan benar**
$statusCounts = [
    'all' => 0,
    'pending' => 0,
    'confirmed' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

// **PERBAIKAN: Cek struktur data dengan benar**
if (isset($orders['data']) && is_array($orders['data'])) {
    $statusCounts['all'] = count($orders['data']);
    foreach ($orders['data'] as $order) {
        // Pastikan $order adalah array
        if (is_array($order) && isset($order['status'])) {
            $status = strtolower($order['status']);
            
            // **PERBAIKAN: Gunakan array_key_exists untuk cek key**
            if (array_key_exists($status, $statusCounts)) {
                $statusCounts[$status]++;
            }
        }
    }
}

// **PERBAIKAN: Jika $orders bukan array, set sebagai array kosong**
if (!isset($orders['data']) || !is_array($orders['data'])) {
    $orders['data'] = [];
}
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>

    <!-- Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['success']);
    endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php 
    unset($_SESSION['error']);
    endif; ?>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <h2><i class="fas fa-shopping-cart me-2"></i>Manajemen Pesanan</h2>
                <p class="text-muted">Kelola semua pesanan dari pelanggan</p>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-body">
                    <form method="GET" action="orders.php" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending" <?= $statusFilter == 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="confirmed" <?= $statusFilter == 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                <option value="processing" <?= $statusFilter == 'processing' ? 'selected' : '' ?>>Diproses</option>
                                <option value="shipped" <?= $statusFilter == 'shipped' ? 'selected' : '' ?>>Dikirim</option>
                                <option value="delivered" <?= $statusFilter == 'delivered' ? 'selected' : '' ?>>Selesai</option>
                                <option value="cancelled" <?= $statusFilter == 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cari</label>
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Nama/Produk/Telepon" value="<?= htmlspecialchars($searchQuery) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="d-flex justify-content-between">
                                <a href="orders.php" class="btn btn-outline-secondary">Reset Filter</a>
                                <button type="submit" class="btn btn-primary">Terapkan Filter</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="status-tabs">
                <a href="orders.php" class="status-tab <?= empty($statusFilter) ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['all'] ?></div>
                    <div class="status-label">Semua</div>
                </a>
                <a href="orders.php?status=pending" class="status-tab <?= $statusFilter == 'pending' ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['pending'] ?></div>
                    <div class="status-label">Pending</div>
                </a>
                <a href="orders.php?status=confirmed" class="status-tab <?= $statusFilter == 'confirmed' ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['confirmed'] ?></div>
                    <div class="status-label">Dikonfirmasi</div>
                </a>
                <a href="orders.php?status=processing" class="status-tab <?= $statusFilter == 'processing' ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['processing'] ?></div>
                    <div class="status-label">Diproses</div>
                </a>
                <a href="orders.php?status=shipped" class="status-tab <?= $statusFilter == 'shipped' ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['shipped'] ?></div>
                    <div class="status-label">Dikirim</div>
                </a>
                <a href="orders.php?status=delivered" class="status-tab <?= $statusFilter == 'delivered' ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['delivered'] ?></div>
                    <div class="status-label">Selesai</div>
                </a>
                <a href="orders.php?status=cancelled" class="status-tab <?= $statusFilter == 'cancelled' ? 'active' : '' ?>">
                    <div class="status-count"><?= $statusCounts['cancelled'] ?></div>
                    <div class="status-label">Dibatalkan</div>
                </a>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Daftar Pesanan</h5>
                        <div>
                            <span class="text-muted me-3">Total: <?= $statusCounts['all'] ?> pesanan</span>
                            <!-- **PERBAIKAN: Tambahkan Export Button -->
                            <button class="btn btn-sm btn-success" onclick="exportOrders()">
                                <i class="fas fa-download me-1"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (count($orders['data']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders['data'] as $order): 
                                    // VALIDASI: Pastikan $order adalah array
                                    if (!is_array($order)) {
                                        continue;
                                    }
                                    
                                    $orderId = $order['id'] ?? '';
                                    $shortId = !empty($orderId) ? substr($orderId, 0, 8) : 'N/A';
                                    $customerName = htmlspecialchars($order['customer_name'] ?? 'Tidak ada nama');
                                    $customerPhone = htmlspecialchars($order['customer_phone'] ?? '');
                                    $productName = htmlspecialchars($order['product_name'] ?? 'Tidak ada produk');
                                    $totalPrice = floatval($order['total_price'] ?? 0);
                                    $status = strtolower($order['status'] ?? 'pending');
                                    $orderDate = $order['order_date'] ?? $order['created_at'] ?? '';
                                    $deliveryMethod = $order['delivery_method'] ?? '';
                                    $shippingType = $order['shipping_type'] ?? '';
                                    $shippingCost = floatval($order['shipping_cost'] ?? 0);
                                    
                                    // Format tanggal
                                    $formattedDate = 'Tidak ada tanggal';
                                    if (!empty($orderDate)) {
                                        try {
                                            $timestamp = strtotime($orderDate);
                                            if ($timestamp !== false) {
                                                $formattedDate = date('d/m/Y H:i', $timestamp);
                                            } else {
                                                $formattedDate = substr($orderDate, 0, 16);
                                            }
                                        } catch (Exception $e) {
                                            $formattedDate = substr($orderDate, 0, 16);
                                        }
                                    }
                                    
                                    // Status configuration
                                    $statusConfig = [
                                        'pending' => ['label' => 'Pending', 'color' => 'warning', 'icon' => 'clock'],
                                        'confirmed' => ['label' => 'Dikonfirmasi', 'color' => 'info', 'icon' => 'check-circle'],
                                        'processing' => ['label' => 'Diproses', 'color' => 'primary', 'icon' => 'cogs'],
                                        'shipped' => ['label' => 'Dikirim', 'color' => 'secondary', 'icon' => 'shipping-fast'],
                                        'delivered' => ['label' => 'Selesai', 'color' => 'success', 'icon' => 'check-double'],
                                        'cancelled' => ['label' => 'Dibatalkan', 'color' => 'danger', 'icon' => 'times-circle']
                                    ];
                                    
                                    $statusInfo = $statusConfig[$status] ?? $statusConfig['pending'];
                                ?>
                                <tr>
                                    <td>
                                        <small class="text-muted">#<?= $shortId ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= substr($customerName, 0, 15) ?></div>
                                        <?php if (!empty($customerPhone)): ?>
                                        <small class="text-muted d-block"><?= substr($customerPhone, 0, 12) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($deliveryMethod)): ?>
                                        <small class="badge bg-light text-dark mt-1"><?= ucfirst($deliveryMethod) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= substr($productName, 0, 20) ?></div>
                                        <?php if (!empty($shippingType)): ?>
                                        <small class="text-muted"><?= ucfirst($shippingType) ?></small>
                                        <?php endif; ?>
                                        <?php if ($shippingCost > 0): ?>
                                        <small class="text-muted d-block">Ongkir: Rp <?= number_format($shippingCost, 0, ',', '.') ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-success">Rp <?= number_format($totalPrice, 0, ',', '.') ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusInfo['color'] ?>">
                                            <i class="fas fa-<?= $statusInfo['icon'] ?> me-1"></i>
                                            <?= $statusInfo['label'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= $formattedDate ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <!-- Detail Button -->
                                            <button type="button" class="btn btn-outline-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?= $shortId ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <!-- Update Status Button -->
                                            <button type="button" class="btn btn-outline-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#statusModal<?= $shortId ?>">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <!-- **PERBAIKAN: Delete Button dengan modal konfirmasi -->
                                            <button type="button" class="btn btn-outline-danger"
                                                    onclick="confirmDeleteOrder('<?= urlencode($orderId) ?>', '<?= addslashes($customerName) ?>', '<?= addslashes($productName) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Detail Modal -->
                                        <div class="modal fade" id="detailModal<?= $shortId ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detail Pesanan #<?= $shortId ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6><i class="fas fa-user me-2"></i>Informasi Pelanggan</h6>
                                                                <table class="table table-sm table-borderless">
                                                                    <tr>
                                                                        <td width="120"><strong>Nama:</strong></td>
                                                                        <td><?= $customerName ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Telepon:</strong></td>
                                                                        <td><?= $customerPhone ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Alamat:</strong></td>
                                                                        <td><?= htmlspecialchars($order['customer_address'] ?? 'Tidak ada alamat') ?></td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6><i class="fas fa-shopping-cart me-2"></i>Informasi Pesanan</h6>
                                                                <table class="table table-sm table-borderless">
                                                                    <tr>
                                                                        <td width="120"><strong>Produk:</strong></td>
                                                                        <td><?= $productName ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Total:</strong></td>
                                                                        <td class="text-success">Rp <?= number_format($totalPrice, 0, ',', '.') ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Status:</strong></td>
                                                                        <td>
                                                                            <span class="badge bg-<?= $statusInfo['color'] ?>">
                                                                                <i class="fas fa-<?= $statusInfo['icon'] ?> me-1"></i>
                                                                                <?= $statusInfo['label'] ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Tanggal:</strong></td>
                                                                        <td><?= $formattedDate ?></td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td><strong>Pengiriman:</strong></td>
                                                                        <td>
                                                                            <?= ucfirst($deliveryMethod) ?> - 
                                                                            <?= ucfirst($shippingType) ?>
                                                                            <?php if ($shippingCost > 0): ?>
                                                                            (Rp <?= number_format($shippingCost, 0, ',', '.') ?>)
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                </table>
                                                            </div>
                                                        </div>
                                                        <!-- **PERBAIKAN: Tambahkan tombol hapus di detail modal -->
                                                        <div class="alert alert-danger mt-3">
                                                            <div class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                                                    <strong>Hapus Pesanan</strong><br>
                                                                    <small>Tindakan ini tidak dapat dibatalkan. Hapus hanya jika benar-benar diperlukan.</small>
                                                                </div>
                                                                <button type="button" class="btn btn-danger btn-sm"
                                                                        onclick="confirmDeleteOrder('<?= urlencode($orderId) ?>', '<?= addslashes($customerName) ?>', '<?= addslashes($productName) ?>', true)">
                                                                    <i class="fas fa-trash me-1"></i> Hapus Pesanan
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?= $shortId ?>" data-bs-dismiss="modal">
                                                            <i class="fas fa-edit me-1"></i> Update Status
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal<?= $shortId ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST" action="orders.php">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderId) ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Status Pesanan #<?= $shortId ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label class="form-label">Status Saat Ini</label>
                                                                <div class="alert alert-<?= $statusInfo['color'] ?> mb-0">
                                                                    <i class="fas fa-<?= $statusInfo['icon'] ?> me-2"></i>
                                                                    <strong><?= $statusInfo['label'] ?></strong>
                                                                </div>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Ubah Status Ke</label>
                                                                <select name="status" class="form-select" required>
                                                                    <option value="">Pilih status baru...</option>
                                                                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                                                                    <option value="confirmed" <?= $status == 'confirmed' ? 'selected' : '' ?>>Dikonfirmasi</option>
                                                                    <option value="processing" <?= $status == 'processing' ? 'selected' : '' ?>>Diproses</option>
                                                                    <option value="shipped" <?= $status == 'shipped' ? 'selected' : '' ?>>Dikirim</option>
                                                                    <option value="delivered" <?= $status == 'delivered' ? 'selected' : '' ?>>Selesai</option>
                                                                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Dibatalkan</option>
                                                                </select>
                                                                <small class="form-text text-muted">Pilih status baru untuk pesanan ini.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">
                                                                <i class="fas fa-save me-1"></i> Update Status
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Simple Pagination -->
                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <div class="text-muted">
                            Menampilkan <?= count($orders['data']) ?> pesanan
                        </div>
                        <div>
                            <a href="orders.php" class="btn btn-outline-secondary">
                                <i class="fas fa-redo me-1"></i> Refresh
                            </a>
                        </div>
                    </div>
                    
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="fas fa-shopping-cart fa-4x opacity-25"></i>
                        </div>
                        <h5 class="text-muted">Tidak ada pesanan</h5>
                        <p class="text-muted">
                            <?php if (!empty($statusFilter)): ?>
                            Tidak ditemukan pesanan dengan status "<?= $statusFilter ?>"
                            <?php elseif (!empty($searchQuery)): ?>
                            Tidak ditemukan pesanan dengan kata kunci "<?= htmlspecialchars($searchQuery) ?>"
                            <?php else: ?>
                            Belum ada pesanan yang tercatat
                            <?php endif; ?>
                        </p>
                        <a href="orders.php" class="btn btn-primary mt-2">
                            <i class="fas fa-redo me-1"></i> Reset Filter
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<style>
/* CSS tetap sama seperti sebelumnya */
.status-tabs {
    display: flex;
    background: white;
    border-radius: 10px;
    padding: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow-x: auto;
}

.status-tab {
    flex: 1;
    text-align: center;
    padding: 15px;
    text-decoration: none;
    color: #6c757d;
    border-radius: 8px;
    transition: all 0.3s;
    min-width: 120px;
}

.status-tab:hover {
    background: #f8f9fa;
    color: #495057;
}

.status-tab.active {
    background: #4361ee;
    color: white;
}

.status-count {
    font-size: 1.5rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.status-label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.table-custom th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
}

.table-custom td {
    vertical-align: middle;
}

.btn-group-sm .btn {
    padding: 0.25rem 0.5rem;
}

.modal-content {
    border-radius: 10px;
    border: none;
}

.modal-header {
    border-bottom: 1px solid #dee2e6;
    background: #f8f9fa;
    border-radius: 10px 10px 0 0;
}

@media (max-width: 768px) {
    .status-tab {
        min-width: 100px;
        padding: 10px;
    }
    
    .status-count {
        font-size: 1.2rem;
    }
    
    .btn-group {
        flex-wrap: wrap;
        gap: 5px;
    }
    
    .btn-group .btn {
        margin-bottom: 5px;
    }
}
</style>

<script>
// Auto-close alerts
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});

// **PERBAIKAN: Fungsi konfirmasi hapus yang lebih baik**
function confirmDeleteOrder(orderId, customerName, productName, fromModal = false) {
    // Buat pesan konfirmasi
    var message = "Apakah Anda yakin ingin menghapus pesanan ini?\n\n";
    message += "Pelanggan: " + customerName + "\n";
    message += "Produk: " + productName + "\n\n";
    message += "Tindakan ini akan menghapus data pesanan secara permanen dan tidak dapat dikembalikan.\n\n";
    message += "Tekan OK untuk hapus, atau Cancel untuk batal.";
    
    if (confirm(message)) {
        // Jika konfirmasi dari modal, close modal dulu
        if (fromModal) {
            var modal = bootstrap.Modal.getInstance(document.querySelector('.modal.show'));
            if (modal) {
                modal.hide();
            }
            setTimeout(function() {
                window.location.href = "orders.php?action=delete&id=" + orderId + "&confirm=yes";
            }, 500);
        } else {
            window.location.href = "orders.php?action=delete&id=" + orderId + "&confirm=yes";
        }
    }
}

// **PERBAIKAN: Tambahkan fungsi export**
function exportOrders() {
    var statusFilter = "<?= $statusFilter ?>";
    var searchQuery = "<?= $searchQuery ?>";
    var dateFrom = "<?= $dateFrom ?>";
    var dateTo = "<?= $dateTo ?>";
    
    var exportUrl = "export_orders.php?";
    if (statusFilter) exportUrl += "status=" + encodeURIComponent(statusFilter) + "&";
    if (searchQuery) exportUrl += "search=" + encodeURIComponent(searchQuery) + "&";
    if (dateFrom) exportUrl += "date_from=" + encodeURIComponent(dateFrom) + "&";
    if (dateTo) exportUrl += "date_to=" + encodeURIComponent(dateTo);
    
    window.open(exportUrl, '_blank');
}

// Auto-focus pada select saat modal status dibuka
document.addEventListener('shown.bs.modal', function(event) {
    var modal = event.target;
    if (modal.id && modal.id.includes('statusModal')) {
        var select = modal.querySelector('select[name="status"]');
        if (select) {
            select.focus();
        }
    }
});
</script>