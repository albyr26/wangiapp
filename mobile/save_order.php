<?php
// save_order.php - VERSI DEBUGGING DETAIL
require_once "../config.php";

// Untuk debugging - tampilkan error
error_reporting(E_ALL);
ini_set("display_errors", 1);

header("Content-Type: application/json");

// Log untuk debugging
function log_debug($message)
{
    $log_file = __DIR__ . "/save_order_debug.log";
    $timestamp = date("Y-m-d H:i:s");
    $log_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

log_debug("==========================================");
log_debug("=== MULAI PROSES SAVE ORDER ===");
log_debug("==========================================");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        // Log semua input
        log_debug("Method: POST");
        log_debug("Content-Type: " . ($_SERVER["CONTENT_TYPE"] ?? "not set"));

        $input = file_get_contents("php://input");
        log_debug("Raw input length: " . strlen($input));
        log_debug("Raw input: " . $input);

        // Cek apakah ini JSON atau Form Data
        $contentType = $_SERVER["CONTENT_TYPE"] ?? "";

        if (strpos($contentType, "application/json") !== false) {
            log_debug("Processing as JSON request");
            $jsonData = json_decode($input, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                log_debug("JSON decode berhasil");
                handleJsonRequest($jsonData);
            } else {
                log_debug("JSON decode error: " . json_last_error_msg());
                echo json_encode([
                    "success" => false,
                    "error" => "Invalid JSON: " . json_last_error_msg(),
                    "raw_input" => $input,
                ]);
            }
        } else {
            log_debug("Processing as Form Data");
            log_debug("POST data: " . json_encode($_POST));
            handleFormRequest($_POST);
        }
    } catch (Exception $e) {
        log_debug("Exception: " . $e->getMessage());
        log_debug("Exception trace: " . $e->getTraceAsString());
        echo json_encode([
            "success" => false,
            "error" => "Exception: " . $e->getMessage(),
            "trace" => $e->getTraceAsString(),
        ]);
    }
} else {
    log_debug("Method tidak diizinkan: " . $_SERVER["REQUEST_METHOD"]);
    echo json_encode(["success" => false, "error" => "Method tidak diizinkan"]);
}

function handleJsonRequest($data)
{
    log_debug("=== HANDLE JSON REQUEST ===");
    log_debug("Data received: " . json_encode($data, JSON_PRETTY_PRINT));

    // Cek apakah ini cart atau single product
    $hasProductsArray = isset($data["products"]) && is_array($data["products"]);

    if ($hasProductsArray) {
        log_debug("Detected as CART request");
        handleCartRequest($data);
    } else {
        log_debug("Detected as SINGLE PRODUCT request");
        handleSingleProductJson($data);
    }
}

