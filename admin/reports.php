<?php
// admin/reports.php - Halaman Laporan dengan Grafik
require_once "header.php";

// Include library untuk chart (Chart.js)
echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';

// Set default periode
$period = $_GET['period'] ?? 'monthly'; // daily, weekly, monthly, yearly
$month = $_GET['month'] ?? date('Y-m');
$date = $_GET['date'] ?? date('Y-m-d');
$year = $_GET['year'] ?? date('Y');

// Set tanggal berdasarkan periode
if ($period === 'daily') {
    $start_date = $date;
    $end_date = $date;
} elseif ($period === 'monthly') {
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
} elseif ($period === 'yearly') {
    $start_date = $year . '-01-01';
    $end_date = $year . '-12-31';
}

// **AMBIL DATA PENJUALAN**
$orders = supabase('orders', 'GET', null, [
    'select' => 'id, customer_name, customer_phone, customer_address, product_name, total_price, status, order_date, delivery_method',
    'order_date' => 'gte.' . $start_date,
    'order_date' => 'lte.' . $end_date,
    'order' => 'order_date.asc'
]);

// **AMBIL DATA PRODUK**
$products = supabase('cust_products', 'GET', null, [
    'select' => 'name, stock, price, category_id'
]);

// **AMBIL DATA ORDER ITEMS UNTUK PRODUK TERLARIS**
$order_items = supabase('order_items', 'GET', null, [
    'select' => 'product_name, quantity, subtotal, created_at',
    'created_at' => 'gte.' . $start_date,
    'created_at' => 'lte.' . $end_date
]);

// **PROSES DATA UNTUK CHART**
$sales_data = [];
$customer_data = [];
$product_sales_data = [];
$status_data = [];
$daily_sales = [];
$top_products = [];
$inventory_status = [
    'low' => 0,
    'medium' => 0,
    'good' => 0,
    'out' => 0
];
$total_inventory_value = 0;

// Data penjualan untuk chart
if (isset($orders['data']) && is_array($orders['data'])) {
    $total_sales = 0;
    $total_orders = count($orders['data']);
    $unique_customers = [];
    $status_counts = [];
    $sales_by_day = [];
    
    foreach ($orders['data'] as $order) {
        // Total penjualan
        $total_sales += floatval($order['total_price']);
        
        // Pelanggan unik
        $key = md5($order['customer_name'] . '_' . $order['customer_phone']);
        if (!isset($unique_customers[$key])) {
            $unique_customers[$key] = true;
        }
        
        // Status order
        $status = $order['status'];
        if (!isset($status_counts[$status])) {
            $status_counts[$status] = 0;
        }
        $status_counts[$status]++;
        
        // Penjualan per hari
        $day = date('Y-m-d', strtotime($order['order_date']));
        if (!isset($sales_by_day[$day])) {
            $sales_by_day[$day] = 0;
        }
        $sales_by_day[$day] += floatval($order['total_price']);
    }
    
    // Data untuk chart
    $sales_data = [
        'total' => $total_sales,
        'average' => $total_orders > 0 ? $total_sales / $total_orders : 0,
        'total_orders' => $total_orders
    ];
    
    $customer_data = [
        'total' => count($unique_customers),
        'repeat_rate' => $total_orders > 0 ? count($unique_customers) / $total_orders * 100 : 0
    ];
    
    $status_data = $status_counts;
    
    // Sort by date untuk daily chart
    ksort($sales_by_day);
    foreach ($sales_by_day as $day => $amount) {
        $daily_sales[] = [
            'day' => date('d M', strtotime($day)),
            'amount' => $amount
        ];
    }
}

// Data produk terlaris
if (isset($order_items['data']) && is_array($order_items['data'])) {
    $product_sales = [];
    
    foreach ($order_items['data'] as $item) {
        $product_name = $item['product_name'];
        
        if (!isset($product_sales[$product_name])) {
            $product_sales[$product_name] = [
                'quantity' => 0,
                'revenue' => 0
            ];
        }
        
        $product_sales[$product_name]['quantity'] += intval($item['quantity']);
        $product_sales[$product_name]['revenue'] += floatval($item['subtotal']);
    }
    
    // Sort by revenue dan ambil top 10
    uasort($product_sales, function($a, $b) {
        return $b['revenue'] <=> $a['revenue'];
    });
    
    $top_products = array_slice($product_sales, 0, 10, true);
}

// Data stok produk
if (isset($products['data']) && is_array($products['data'])) {
    foreach ($products['data'] as $product) {
        $stock = intval($product['stock']);
        $price = floatval($product['price']);
        $stock_value = $stock * $price;
        $total_inventory_value += $stock_value;
        
        if ($stock <= 0) {
            $inventory_status['out']++;
        } elseif ($stock <= 5) {
            $inventory_status['low']++;
        } elseif ($stock <= 10) {
            $inventory_status['medium']++;
        } else {
            $inventory_status['good']++;
        }
    }
}

