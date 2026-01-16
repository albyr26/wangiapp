<?php
// check_tables.php
require_once "../config.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h2>Check Table Structure in Supabase</h2>";

// Cek struktur tabel orders
echo "<h3>1. Check Orders Table Structure</h3>";
$orders = supabase("orders", "GET", null, [
    "select" => "*",
    "limit" => 1
]);

if (isset($orders['success']) && $orders['success'] && !empty($orders['data'])) {
    $firstOrder = $orders['data'][0];
    echo "<pre>First Order Record: " . json_encode($firstOrder, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h4>Columns in orders table:</h4>";
    echo "<ul>";
    foreach (array_keys($firstOrder) as $column) {
        echo "<li>$column: " . gettype($firstOrder[$column]) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>Cannot access orders table</p>";
}

// Cek apakah bisa POST tanpa ID
echo "<h3>2. Test POST to orders (without ID)</h3>";
$testData = [
    "customer_name" => "Test Customer",
    "customer_phone" => "08123456789",
    "customer_address" => "Test Address",
    "delivery_method" => "pickup",
    "total_price" => 10000,
    "status" => "pending",
    "order_date" => date("Y-m-d H:i:s"),
    "created_at" => date("Y-m-d H:i:s"),
    "updated_at" => date("Y-m-d H:i:s")
];

$result = supabase("orders", "POST", $testData);
echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";

// Cek cust_products
echo "<h3>3. Check cust_products Table</h3>";
$products = supabase("cust_products", "GET", null, [
    "select" => "id,name,stock",
    "limit" => 2
]);

if (isset($products['success']) && $products['success']) {
    echo "<pre>" . json_encode($products, JSON_PRETTY_PRINT) . "</pre>";
    
    if (!empty($products['data'])) {
        echo "<h4>Product IDs are integers:</h4>";
        foreach ($products['data'] as $product) {
            echo "<p>ID: {$product['id']} (type: " . gettype($product['id']) . ")</p>";
        }
    }
}

// Cek stock_history
echo "<h3>4. Check stock_history Table</h3>";
$history = supabase("stock_history", "GET", null, [
    "select" => "*",
    "limit" => 2
]);

echo "<pre>" . json_encode($history, JSON_PRETTY_PRINT) . "</pre>";

// Cek order_items
echo "<h3>5. Check order_items Table</h3>";
$orderItems = supabase("order_items", "GET", null, [
    "select" => "*",
    "limit" => 2
]);

echo "<pre>" . json_encode($orderItems, JSON_PRETTY_PRINT) . "</pre>";