function handleCartRequest($data)
{
    log_debug("--- Processing Cart ---");

    $customerName = trim($data["customer_name"] ?? "");
    $customerPhone = trim($data["customer_phone"] ?? "");
    $customerAddress = trim($data["customer_address"] ?? "");
    $deliveryMethod = trim($data["delivery_method"] ?? "pickup");
    $shippingType = trim($data["shipping_type"] ?? "");
    $shippingCost = floatval($data["shipping_cost"] ?? 0);
    $totalPrice = floatval($data["total_price"] ?? 0);
    $products = $data["products"] ?? [];

    log_debug("Customer: $customerName, Phone: $customerPhone");
    log_debug("Delivery: $deliveryMethod, Shipping Type: $shippingType");
    log_debug("Shipping Cost: $shippingCost, Total Price: $totalPrice");
    log_debug("Products count: " . count($products));

    // Validasi
    $errors = [];
    if (empty($customerName)) {
        $errors[] = "Nama pelanggan kosong";
    }
    if (empty($customerPhone)) {
        $errors[] = "Telepon pelanggan kosong";
    }
    if (empty($customerAddress)) {
        $errors[] = "Alamat pelanggan kosong";
    }
    if (empty($products)) {
        $errors[] = "Tidak ada produk dalam keranjang";
    }

    if (!empty($errors)) {
        $errorMsg = "Validasi gagal: " . implode(", ", $errors);
        log_debug($errorMsg);
        echo json_encode(["success" => false, "error" => $errorMsg]);
        exit();
    }

    // 1. Simpan order utama (tanpa product_id dan product_name karena cart)
    $orderData = [
        "customer_name" => $customerName,
        "customer_phone" => $customerPhone,
        "customer_address" => $customerAddress,
        "delivery_method" => $deliveryMethod,
        "shipping_type" =>
            $deliveryMethod === "delivery" ? $shippingType : null,
        "shipping_cost" => $shippingCost,
        "total_price" => $totalPrice,
        "status" => "pending",
        "order_date" => date("Y-m-d H:i:s"),
        // Untuk cart, product_id dan product_name NULL
        "product_id" => null,
        "product_name" => null,
    ];

    log_debug("Preparing to save order data: " . json_encode($orderData));

    // Simpan ke orders
    $orderResult = supabase("orders", "POST", $orderData);
    log_debug("Supabase orders response: " . json_encode($orderResult));

    if (!isset($orderResult["success"]) || !$orderResult["success"]) {
        $errorDetails = "";
        if (isset($orderResult["message"])) {
            $errorDetails .= "Message: " . $orderResult["message"];
        }
        if (isset($orderResult["error"])) {
            $errorDetails .= " Error: " . $orderResult["error"];
        }
        if (isset($orderResult["data"]["message"])) {
            $errorDetails .=
                " Data Message: " . $orderResult["data"]["message"];
        }

        $errorMsg =
            "Gagal menyimpan pesanan ke orders" .
            ($errorDetails ? ": " . $errorDetails : "");
        log_debug($errorMsg);
        echo json_encode(["success" => false, "error" => $errorMsg]);
        exit();
    }

    $orderId = $orderResult["data"]["id"] ?? "";
    if (empty($orderId)) {
        log_debug("ERROR: Order ID kosong setelah save");
        echo json_encode([
            "success" => false,
            "error" => "Gagal mendapatkan ID pesanan",
            "response" => $orderResult,
        ]);
        exit();
    }

    log_debug("SUCCESS: Order berhasil disimpan dengan ID: $orderId");

    // 2. Simpan items ke order_items
    $savedItems = 0;
    $failedItems = 0;

    foreach ($products as $index => $product) {
        $productId = $product["id"] ?? "";
        $productName = $product["name"] ?? "";
        $quantity = intval($product["quantity"] ?? 1);
        $price = floatval($product["price"] ?? 0);

        if (empty($productId) || $quantity <= 0) {
            log_debug(
                "Produk invalid di index $index: ID=$productId, Qty=$quantity",
            );
            $failedItems++;
            continue;
        }

        $itemData = [
            "order_id" => $orderId,
            "product_id" => $productId,
            "product_name" => $productName,
            "quantity" => $quantity,
            "unit_price" => $price,
            "subtotal" => $price * $quantity,
        ];

        log_debug("Saving item $index: " . json_encode($itemData));

        $itemResult = supabase("order_items", "POST", $itemData);

        if (isset($itemResult["success"]) && $itemResult["success"]) {
            $savedItems++;
            log_debug("Item $index berhasil disimpan");

            // Kurangi stok
            reduceProductStock($productId, $quantity);
        } else {
            $failedItems++;
            log_debug("Item $index gagal: " . json_encode($itemResult));
        }
    }

    // 3. Response
    if ($savedItems > 0) {
        $message = "Pesanan berhasil disimpan. $savedItems produk tersimpan";
        if ($failedItems > 0) {
            $message .= ", $failedItems produk gagal";
        }

        log_debug("SUCCESS: " . $message);
        echo json_encode([
            "success" => true,
            "order_id" => $orderId,
            "message" => $message,
            "saved_items" => $savedItems,
            "failed_items" => $failedItems,
        ]);
    } else {
        log_debug("ERROR: Tidak ada produk yang berhasil disimpan");
        echo json_encode([
            "success" => false,
            "error" => "Tidak ada produk yang berhasil disimpan",
            "order_id" => $orderId,
        ]);
    }
}

function handleSingleProductJson($data)
{
    log_debug("--- Processing Single Product (JSON) ---");

    $productId = trim($data["product_id"] ?? "");
    $productName = trim($data["product_name"] ?? "");
    $customerName = trim($data["customer_name"] ?? "");
    $customerPhone = trim($data["customer_phone"] ?? "");
    $customerAddress = trim($data["customer_address"] ?? "");
    $deliveryMethod = trim($data["delivery_method"] ?? "pickup");
    $shippingType = trim($data["shipping_type"] ?? "");
    $shippingCost = floatval($data["shipping_cost"] ?? 0);
    $totalPrice = floatval($data["total_price"] ?? 0);

    log_debug("Product: $productName ($productId)");
    log_debug("Customer: $customerName, Phone: $customerPhone");
    log_debug("Total Price: $totalPrice");

    // Validasi
    $errors = [];
    if (empty($productId)) {
        $errors[] = "ID produk kosong";
    }
    if (empty($productName)) {
        $errors[] = "Nama produk kosong";
    }
    if (empty($customerName)) {
        $errors[] = "Nama pelanggan kosong";
    }
    if (empty($customerPhone)) {
        $errors[] = "Telepon pelanggan kosong";
    }

    if (!empty($errors)) {
        $errorMsg = "Validasi gagal: " . implode(", ", $errors);
        log_debug($errorMsg);
        echo json_encode(["success" => false, "error" => $errorMsg]);
        exit();
    }

    // Simpan ke orders
    $orderData = [
        "product_id" => $productId,
        "product_name" => $productName,
        "customer_name" => $customerName,
        "customer_phone" => $customerPhone,
        "customer_address" => $customerAddress,
        "delivery_method" => $deliveryMethod,
        "shipping_type" => $shippingType,
        "shipping_cost" => $shippingCost,
        "total_price" => $totalPrice,
        "status" => "pending",
        "order_date" => date("Y-m-d H:i:s"),
    ];

    log_debug("Saving order: " . json_encode($orderData));

    $result = supabase("orders", "POST", $orderData);
    log_debug("Supabase response: " . json_encode($result));

    if (isset($result["success"]) && $result["success"]) {
        $orderId = $result["data"]["id"] ?? "";
        log_debug("SUCCESS: Order berhasil dengan ID: $orderId");

        // Kurangi stok
        reduceProductStock($productId, 1);

        // Juga simpan ke order_items untuk konsistensi
        if ($orderId) {
            $itemData = [
                "order_id" => $orderId,
                "product_id" => $productId,
                "product_name" => $productName,
                "quantity" => 1,
                "unit_price" => $totalPrice,
                "subtotal" => $totalPrice,
            ];

            $itemResult = supabase("order_items", "POST", $itemData);
            log_debug("Order items save result: " . json_encode($itemResult));
        }

        echo json_encode([
            "success" => true,
            "order_id" => $orderId,
            "message" => "Pesanan berhasil disimpan",
        ]);
    } else {
        $errorDetails = "";
        if (isset($result["message"])) {
            $errorDetails .= "Message: " . $result["message"];
        }
        if (isset($result["error"])) {
            $errorDetails .= " Error: " . $result["error"];
        }
        if (isset($result["data"]["message"])) {
            $errorDetails .= " Data Message: " . $result["data"]["message"];
        }

        $errorMsg =
            "Gagal menyimpan pesanan" .
            ($errorDetails ? ": " . $errorDetails : "");
        log_debug("ERROR: " . $errorMsg);
        echo json_encode(["success" => false, "error" => $errorMsg]);
    }
}

