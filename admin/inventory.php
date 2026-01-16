<?php
// admin/inventory.php - Manajemen Inventory Terintegrasi
require_once "header.php";

// Tangani update stok
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_stock'])) {
    $product_id = $_POST['product_id'];
    $action = $_POST['action']; // restock, adjust, set
    $quantity = intval($_POST['quantity']);
    $notes = trim($_POST['notes'] ?? '');
    
    // Get current stock
    $current = supabase('cust_products', 'GET', null, [
        'select' => 'id, name, stock',
        'id' => 'eq.' . $product_id
    ]);
    
    if (!isset($current['data'][0])) {
        $_SESSION['error'] = "Produk tidak ditemukan!";
        header('Location: inventory.php');
        exit;
    }
    
    $product = $current['data'][0];
    $current_stock = intval($product['stock'] ?? 0);
    $previous_stock = $current_stock;
    
    // Calculate new stock
    switch ($action) {
        case 'restock':
            $new_stock = $current_stock + $quantity;
            $type = 'restock';
            break;
        case 'adjustment':
            $new_stock = $current_stock + $quantity; // bisa positif/negatif
            $type = 'adjustment';
            break;
        case 'set':
            $new_stock = $quantity;
            $type = 'adjustment';
            break;
        default:
            $_SESSION['error'] = "Aksi tidak valid!";
            header('Location: inventory.php');
            exit;
    }
    
    // Update product stock
    $update_result = supabase('cust_products', 'PATCH', [
        'stock' => $new_stock,
        'updated_at' => date('Y-m-d H:i:s')
    ], [
        'id' => 'eq.' . $product_id
    ]);
    
    if (isset($update_result['error'])) {
        $_SESSION['error'] = "Gagal update stok: " . $update_result['error']['message'];
    } else {
        // Save to stock history
        $history_data = [
            'product_id' => $product_id,
            'type' => $type,
            'quantity' => $action == 'set' ? ($new_stock - $previous_stock) : $quantity,
            'previous_stock' => $previous_stock,
            'new_stock' => $new_stock,
            'notes' => !empty($notes) ? $notes : ($type == 'restock' ? 'Restock produk' : 'Penyesuaian stok'),
            'created_by' => $_SESSION['admin_username'] ?? 'admin',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        supabase('stock_history', 'POST', $history_data);
        
        $_SESSION['success'] = "Stok berhasil diupdate! ($previous_stock → $new_stock)";
    }
    
    header('Location: inventory.php');
    exit;
}

// Get all products with stock info
$products = supabase('cust_products', 'GET', null, [
    'select' => 'id, name, category_id, price, stock, image_url, short_description, created_at',
    'order' => 'stock.asc, name.asc'
]);

// Get stock history untuk laporan
$stock_history = supabase('stock_history', 'GET', null, [
    'select' => 'id, product_id, type, quantity, previous_stock, new_stock, notes, created_by, created_at',
    'order' => 'created_at.desc',
    'limit' => 20
]);

// Hitung statistik
$stats = [
    'total_stock' => 0,
    'total_products' => 0,
    'low_stock' => 0,
    'out_of_stock' => 0,
    'total_value' => 0
];

