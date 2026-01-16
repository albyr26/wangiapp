<?php
// admin/customers.php - Manajemen Data Pelanggan
require_once "header.php";

// **AMBIL SEMUA DATA ORDERS**
$all_orders = supabase('orders', 'GET', null, [
    'select' => 'customer_name, customer_phone, customer_address, total_price, order_date, status',
    'order' => 'order_date.desc',
    'limit' => 1000  // Ambil cukup banyak data
]);

// **PROSES DATA PELANGGAN SECARA MANUAL**
function processCustomers($orders_data) {
    $customers = [];
    
    if (!isset($orders_data['data']) || !is_array($orders_data['data'])) {
        return $customers;
    }
    
    foreach ($orders_data['data'] as $order) {
        if (!is_array($order)) continue;
        
        $name = trim($order['customer_name'] ?? '');
        $phone = trim($order['customer_phone'] ?? '');
        
        // Skip jika tidak ada nama atau telepon
        if (empty($name) || empty($phone)) {
            continue;
        }
        
        // Buat key unik dari kombinasi nama dan telepon
        $key = md5($name . '_' . $phone);
        
        if (!isset($customers[$key])) {
            $customers[$key] = [
                'name' => $name,
                'phone' => $phone,
                'address' => $order['customer_address'] ?? '-',
                'order_count' => 0,
                'total_spent' => 0,
                'last_order' => '',
                'orders' => []  // Simpan semua order untuk referensi
            ];
        }
        
        // Update statistik
        $customers[$key]['order_count']++;
        $customers[$key]['total_spent'] += floatval($order['total_price'] ?? 0);
        
        // Simpan detail order
        $customers[$key]['orders'][] = [
            'date' => $order['order_date'] ?? '',
            'total' => floatval($order['total_price'] ?? 0),
            'status' => $order['status'] ?? 'pending'
        ];
        
        // Update last order jika lebih baru
        $order_date = $order['order_date'] ?? '';
        if (!empty($order_date)) {
            // Parse date yang bermacam format
            $parsed_date = parseDate($order_date);
            
            if (empty($customers[$key]['last_order']) || 
                $parsed_date > strtotime($customers[$key]['last_order'])) {
                $customers[$key]['last_order'] = date('Y-m-d H:i:s', $parsed_date);
            }
        }
    }
    
    // Convert ke indexed array dan sort
    $result = array_values($customers);
    
    // Hitung rata-rata dan format data
    foreach ($result as &$customer) {
        $customer['avg_order_value'] = $customer['order_count'] > 0 
            ? $customer['total_spent'] / $customer['order_count'] 
            : 0;
        
        // Sort orders by date desc
        usort($customer['orders'], function($a, $b) {
            return strtotime($b['date']) <=> strtotime($a['date']);
        });
    }
    
    // Sort customers by total spent desc
    usort($result, function($a, $b) {
        return $b['total_spent'] <=> $a['total_spent'];
    });
    
    return $result;
}

// Helper function untuk parse berbagai format date
function parseDate($date_string) {
    if (empty($date_string)) return 0;
    
    // Coba format "d/m/Y H:i"
    if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{1,2})/', $date_string, $matches)) {
        return strtotime($matches[3] . '-' . $matches[2] . '-' . $matches[1] . ' ' . $matches[4] . ':' . $matches[5]);
    }
    
    // Coba format "Y-m-d H:i:s"
    if (preg_match('/\d{4}-\d{2}-\d{2}/', $date_string)) {
        return strtotime($date_string);
    }
    
    // Default
    return strtotime($date_string);
}

// Proses data pelanggan
$customers = processCustomers($all_orders);

// Hitung statistik
$stats = [
    'total_customers' => count($customers),
    'repeat_customers' => 0,
    'new_customers_today' => 0,
    'total_revenue' => 0
];

foreach ($customers as $customer) {
    $stats['total_revenue'] += $customer['total_spent'];
    
    if ($customer['order_count'] > 1) {
        $stats['repeat_customers']++;
    }
    
    // Cek pelanggan baru hari ini
    if (!empty($customer['last_order']) && 
        strpos($customer['last_order'], date('Y-m-d')) === 0) {
        $stats['new_customers_today']++;
    }
}

// **DEBUG: Lihat data yang diproses**
// echo '<pre>';
// echo 'Total customers found: ' . count($customers) . "\n";
// echo 'Customers data: ';
// print_r($customers);
// echo '</pre>';