function handleFormRequest($postData)
{
    log_debug("=== HANDLE FORM REQUEST ===");
    log_debug("Form data: " . json_encode($postData));

    // Sama seperti single product JSON
    handleSingleProductJson($postData);
}

function reduceProductStock($productId, $quantity)
{
    try {
        log_debug("Reducing stock for $productId by $quantity");

        // Get current stock - GANTI format parameter
        $productResult = supabase(
            "cust_products", 
            "GET", 
            null,  // Ganti [] dengan null
            [
                "select" => "id,stock",
                "id" => "eq." . $productId,  // GANTI format ini
                "limit" => 1
            ]
        );

        log_debug("Get product result: " . json_encode($productResult));

        if (isset($productResult['data']) && count($productResult['data']) > 0) {
            $currentStock = intval($productResult['data'][0]['stock'] ?? 0);
            $newStock = $currentStock - $quantity;

            if ($newStock < 0) {
                log_debug("Stock insufficient: $currentStock - $quantity = $newStock");
                // Buat record adjustment negative untuk tracking
                createStockAdjustment($productId, -$quantity, $currentStock, "Penjualan melebihi stok");
                $newStock = 0; // Set ke 0 minimal
            }

            // Update stock - GANTI format seperti di inventory.php
            $updateResult = supabase(
                "cust_products", 
                "PATCH", 
                [
                    "stock" => $newStock,
                    "updated_at" => date('Y-m-d H:i:s')
                ], 
                [
                    "id" => "eq." . $productId  // GANTI format ini
                ]
            );

            log_debug("Update stock result: " . json_encode($updateResult));

            if (!isset($updateResult['error'])) {
                // Tambah history stok seperti di inventory.php
                $history_data = [
                    'product_id' => $productId,
                    'type' => 'sale',
                    'quantity' => -$quantity, // negatif karena pengurangan
                    'previous_stock' => $currentStock,
                    'new_stock' => $newStock,
                    'notes' => 'Penjualan produk',
                    'created_by' => 'system', // atau customer name jika ada
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $historyResult = supabase('stock_history', 'POST', $history_data);
                log_debug("History save result: " . json_encode($historyResult));
                
                log_debug("Stock updated successfully from $currentStock to $newStock");
                return true;
            } else {
                log_debug("Failed to update stock: " . json_encode($updateResult));
            }
        } else {
            log_debug("Product not found: $productId");
        }

        return false;
    } catch (Exception $e) {
        log_debug("Exception reducing stock: " . $e->getMessage());
        return false;
    }
}

// Fungsi bantuan untuk adjustment negative
function createStockAdjustment($productId, $quantity, $currentStock, $notes)
{
    $newStock = max(0, $currentStock + $quantity); // quantity bisa negatif
    
    $history_data = [
        'product_id' => $productId,
        'type' => 'adjustment',
        'quantity' => $quantity,
        'previous_stock' => $currentStock,
        'new_stock' => $newStock,
        'notes' => $notes,
        'created_by' => 'system_auto',
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    return supabase('stock_history', 'POST', $history_data);
}
// Test function supabase - tambahkan ini untuk debugging
function testSupabaseConnection()
{
    log_debug("Testing Supabase connection...");

    // Test simple query to orders table
    $testResult = supabase("orders", "GET", [], ["limit" => 1]);
    log_debug("Supabase connection test: " . json_encode($testResult));

    return isset($testResult["success"]) && $testResult["success"];
}

// Jalankan test connection
log_debug("Running connection test...");
$connectionOk = testSupabaseConnection();
log_debug("Connection test result: " . ($connectionOk ? "OK" : "FAILED"));

log_debug("=== END OF FILE ===");
?>