if (isset($products['data']) && is_array($products['data'])) {
    $stats['total_products'] = count($products['data']);
    
    foreach ($products['data'] as $product) {
        $stock = intval($product['stock'] ?? 0);
        $price = floatval($product['price'] ?? 0);
        
        $stats['total_stock'] += $stock;
        $stats['total_value'] += $stock * $price;
        
        if ($stock <= 0) {
            $stats['out_of_stock']++;
        } elseif ($stock <= 5) {
            $stats['low_stock']++;
        }
    }
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
                <h2><i class="fas fa-boxes me-2"></i>Manajemen Inventori</h2>
                <p class="text-muted">Kelola dan pantau stok produk</p>
            </div>
        </div>
    </div>
    
    <!-- Stock Stats -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= number_format($stats['total_stock']) ?></div>
                        <div class="stat-label">Total Unit Stok</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-box"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value">Rp <?= number_format($stats['total_value'], 0, ',', '.') ?></div>
                        <div class="stat-label">Nilai Inventori</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9e00, #ff9100); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= $stats['low_stock'] ?></div>
                        <div class="stat-label">Stok Rendah (≤5)</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #f72585, #b5179e); color: white;">
                <div class="d-flex justify-content-between">
                    <div>
                        <div class="stat-value"><?= $stats['out_of_stock'] ?></div>
                        <div class="stat-label">Stok Habis</div>
                    </div>
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Inventory Table -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-box me-2"></i>Daftar Stok Produk</h5>
                        <div>
                            <span class="badge bg-primary me-2"><?= $stats['total_products'] ?> Produk</span>
                            <a href="add_product.php" class="btn btn-sm btn-primary">
                                <i class="fas fa-plus me-1"></i> Tambah Produk
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($products['data']) && count($products['data']) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-custom">
                            <thead>
                                <tr>
                                    <th>Produk</th>
                                    <th>Stok Saat Ini</th>
                                    <th>Status</th>
                                    <th>Harga</th>
                                    <th>Nilai Stok</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products['data'] as $product): 
                                    $stock = intval($product['stock'] ?? 0);
                                    $price = floatval($product['price'] ?? 0);
                                    $stock_value = $stock * $price;
                                    
                                    // Tentukan status stok
                                    if ($stock <= 0) {
                                        $status_class = 'danger';
                                        $status_text = 'Habis';
                                        $status_icon = 'times-circle';
                                    } elseif ($stock <= 5) {
                                        $status_class = 'warning';
                                        $status_text = 'Rendah';
                                        $status_icon = 'exclamation-triangle';
                                    } else {
                                        $status_class = 'success';
                                        $status_text = 'Aman';
                                        $status_icon = 'check-circle';
                                    }
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if (!empty($product['image_url'])): ?>
                                            <img src="<?= htmlspecialchars($product['image_url']) ?>" 
                                                 class="rounded me-3" 
                                                 style="width: 50px; height: 50px; object-fit: cover;"
                                                 alt="<?= htmlspecialchars($product['name']) ?>">
                                            <?php else: ?>
                                            <div class="rounded me-3 d-flex align-items-center justify-content-center" 
                                                 style="width: 50px; height: 50px; background: #f8f9fa;">
                                                <i class="fas fa-wine-bottle text-muted"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?= htmlspecialchars($product['name']) ?></div>
                                                <?php if (!empty($product['short_description'])): ?>
                                                <small class="text-muted"><?= substr(htmlspecialchars($product['short_description']), 0, 50) ?>...</small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold h5 mb-0"><?= $stock ?></div>
                                        <small class="text-muted">unit</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $status_class ?>">
                                            <i class="fas fa-<?= $status_icon ?> me-1"></i>
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold">Rp <?= number_format($price, 0, ',', '.') ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-success">Rp <?= number_format($stock_value, 0, ',', '.') ?></div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStockModal<?= substr($product['id'], 0, 8) ?>"
                                                title="Update Stok">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#stockHistoryModal<?= substr($product['id'], 0, 8) ?>"
                                                title="Riwayat Stok">
                                            <i class="fas fa-history"></i>
                                        </button>
                                        
                                        <!-- Update Stock Modal -->
                                        <div class="modal fade" id="updateStockModal<?= substr($product['id'], 0, 8) ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="update_stock" value="1">
                                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                        
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Stok: <?= htmlspecialchars($product['name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="alert alert-info">
                                                                <strong>Stok saat ini:</strong> <?= $stock ?> unit
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Aksi</label>
                                                                <select name="action" class="form-select" required onchange="toggleQuantityLabel(this, <?= $stock ?>)">
                                                                    <option value="restock">Tambah Stok (Restock)</option>
                                                                    <option value="adjustment">Penyesuaian (+/-)</option>
                                                                    <option value="set">Set Stok Baru</option>
                                                                </select>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label" id="quantityLabel">Jumlah Restock</label>
                                                                <input type="number" name="quantity" class="form-control" 
                                                                       min="1" value="1" required>
                                                            </div>
                                                            
                                                            <div class="mb-3">
                                                                <label class="form-label">Catatan (Opsional)</label>
                                                                <textarea name="notes" class="form-control" rows="2" 
                                                                          placeholder="Contoh: Restock dari supplier, koreksi stok, dll."></textarea>
                                                            </div>
                                                            
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-info-circle me-2"></i>
                                                                <small>Perubahan akan dicatat di riwayat stok dan tidak dapat dibatalkan.</small>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                            <button type="submit" class="btn btn-primary">Update Stok</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Stock History Modal -->
                                        <div class="modal fade" id="stockHistoryModal<?= substr($product['id'], 0, 8) ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Riwayat Stok: <?= htmlspecialchars($product['name']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php 
                                                        // Get history for this product
                                                        $product_history = supabase('stock_history', 'GET', null, [
                                                            'select' => '*',
                                                            'product_id' => 'eq.' . $product['id'],
                                                            'order' => 'created_at.desc',
                                                            'limit' => 10
                                                        ]);
                                                        ?>
                                                        
                                                        <?php if (isset($product_history['data']) && count($product_history['data']) > 0): ?>
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Tanggal</th>
                                                                        <th>Tipe</th>
                                                                        <th>Perubahan</th>
                                                                        <th>Stok Lama</th>
                                                                        <th>Stok Baru</th>
                                                                        <th>Catatan</th>
                                                                        <th>Oleh</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php foreach ($product_history['data'] as $history): 
                                                                        $type_labels = [
                                                                            'initial' => ['label' => 'Awal', 'color' => 'info'],
                                                                            'restock' => ['label' => 'Restock', 'color' => 'success'],
                                                                            'adjustment' => ['label' => 'Adjust', 'color' => 'warning'],
                                                                            'sale' => ['label' => 'Penjualan', 'color' => 'primary']
                                                                        ];
                                                                        $type_info = $type_labels[$history['type']] ?? ['label' => $history['type'], 'color' => 'secondary'];
                                                                        $quantity = intval($history['quantity']);
                                                                    ?>
                                                                    <tr>
                                                                        <td><?= date('d/m/Y H:i', strtotime($history['created_at'])) ?></td>
                                                                        <td>
                                                                            <span class="badge bg-<?= $type_info['color'] ?>">
                                                                                <?= $type_info['label'] ?>
                                                                            </span>
                                                                        </td>
                                                                        <td class="<?= $quantity > 0 ? 'text-success' : 'text-danger' ?>">
                                                                            <?= $quantity > 0 ? '+' : '' ?><?= $quantity ?>
                                                                        </td>
                                                                        <td><?= $history['previous_stock'] ?></td>
                                                                        <td><strong><?= $history['new_stock'] ?></strong></td>
                                                                        <td><small><?= htmlspecialchars($history['notes'] ?? '-') ?></small></td>
                                                                        <td><small><?= htmlspecialchars($history['created_by'] ?? 'system') ?></small></td>
                                                                    </tr>
                                                                    <?php endforeach; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <?php else: ?>
                                                        <div class="text-center py-4">
                                                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                                            <p class="text-muted">Belum ada riwayat stok untuk produk ini</p>
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
                            <i class="fas fa-boxes fa-4x opacity-25"></i>
                        </div>
                        <h5 class="text-muted">Belum ada produk</h5>
                        <p class="text-muted">Tambahkan produk terlebih dahulu untuk mengelola inventori</p>
                        <a href="add_product.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Tambah Produk
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Stock History -->
    <?php if (isset($stock_history['data']) && count($stock_history['data']) > 0): ?>
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-history me-2"></i>Riwayat Stok Terbaru</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th>Tipe</th>
                                    <th>Perubahan</th>
                                    <th>Stok Lama → Baru</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stock_history['data'] as $history): 
                                    // Get product name
                                    $product_name = 'Produk';
                                    if (isset($products['data'])) {
                                        foreach ($products['data'] as $p) {
                                            if ($p['id'] == $history['product_id']) {
                                                $product_name = $p['name'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    $type_labels = [
                                        'initial' => ['label' => 'Stok Awal', 'color' => 'info'],
                                        'restock' => ['label' => 'Restock', 'color' => 'success'],
                                        'adjustment' => ['label' => 'Penyesuaian', 'color' => 'warning'],
                                        'sale' => ['label' => 'Penjualan', 'color' => 'primary']
                                    ];
                                    $type_info = $type_labels[$history['type']] ?? ['label' => $history['type'], 'color' => 'secondary'];
                                    $quantity = intval($history['quantity']);
                                ?>
                                <tr>
                                    <td><small><?= date('d/m H:i', strtotime($history['created_at'])) ?></small></td>
                                    <td><small><?= substr(htmlspecialchars($product_name), 0, 20) ?></small></td>
                                    <td>
                                        <span class="badge bg-<?= $type_info['color'] ?>">
                                            <?= $type_info['label'] ?>
                                        </span>
                                    </td>
                                    <td class="<?= $quantity > 0 ? 'text-success' : 'text-danger' ?>">
                                        <strong><?= $quantity > 0 ? '+' : '' ?><?= $quantity ?></strong>
                                    </td>
                                    <td>
                                        <small>
                                            <?= $history['previous_stock'] ?> 
                                            <i class="fas fa-arrow-right mx-1"></i> 
                                            <strong><?= $history['new_stock'] ?></strong>
                                        </small>
                                    </td>
                                    <td><small><?= htmlspecialchars(substr($history['notes'] ?? '-', 0, 30)) ?>...</small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include "footer.php"; ?>

<style>
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

.page-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
</style>

<script>
function toggleQuantityLabel(select, currentStock) {
    const label = document.getElementById('quantityLabel');
    const input = document.querySelector('input[name="quantity"]');
    
    switch(select.value) {
        case 'restock':
            label.textContent = 'Jumlah Restock';
            input.min = 1;
            input.value = 1;
            break;
        case 'adjustment':
            label.textContent = 'Jumlah Penyesuaian (+/-)';
            input.min = -9999;
            input.value = 0;
            break;
        case 'set':
            label.textContent = 'Stok Baru';
            input.min = 0;
            input.value = currentStock;
            break;
    }
}

// Auto-close alerts
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(alert => {
        new bootstrap.Alert(alert).close();
    });
}, 5000);
</script>