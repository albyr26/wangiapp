<?php
// admin/export_pdf.php - Generate PDF dari laporan
require_once "../config.php";

// Check login
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit();
}

require_once '../vendor/autoload.php'; // Jika menggunakan composer

use Dompdf\Dompdf;
use Dompdf\Options;

// Ambil parameter
$period = $_GET['period'] ?? 'monthly';
$month = $_GET['month'] ?? date('Y-m');
$date = $_GET['date'] ?? date('Y-m-d');

// Set tanggal
if ($period === 'daily') {
    $title_period = date('d F Y', strtotime($date));
} else {
    $title_period = date('F Y', strtotime($month));
}

// Generate HTML untuk PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Laporan ParfumStore - ' . $title_period . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #4361ee; }
        .header p { margin: 5px 0; color: #666; }
        .section { margin-bottom: 20px; }
        .section-title { background: #f8f9fa; padding: 8px; font-weight: bold; border-left: 4px solid #4361ee; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        th { background: #4361ee; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .total-row { font-weight: bold; background: #f8f9fa; }
        .footer { text-align: center; margin-top: 30px; color: #666; font-size: 10px; border-top: 1px solid #ddd; padding-top: 10px; }
        .stat-box { display: inline-block; width: 23%; margin: 5px 1%; padding: 15px; background: #f8f9fa; border-radius: 5px; text-align: center; vertical-align: top; }
        .stat-value { font-size: 24px; font-weight: bold; color: #4361ee; }
        .stat-label { font-size: 10px; color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>PARFUMSTORE</h1>
        <p>Management System - Laporan ' . $title_period . '</p>
        <p>Dibuat: ' . date('d/m/Y H:i') . '</p>
    </div>
    
    <div class="section">
        <div class="section-title">Ringkasan Statistik</div>
        <div class="stat-box">
            <div class="stat-value">' . ($sales_data['total_orders'] ?? 0) . '</div>
            <div class="stat-label">Total Pesanan</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">Rp ' . number_format($sales_data['total'] ?? 0, 0, ',', '.') . '</div>
            <div class="stat-label">Total Penjualan</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">' . ($customer_data['total'] ?? 0) . '</div>
            <div class="stat-label">Total Pelanggan</div>
        </div>
        <div class="stat-box">
            <div class="stat-value">Rp ' . number_format($total_inventory_value, 0, ',', '.') . '</div>
            <div class="stat-label">Nilai Stok</div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Data Penjualan</div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pelanggan</th>
                    <th>Produk</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                </tr>
            </thead>
            <tbody>';
            
// Tambahkan data penjualan
if (isset($orders['data'])) {
    foreach ($orders['data'] as $index => $order) {
        $html .= '
                <tr>
                    <td>' . ($index + 1) . '</td>
                    <td>' . htmlspecialchars($order['customer_name']) . '</td>
                    <td>' . htmlspecialchars($order['product_name']) . '</td>
                    <td>Rp ' . number_format($order['total_price'], 0, ',', '.') . '</td>
                    <td>' . ucfirst($order['status']) . '</td>
                    <td>' . date('d/m/Y', strtotime($order['order_date'])) . '</td>
                </tr>';
    }
}

$html .= '
            </tbody>
        </table>
    </div>
    
    <div class="section">
        <div class="section-title">Produk Terlaris (Top 5)</div>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Produk</th>
                    <th>Qty Terjual</th>
                    <th>Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>';
            
// Tambahkan data produk terlaris
$counter = 1;
foreach (array_slice($top_products, 0, 5, true) as $product_name => $data) {
    $html .= '
                <tr>
                    <td>' . $counter++ . '</td>
                    <td>' . htmlspecialchars($product_name) . '</td>
                    <td>' . $data['quantity'] . ' unit</td>
                    <td>Rp ' . number_format($data['revenue'], 0, ',', '.') . '</td>
                </tr>';
}

$html .= '
            </tbody>
        </table>
    </div>
    
    <div class="footer">
        <p>Laporan ini dihasilkan secara otomatis oleh sistem ParfumStore</p>
        <p>© ' . date('Y') . ' ParfumStore - All rights reserved</p>
    </div>
</body>
</html>';

// Setup DomPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Arial');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output PDF
$dompdf->stream('laporan_parfumstore_' . date('Ymd_His') . '.pdf', [
    'Attachment' => true
]);
?>