// **DATA PETA PELANGGAN** (contoh koordinat dari alamat)
// Note: Ini contoh data statis, bisa dihubungkan dengan API geocoding
$customer_coordinates = [
    ['name' => 'Ayp', 'lat' => -6.1559, 'lng' => 106.8414, 'orders' => 1, 'total' => 50000],
    ['name' => 'Alby', 'lat' => -6.5971, 'lng' => 106.8060, 'orders' => 1, 'total' => 35000]
];
?>

<!-- Include Sidebar -->
<?php include "sidebar.php"; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Include Header Content -->
    <?php include "header-content.php"; ?>
    
    <!-- Messages -->
    <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show alert-auto-hide" role="alert">
        <i class="fas fa-check-circle me-2"></i>
        <?= $_SESSION['success'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); endif; ?>
    
    <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show alert-auto-hide" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>
        <?= $_SESSION['error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); endif; ?>

    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="fas fa-chart-line me-2"></i>Laporan Dashboard</h2>
                        <p class="text-muted mb-0">Analisis lengkap penjualan, pelanggan, dan produk</p>
                    </div>
                    <div>
                        <button onclick="exportToPDF()" class="btn btn-danger">
                            <i class="fas fa-file-pdf me-1"></i> Export PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Controls -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <!-- Period Tabs -->
                        <div class="col-md-8">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn <?= $period === 'daily' ? 'btn-primary' : 'btn-outline-primary' ?>"
                                        onclick="setPeriod('daily')">
                                    Harian
                                </button>
                                <button type="button" class="btn <?= $period === 'monthly' ? 'btn-primary' : 'btn-outline-primary' ?>"
                                        onclick="setPeriod('monthly')">
                                    Bulanan
                                </button>
                                <button type="button" class="btn <?= $period === 'yearly' ? 'btn-primary' : 'btn-outline-primary' ?>"
                                        onclick="setPeriod('yearly')">
                                    Tahunan
                                </button>
                            </div>
                            
                            <div class="row mt-3 g-2">
                                <!-- Daily Selector -->
                                <div class="col-md-4 <?= $period !== 'daily' ? 'd-none' : '' ?>" id="dailySelector">
                                    <input type="date" name="date" class="form-control" 
                                           value="<?= $date ?>" max="<?= date('Y-m-d') ?>">
                                </div>
                                
                                <!-- Monthly Selector -->
                                <div class="col-md-4 <?= $period !== 'monthly' ? 'd-none' : '' ?>" id="monthlySelector">
                                    <input type="month" name="month" class="form-control" 
                                           value="<?= $month ?>" max="<?= date('Y-m') ?>">
                                </div>
                                
                                <!-- Yearly Selector -->
                                <div class="col-md-4 <?= $period !== 'yearly' ? 'd-none' : '' ?>" id="yearlySelector">
                                    <select name="year" class="form-select">
                                        <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                        <option value="<?= $y ?>" <?= $y == $year ? 'selected' : '' ?>>
                                            <?= $y ?>
                                        </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="fas fa-filter me-1"></i> Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="col-md-4">
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-success" onclick="refreshCharts()">
                                    <i class="fas fa-sync-alt me-1"></i> Refresh Data
                                </button>
                                <a href="reports.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i> Reset Filter
                                </a>
                            </div>
                        </div>
                        
                        <input type="hidden" name="period" id="period" value="<?= $period ?>">
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #4361ee, #3a0ca3); color: white;">
                <div class="stat-value"><?= number_format($sales_data['total_orders'] ?? 0) ?></div>
                <div class="stat-label">Total Pesanan</div>
                <div class="stat-detail">
                    <small>
                        <i class="fas fa-arrow-up me-1"></i>
                        <?= number_format($sales_data['average'] ?? 0, 0, ',', '.') ?> /order
                    </small>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #4cc9f0, #4895ef); color: white;">
                <div class="stat-value">Rp <?= number_format($sales_data['total'] ?? 0, 0, ',', '.') ?></div>
                <div class="stat-label">Total Penjualan</div>
                <div class="stat-detail">
                    <small>
                        <i class="fas fa-calendar me-1"></i>
                        Periode <?= $period === 'daily' ? date('d M Y', strtotime($date)) : ($period === 'monthly' ? date('F Y', strtotime($month)) : $year) ?>
                    </small>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-money-bill-wave"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #ff9e00, #ff9100); color: white;">
                <div class="stat-value"><?= number_format($customer_data['total'] ?? 0) ?></div>
                <div class="stat-label">Total Pelanggan</div>
                <div class="stat-detail">
                    <small>
                        <i class="fas fa-percentage me-1"></i>
                        <?= number_format($customer_data['repeat_rate'] ?? 0, 1) ?>% repeat rate
                    </small>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="stat-card" style="background: linear-gradient(135deg, #7209b7, #560bad); color: white;">
                <div class="stat-value">Rp <?= number_format($total_inventory_value, 0, ',', '.') ?></div>
                <div class="stat-label">Nilai Stok</div>
                <div class="stat-detail">
                    <small>
                        <i class="fas fa-box me-1"></i>
                        <?= $inventory_status['low'] ?> rendah, <?= $inventory_status['out'] ?> habis
                    </small>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-warehouse"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="row mb-4">
        <!-- Sales Chart -->
        <div class="col-xl-8 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Grafik Penjualan</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Products Pie Chart -->
        <div class="col-xl-4 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Produk Terlaris</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="productsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Maps and Inventory Section -->
    <div class="row mb-4">
        <!-- Customer Map -->
        <div class="col-xl-6 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Peta Distribusi Pelanggan</h5>
                </div>
                <div class="card-body">
                    <div id="customerMap" style="height: 400px; border-radius: 8px;"></div>
                </div>
            </div>
        </div>
        
        <!-- Inventory Status -->
        <div class="col-xl-6 mb-4">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <h5 class="mb-0"><i class="fas fa-boxes me-2"></i>Status Stok Produk</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="inventoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sales Data Table -->
    <div class="row">
        <div class="col-12">
            <div class="card card-custom">
                <div class="card-header-custom">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Data Penjualan</h5>
                        <span class="badge bg-primary"><?= $sales_data['total_orders'] ?? 0 ?> Transaksi</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($orders['data']) && !empty($orders['data'])): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-custom" id="salesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>ID Order</th>
                                    <th>Pelanggan</th>
                                    <th>Produk</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                    <th>Metode</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders['data'] as $index => $order): 
                                    $statusColors = [
                                        'pending' => 'warning',
                                        'confirmed' => 'info',
                                        'processing' => 'primary',
                                        'shipped' => 'secondary',
                                        'delivered' => 'success',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ];
                                    $statusColor = $statusColors[$order['status']] ?? 'secondary';
                                ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td>
                                        <small class="text-muted">#<?= substr($order['id'], 0, 8) ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($order['customer_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($order['customer_phone'] ?? '') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($order['product_name']) ?></td>
                                    <td class="fw-bold text-success">
                                        Rp <?= number_format($order['total_price'], 0, ',', '.') ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?= date('d/m/Y H:i', strtotime($order['order_date'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= $order['delivery_method'] ?? 'Pickup' ?>
                                        </small>
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
                        <h5 class="text-muted">Tidak ada data penjualan</h5>
                        <p class="text-muted">
                            Tidak ada transaksi ditemukan untuk periode yang dipilih
                        </p>
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
    position: relative;
    overflow: hidden;
    height: 100%;
}

.stat-card .stat-value {
    font-size: 1.8rem;
    font-weight: bold;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    margin-top: 5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-card .stat-detail {
    margin-top: 10px;
    font-size: 0.8rem;
    opacity: 0.8;
}

.stat-card .stat-icon {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2rem;
    opacity: 0.2;
}

.card-custom {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    height: 100%;
}

.card-header-custom {
    background: white;
    border-bottom: 1px solid #eee;
    padding: 15px 20px;
    border-radius: 10px 10px 0 0;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

#customerMap {
    min-height: 400px;
}

.table-custom th {
    font-weight: 600;
    color: #495057;
    border-bottom: 2px solid #dee2e6;
    white-space: nowrap;
    background: #f8f9fa;
}

.table-custom td {
    vertical-align: middle;
    padding: 12px 8px;
}

/* Legend for maps */
.leaflet-control-layers {
    background: white;
    padding: 10px;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
</style>

<script>
// Fungsi untuk set periode
function setPeriod(period) {
    document.getElementById('period').value = period;
    
    // Show/hide selectors
    document.querySelectorAll('[id$="Selector"]').forEach(el => {
        el.classList.add('d-none');
    });
    
    document.getElementById(period + 'Selector').classList.remove('d-none');
}

// Initialize Charts
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($daily_sales, 'day')) ?>,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: <?= json_encode(array_column($daily_sales, 'amount')) ?>,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
    
    // Products Chart
    const productsCtx = document.getElementById('productsChart').getContext('2d');
    const productsChart = new Chart(productsCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_keys($top_products)) ?>,
            datasets: [{
                data: <?= json_encode(array_column($top_products, 'revenue')) ?>,
                backgroundColor: [
                    '#4361ee', '#4cc9f0', '#ff9e00', '#f72585', 
                    '#7209b7', '#3a0ca3', '#4895ef', '#ff9100',
                    '#b5179e', '#560bad'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        boxWidth: 12,
                        padding: 20
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const productName = context.label;
                            const revenue = context.raw;
                            const quantity = <?= json_encode(array_column($top_products, 'quantity')) ?>[context.dataIndex];
                            return [
                                productName,
                                'Qty: ' + quantity + ' unit',
                                'Rp ' + revenue.toLocaleString('id-ID')
                            ];
                        }
                    }
                }
            }
        }
    });
    
    // Inventory Chart
    const inventoryCtx = document.getElementById('inventoryChart').getContext('2d');
    const inventoryChart = new Chart(inventoryCtx, {
        type: 'bar',
        data: {
            labels: ['Stok Habis', 'Stok Rendah', 'Stok Sedang', 'Stok Aman'],
            datasets: [{
                label: 'Jumlah Produk',
                data: [
                    <?= $inventory_status['out'] ?>,
                    <?= $inventory_status['low'] ?>,
                    <?= $inventory_status['medium'] ?>,
                    <?= $inventory_status['good'] ?>
                ],
                backgroundColor: [
                    '#f72585',
                    '#ff9e00',
                    '#4cc9f0',
                    '#4361ee'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
    
    // Initialize Map
    initCustomerMap();
});

// Initialize Customer Map
function initCustomerMap() {
    const map = L.map('customerMap').setView([-6.1751, 106.8650], 5); // Center Indonesia
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add customer markers
    const customers = <?= json_encode($customer_coordinates) ?>;
    
    customers.forEach(customer => {
        const marker = L.marker([customer.lat, customer.lng]).addTo(map);
        
        marker.bindPopup(`
            <div style="min-width: 200px;">
                <h6 style="margin: 0 0 5px 0;">${customer.name}</h6>
                <p style="margin: 0 0 5px 0; font-size: 12px;">
                    <strong>Total Order:</strong> ${customer.orders}<br>
                    <strong>Total Belanja:</strong> Rp ${customer.total.toLocaleString('id-ID')}
                </p>
                <small class="text-muted">Klik untuk detail</small>
            </div>
        `);
        
        // Custom icon berdasarkan total belanja
        const iconSize = customer.total > 50000 ? [30, 30] : [25, 25];
        const iconColor = customer.total > 50000 ? '#f72585' : '#4361ee';
        
        const customIcon = L.divIcon({
            html: `<div style="
                background: ${iconColor};
                width: ${iconSize[0]}px;
                height: ${iconSize[1]}px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                border: 2px solid white;
                box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            ">
                ${customer.name.charAt(0)}
            </div>`,
            className: 'custom-marker',
            iconSize: iconSize,
            iconAnchor: [iconSize[0]/2, iconSize[1]/2]
        });
        
        marker.setIcon(customIcon);
    });
    
    // Fit bounds to show all markers
    if (customers.length > 0) {
        const group = new L.featureGroup(customers.map(c => 
            L.marker([c.lat, c.lng])
        ));
        map.fitBounds(group.getBounds().pad(0.1));
    }
}

// Refresh Charts
function refreshCharts() {
    location.reload();
}

// Export to PDF
function exportToPDF() {
    const element = document.querySelector('.main-content');
    const opt = {
        margin: [10, 10, 10, 10],
        filename: 'laporan_parfumstore_<?= date('Ymd_His') ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2,
            useCORS: true,
            logging: true
        },
        jsPDF: { 
            unit: 'mm', 
            format: 'a4', 
            orientation: 'portrait' 
        }
    };
    
    // Tampilkan loading
    const loadingBtn = event.target;
    const originalHTML = loadingBtn.innerHTML;
    loadingBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Processing...';
    loadingBtn.disabled = true;
    
    // Import html2pdf hanya ketika diperlukan
    const script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
    script.onload = function() {
        html2pdf().set(opt).from(element).save().then(() => {
            // Reset button
            loadingBtn.innerHTML = originalHTML;
            loadingBtn.disabled = false;
        });
    };
    document.head.appendChild(script);
}

// Quick date range buttons
function setQuickRange(range) {
    const today = new Date();
    const dateInput = document.querySelector('input[name="date"]');
    const monthInput = document.querySelector('input[name="month"]');
    const yearSelect = document.querySelector('select[name="year"]');
    
    if (range === 'today') {
        dateInput.value = today.toISOString().split('T')[0];
        document.getElementById('period').value = 'daily';
        setPeriod('daily');
    } else if (range === 'month') {
        monthInput.value = today.toISOString().substring(0, 7);
        document.getElementById('period').value = 'monthly';
        setPeriod('monthly');
    }
}

// Auto-hide alerts
setTimeout(function() {
    document.querySelectorAll('.alert-auto-hide').forEach(alert => {
        alert.style.transition = 'all 0.5s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        alert.style.maxHeight = '0';
        alert.style.padding = '0';
        alert.style.margin = '0';
        alert.style.overflow = 'hidden';
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 500);
    });
}, 5000);
</script>