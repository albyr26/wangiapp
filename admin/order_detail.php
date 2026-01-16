<?php
// admin/order_detail.php
require_once "header.php";

$id = $_GET['id'] ?? '';
if (empty($id)) {
    header('Location: orders.php');
    exit;
}

// Get order details
$order = supabase('orders', 'GET', null, [
    'id' => 'eq.' . $id
]);

if (!isset($order['data'][0])) {
    $_SESSION['error'] = 'Pesanan tidak ditemukan!';
    header('Location: orders.php');
    exit;
}

$orderData = $order['data'][0];
?>

<?php include "sidebar.php"; ?>

<div class="main-content">
    <?php include "header-content.php"; ?>
    
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="orders.php">Pesanan</a></li>
                    <li class="breadcrumb-item active">Detail Pesanan</li>
                </ol>
            </nav>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card card-custom mb-4">
                <div class="card-header-custom">
                    <h5 class="mb-0">Detail Pesanan #<?= substr($orderData['id'], 0, 8) ?></h5>
                </div>
                <div class="card-body">
                    <!-- Isi detail pesanan -->
                    <p>Isi detail di sini...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "footer.php"; ?>