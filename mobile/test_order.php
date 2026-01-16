<?php
// test_order.php
echo "<h2>Test Save Order</h2>";

// Test 1: Single product
echo "<h3>Test 1: Single Product</h3>";
$singleData = [
    "product_id" => "test-123",
    "product_name" => "Parfum Test",
    "customer_name" => "John Doe",
    "customer_phone" => "081234567890",
    "customer_address" => "Jl. Test No. 123",
    "delivery_method" => "pickup",
    "total_price" => 150000,
];

echo "<pre>Data: " . json_encode($singleData, JSON_PRETTY_PRINT) . "</pre>";

$ch = curl_init("http://localhost/wangi/mobile/save_order.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($singleData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/x-www-form-urlencoded",
]);

$response = curl_exec($ch);
echo "<pre>Response: $response</pre>";

// Test 2: Cart (JSON)
echo "<h3>Test 2: Cart (JSON)</h3>";
$cartData = [
    "customer_name" => "Jane Doe",
    "customer_phone" => "081298765432",
    "customer_address" => "Jl. Contoh No. 456",
    "delivery_method" => "delivery",
    "shipping_type" => "regular",
    "shipping_cost" => 15000,
    "total_price" => 165000,
    "products" => [
        [
            "id" => "test-123",
            "name" => "Parfum A",
            "price" => 75000,
            "quantity" => 2,
        ],
    ],
];

echo "<pre>Data: " . json_encode($cartData, JSON_PRETTY_PRINT) . "</pre>";

$ch = curl_init("http://localhost/wangi/mobile/save_order.php");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($cartData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

$response = curl_exec($ch);
echo "<pre>Response: $response</pre>";
?>