// Handle filter/search
$search = $_GET['search'] ?? '';
if (!empty($search)) {
    $customers = array_filter($customers, function($customer) use ($search) {
        return stripos($customer['name'], $search) !== false || 
               stripos($customer['phone'], $search) !== false ||
               stripos($customer['address'], $search) !== false;
    });
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
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <h2><i class="fas fa-users me-2"></i>Manajemen Pelanggan</h2>
                <p class="text-muted">
                    <?= $stats['total_customers'] ?> pelanggan ditemukan dari <?= count($all_orders['data'] ?? []) ?> orders
                </p>
            </div>
        </div>
    </div>
    
    <!-- Customer Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= number_format($stats['total_customers']) ?></div>
                        <div class="stat-label">Total Pelanggan</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-plus-circle me-1"></i>
                        <?= $stats['new_customers_today'] ?> baru hari ini
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= number_format($stats['repeat_customers']) ?></div>
                        <div class="stat-label">Pelanggan Setia</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-percentage me-1"></i>
                        <?= $stats['total_customers'] > 0 ? round(($stats['repeat_customers']/$stats['total_customers'])*100) : 0 ?>% dari total
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9e00, #ff9100); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value">Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></div>
                        <div class="stat-label">Total Belanja</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-chart-line me-1"></i>
                        Semua pelanggan
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #f72585, #b5179e); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= $stats['total_customers'] > 0 ? number_format($stats['total_revenue']/$stats['total_customers'], 0, ',', '.') : 0 ?></div>
                        <div class="stat-label">Rata-rata/Pelanggan</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-calculator"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small>
                        <i class="fas fa-shopping-cart me-1"></i>
                        Nilai per pelanggan
                    </small>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Cari nama pelanggan, telepon, atau alamat..." 
                                       value="<?= htmlspecialchars($search) ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" name="sort" onchange="this.form.submit()">
                                <option value="spent" <?= ($_GET['sort'] ?? '') == 'spent' ? 'selected' : '' ?>>Belanja Tertinggi</option>
                                <option value="name" <?= ($_GET['sort'] ?? '') == 'name' ? 'selected' : '' ?>>Nama A-Z</option>
                                <option value="recent" <?= ($_GET['sort'] ?? '') == 'recent' ? 'selected' : '' ?>>Terbaru</option>
                                <option value="orders" <?= ($_GET['sort'] ?? '') == 'orders' ? 'selected' : '' ?>>Pesanan Terbanyak</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <div class="d-grid gap-2 d-md-flex">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-1"></i> Filter
                                </button>
                                <?php if (!empty($search)): ?>
                                <a href="customers.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Reset
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Customers List -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>Daftar Pelanggan</h5>
                        <span class="badge bg-primary"><?= count($customers) ?> Pelanggan</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($customers)): 
                        // Apply sorting
                        $sort = $_GET['sort'] ?? 'spent';
                        switch ($sort) {
                            case 'name':
                                usort($customers, function($a, $b) {
                                    return strcmp($a['name'], $b['name']);
                                });
                                break;
                            case 'recent':
                                usort($customers, function($a, $b) {
                                    return strtotime($b['last_order']) <=> strtotime($a['last_order']);
                                });
                                break;
                            case 'orders':
                                usort($customers, function($a, $b) {
                                    return $b['order_count'] <=> $a['order_count'];
                                });
                                break;
                            default: // spent (default)
                                usort($customers, function($a, $b) {
                                    return $b['total_spent'] <=> $a['total_spent'];
                                });
                        }
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Pelanggan</th>
                                    <th>Kontak</th>
                                    <th>Total Pesanan</th>
                                    <th>Total Belanja</th>
                                    <th>Rata-rata</th>
                                    <th>Terakhir Order</th>
                                    <th>Tindakan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customers as $index => $customer): 
                                    $is_vip = $customer['order_count'] >= 5 || $customer['total_spent'] >= 500000;
                                    $last_order_formatted = !empty($customer['last_order']) 
                                        ? date('d/m/Y H:i', strtotime($customer['last_order'])) 
                                        : '-';
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold"><?= $index + 1 ?></div>
                                        <?php if ($is_vip): ?>
                                        <span class="badge bg-warning badge-sm">VIP</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="customer-avatar me-3">
                                                <div class="avatar-circle" style="background: <?= $is_vip ? '#ff9e00' : '#4361ee' ?>">
                                                    <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($customer['name']) ?></div>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars(substr($customer['address'], 0, 30)) ?>
                                                    <?= strlen($customer['address']) > 30 ? '...' : '' ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($customer['phone']) ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?= htmlspecialchars(substr($customer['address'], 0, 20)) ?>
                                            <?= strlen($customer['address']) > 20 ? '...' : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-bold h5 mb-0"><?= $customer['order_count'] ?></div>
                                        <small class="text-muted">pesanan</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success">Rp <?= number_format($customer['total_spent'], 0, ',', '.') ?></div>
                                        <small class="text-muted">total</small>
                                    </td>
                                    <td>
                                        <div class="fw-bold">Rp <?= number_format($customer['avg_order_value'], 0, ',', '.') ?></div>
                                        <small class="text-muted">per order</small>
                                    </td>
                                    <td>
                                        <?php if ($last_order_formatted != '-'): ?>
                                        <div class="fw-bold"><?= $last_order_formatted ?></div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('H:i', strtotime($customer['last_order'])) ?>
                                        </small>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#customerDetailModal<?= $index ?>"
                                                title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#customerOrderModal<?= $index ?>"
                                                title="Riwayat Order">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="createNewOrder('<?= addslashes($customer['name']) ?>', '<?= addslashes($customer['phone']) ?>', '<?= addslashes($customer['address']) ?>')"
                                                title="Buat Order Baru">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                        
                                        <!-- Customer Detail Modal -->
                                        <div class="modal fade" id="customerDetailModal<?= $index ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detail Pelanggan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="text-center mb-4">
                                                            <div class="avatar-circle-lg mx-auto mb-3" style="background: <?= $is_vip ? '#ff9e00' : '#4361ee' ?>">
                                                                <?= strtoupper(substr($customer['name'], 0, 1)) ?>
                                                            </div>
                                                            <h5><?= htmlspecialchars($customer['name']) ?></h5>
                                                            <?php if ($is_vip): ?>
                                                            <span class="badge bg-warning mb-2">
                                                                <i class="fas fa-crown me-1"></i> Pelanggan VIP
                                                            </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label text-muted">Telepon</label>
                                                                <div class="fw-bold"><?= htmlspecialchars($customer['phone']) ?></div>
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label text-muted">Total Pesanan</label>
                                                                <div class="fw-bold h4"><?= $customer['order_count'] ?></div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label text-muted">Alamat</label>
                                                            <div class="fw-bold"><?= htmlspecialchars($customer['address']) ?></div>
                                                        </div>
                                                        
                                                        <div class="row">
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label text-muted">Total Belanja</label>
                                                                <div class="fw-bold text-success h5">
                                                                    Rp <?= number_format($customer['total_spent'], 0, ',', '.') ?>
                                                                </div>
                                                            </div>
                                                            <div class="col-6 mb-3">
                                                                <label class="form-label text-muted">Rata-rata/Order</label>
                                                                <div class="fw-bold h5">
                                                                    Rp <?= number_format($customer['avg_order_value'], 0, ',', '.') ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label text-muted">Terakhir Order</label>
                                                            <div class="fw-bold">
                                                                <?php if (!empty($customer['last_order'])): ?>
                                                                <?= date('d F Y H:i', strtotime($customer['last_order'])) ?>
                                                                <?php else: ?>
                                                                <span class="text-muted">Belum pernah order</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Customer Orders Modal -->
                                        <div class="modal fade" id="customerOrderModal<?= $index ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            Riwayat Order: <?= htmlspecialchars($customer['name']) ?>
                                                            <small class="text-muted">(<?= $customer['order_count'] ?> orders)</small>
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if (!empty($customer['orders'])): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>No</th>
                                                                        <th>Tanggal</th>
                                                                        <th>Total</th>
                                                                        <th>Status</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($customer['orders'] as $order_index => $order): 
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
                                                                        $statusColor = $statusColors[$order['status']] ?? 'secondary';
                                                                        $order_date = !empty($order['date']) 
                                                                            ? date('d/m/Y H:i', parseDate($order['date'])) 
                                                                            : '-';
                                                                    ?>
                                                                    <tr>
                                                                        <td><strong>#<?= $order_index + 1 ?></strong></td>
                                                                        <td><small><?= $order_date ?></small></td>
                                                                        <td class="text-success">
                                                                            <strong>Rp <?= number_format($order['total'], 0, ',', '.') ?></strong>
                                                                        </td>
                                                                        <td>
                                                                            <span class="badge bg-<?= $statusColor ?>">
                                                                                <?= ucfirst($order['status']) ?>
                                                                            </span>
                                                                        </td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="text-center py-4">
                                                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                                            <p class="text-muted">Belum ada riwayat order</p>
                                                        </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <div class="text-muted mb-3">
                            <i class="fas fa-users fa-4x opacity-25"></i>
                        </div>
                        <h5 class="text-muted">Tidak ada data pelanggan</h5>
                        <p class="text-muted">
                            <?php if (!empty($search)): ?>
                            Hasil pencarian "<?= htmlspecialchars($search) ?>" tidak ditemukan
                            <?php else: ?>
                            Data pelanggan akan muncul setelah ada transaksi
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search)): ?>
                        <a href="customers.php" class="btn btn-primary">
                            <i class="fas fa-undo me-1"></i> Tampilkan Semua
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>

<style>
.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-card {
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-value {
    font-size: 2rem;
    font-weight: bold;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 5px;
}

.stat-icon {
    font-size: 2.5rem;
    opacity: 0.3;
}

.card-custom {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.card-header-custom {
    background: white;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
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

.avatar-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
}

.avatar-circle-lg {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2rem;
}

.badge-sm {
    font-size: 0.6rem;
    padding: 2px 5px;
}
</style>

<script>
function createNewOrder(customerName, customerPhone, customerAddress) {
    if (confirm('Buat order baru untuk ' + customerName + '?')) {
        // Redirect ke halaman orders dengan parameter customer
        window.location.href = 'orders.php?new_order=1&customer_name=' + 
                              encodeURIComponent(customerName) + 
                              '&customer_phone=' + encodeURIComponent(customerPhone) + 
                              '&customer_address=' + encodeURIComponent(customerAddress);
    }
}

// Auto-close alerts
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(alert => {
        new bootstrap.Alert(alert).close();
    });
}, 5000);
</script>