<?php
// test_uuid_flow.php
require_once "../config.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h2>Test UUID Flow</h2>";

// 1. Dapatkan produk untuk testing
$products = supabase("cust_products", "GET", null, [
    "select" => "id,name,stock,price",
    "limit" => 2
]);

if (isset($products['success']) && $products['success'] && !empty($products['data'])) {
    $product = $products['data'][0];
    $productId = $product['id'];
    $productName = $product['name'];
    $currentStock = $product['stock'];
    
    echo "<h3>1. Product for testing:</h3>";
    echo "<pre>" . json_encode($product, JSON_PRETTY_PRINT) . "</pre>";
    
    // 2. Test create order dengan UUID
    echo "<h3>2. Test Create Order with UUID</h3>";
    
    $orderData = [
        "product_id" => $productId,
        "product_name" => $productName,
        "customer_name" => "Test Customer",
        "customer_phone" => "08123456789",
        "customer_address" => "Test Address",
        "delivery_method" => "pickup",
        "shipping_type" => "",
        "shipping_cost" => 0,
        "total_price" => $product['price'],
        "status" => "pending",
        "order_date" => date("Y-m-d H:i:s"),
    ];
    
    echo "Order Data to Send:<br>";
    echo "<pre>" . json_encode($orderData, JSON_PRETTY_PRINT) . "</pre>";
    
    $orderResult = supabase("orders", "POST", $orderData);
    echo "Order Create Result:<br>";
    echo "<pre>" . json_encode($orderResult, JSON_PRETTY_PRINT) . "</pre>";
    
    if (isset($orderResult['success']) && $orderResult['success']) {
        // 3. Test reduce stock
        echo "<h3>3. Test Reduce Stock</h3>";
        
        // Test query dengan UUID
        echo "<h4>a. Query product dengan UUID filter:</h4>";
        $queryResult = supabase("cust_products", "GET", null, [
            "select" => "id,name,stock",
            "id" => "eq." . $productId
        ]);
        echo "<pre>" . json_encode($queryResult, JSON_PRETTY_PRINT) . "</pre>";
        
        // Test update dengan UUID
        echo "<h4>b. Update stock dengan UUID:</h4>";
        $newStock = $currentStock - 1;
        $updateData = [
            "stock" => $newStock,
            "updated_at" => date('Y-m-d H:i:s')
        ];
        
        $updateResult = supabase("cust_products", "PATCH", $updateData, [
            "id" => "eq." . $productId
        ]);
        
        echo "Update Data:<br>";
        echo "<pre>" . json_encode($updateData, JSON_PRETTY_PRINT) . "</pre>";
        echo "Update Result:<br>";
        echo "<pre>" . json_encode($updateResult, JSON_PRETTY_PRINT) . "</pre>";
        
        // Verify
        echo "<h4>c. Verify update:</h4>";
        $verify = supabase("cust_products", "GET", null, [
            "select" => "id,name,stock",
            "id" => "eq." . $productId
        ]);
        echo "<pre>" . json_encode($verify, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>Tidak ada produk untuk testing</p>";
}
?>