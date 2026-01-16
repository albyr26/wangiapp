<?php
// debug_post.php - Debug POST data
session_start();

echo "<h1>Debug POST Data</h1>";

// Simulasikan POST data dari form edit
$_SESSION["admin_logged_in"] = true; // Set session untuk test

// Data contoh dari produk CK One
$_POST = [
    "id" => "08ab91e9-23a6-4eaa-b788-4b8264360734",
    "name" => "CK One - 100ml UPDATED",
    "category_id" => "7d66653f-6953-445f-b7d0-3e2526a33553",
    "price" => "600000",
    "stock" => "25",
    "likes_count" => "0",
    "short_description" => "Test update",
    "description" => "Test update description",
    "current_image_url" => "",
    "image_url_text" => "",
    "product_images" => "",
];

echo "<h2>POST Data:</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

// Include config
require_once "../config.php";

echo "<h2>Testing PATCH Request...</h2>";

// Test PATCH langsung
$update_data = [
    "name" => "CK One - 100ml UPDATED TEST",
    "price" => 650000,
    "stock" => 30,
    "updated_at" => date("Y-m-d H:i:s"),
];

$result = supabase("cust_products", "PATCH", $update_data, [
    "id" => "eq." . $_POST["id"],
]);

echo "<h3>PATCH Result:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result["success"]) {
    echo "<p style='color:green; font-weight:bold;'>✓ PATCH BERHASIL!</p>";
    echo "<p>HTTP Code: " . $result["code"] . "</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>✗ PATCH GAGAL!</p>";
    echo "<p>HTTP Code: " . $result["code"] . "</p>";
    echo "<p>Error: " . ($result["error"] ?? "No error message") . "</p>";
    echo "<p>Raw Response: " .
        htmlspecialchars(substr($result["raw"] ?? "", 0, 500)) .
        "</p>";
}

// Test GET untuk verifikasi
echo "<h2>Verification GET Request...</h2>";
$verify = supabase("cust_products", "GET", null, [
    "id" => "eq." . $_POST["id"],
    "select" => "id,name,price,stock,updated_at",
]);

echo "<pre>";
print_r($verify);
echo "</pre>";
?>
