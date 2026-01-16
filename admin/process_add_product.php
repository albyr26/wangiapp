<?php
// DI AWAL FILE, SEBELUM session_start():
echo "<!-- PHP Version: " . phpversion() . " -->\n";
echo "<!-- Max Upload Size: " . ini_get("upload_max_filesize") . " -->\n";
echo "<!-- Max Post Size: " . ini_get("post_max_size") . " -->\n";
echo "<!-- GD Library: " .
    (function_exists("imagecreatefromjpeg") ? "ENABLED" : "DISABLED") .
    " -->\n";

session_start();
require_once "../config.php";

// Aktifkan error untuk debug
error_reporting(E_ALL);
ini_set("display_errors", 1);

// Cek login
if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Processing Add Product</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                .debug { background: #f8f9fa; padding: 15px; border: 1px solid #e3e6f0; border-radius: 5px; margin: 10px 0; }
                .success { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; }
                .error { background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; }
                .info { background: #e0f7fa; padding: 10px; border: 1px solid #b2ebf2; border-radius: 5px; margin: 10px 0; }
                .warning { background: #fff3cd; color: #856404; padding: 10px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 10px 0; }
                pre { white-space: pre-wrap; word-wrap: break-word; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 5px; }
                .btn:hover { background: #0056b3; }
                .btn-secondary { background: #6c757d; }
                .btn-secondary:hover { background: #545b62; }
            </style>
        </head>
        <body>
        <h1>Processing Add Product - DEBUG MODE</h1>";

        // ========== DEBUG DETAIL ==========
        echo "<div class='debug'>";
        echo "<h3>🔄 SERVER & PHP CONFIG</h3>";
        echo "PHP Version: " . phpversion() . "<br>";
        echo "upload_max_filesize: " . ini_get("upload_max_filesize") . "<br>";
        echo "post_max_size: " . ini_get("post_max_size") . "<br>";
        echo "memory_limit: " . ini_get("memory_limit") . "<br>";
        echo "max_execution_time: " . ini_get("max_execution_time") . "s<br>";
        echo "GD Library: " .
            (function_exists("imagecreatefromjpeg")
                ? "✅ ENABLED"
                : "❌ DISABLED") .
            "<br>";
        echo "</div>";

        echo "<div class='debug'>";
        echo "<h3>📤 UPLOAD DEBUG INFO</h3>";

        if (isset($_FILES["image_url"])) {
            $file = $_FILES["image_url"];
            echo "<strong>File Information:</strong><br>";
            echo "Name: " . ($file["name"] ?? "NULL") . "<br>";
            echo "Type: " . ($file["type"] ?? "NULL") . "<br>";
            echo "Size: " . ($file["size"] ?? 0) . " bytes<br>";
            echo "Temp Name: " . ($file["tmp_name"] ?? "NULL") . "<br>";
            echo "Error Code: " . ($file["error"] ?? "NULL") . " - ";

            // Decode error message
            $error_codes = [
                0 => "UPLOAD_ERR_OK - No error",
                1 => "UPLOAD_ERR_INI_SIZE - File too big (php.ini)",
                2 => "UPLOAD_ERR_FORM_SIZE - File too big (HTML form)",
                3 => "UPLOAD_ERR_PARTIAL - Partial upload",
                4 => "UPLOAD_ERR_NO_FILE - No file uploaded",
                6 => "UPLOAD_ERR_NO_TMP_DIR - No temp directory",
                7 => "UPLOAD_ERR_CANT_WRITE - Can't write to disk",
                8 => "UPLOAD_ERR_EXTENSION - PHP extension stopped upload",
            ];
            echo ($error_codes[$file["error"]] ?? "Unknown error") . "<br>";

            // Cek temp file
            $tempFile = $file["tmp_name"] ?? "";
            if ($tempFile && file_exists($tempFile)) {
                echo "✅ Temp file EXISTS at: " . $tempFile . "<br>";
                echo "Temp file size: " . filesize($tempFile) . " bytes<br>";
                echo "Is readable: " .
                    (is_readable($tempFile) ? "✅ YES" : "❌ NO") .
                    "<br>";
                echo "Is writable: " .
                    (is_writable($tempFile) ? "✅ YES" : "❌ NO") .
                    "<br>";

                // Cek MIME type dengan finfo
                if (function_exists("finfo_open")) {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime = finfo_file($finfo, $tempFile);
                    finfo_close($finfo);
                    echo "Actual MIME type: " . $mime . "<br>";
                }
            } else {
                echo "❌ Temp file DOES NOT EXIST<br>";
            }
        } else {
            echo "❌ No 'image_url' in FILES array<br>";
        }

        echo "<br><strong>POST Data (form):</strong><br>";
        echo "<pre>";
        foreach ($_POST as $key => $value) {
            echo htmlspecialchars($key) .
                ": " .
                htmlspecialchars($value) .
                "\n";
        }
        echo "</pre>";
        echo "</div>";
        // ========== END DEBUG ==========

        // Ambil data dari form
        $name = trim($_POST["name"] ?? "");
        $category_id = trim($_POST["category_id"] ?? "");
        $price = trim($_POST["price"] ?? "0");
        $stock = trim($_POST["stock"] ?? "0");
        $short_description = trim($_POST["short_description"] ?? "");
        $description = trim($_POST["description"] ?? "");
        $image_url_text = trim($_POST["image_url_text"] ?? "");

        // Validasi data
        $errors = [];
        if (empty($name)) {
            $errors[] = "Nama produk harus diisi";
        }
        if (empty($category_id)) {
            $errors[] = "Kategori harus dipilih";
        }
        if (!is_numeric($price) || $price <= 0) {
            $errors[] = "Harga harus berupa angka dan lebih dari 0";
        }
        if (!is_numeric($stock) || $stock < 0) {
            $errors[] = "Stok harus berupa angka dan tidak negatif";
        }

        if (!empty($errors)) {
            $_SESSION["error"] = implode("<br>", $errors);
            header("Location: add_product.php");
            exit();
        }

        // Konversi tipe data
        $price = (int) $price;
        $stock = (int) $stock;

        // ========== SIMPLE IMAGE UPLOAD (TANPA COMPRESS) ==========
        $image_url = "";

        echo "<div class='info'>";
        echo "<strong>🖼️ SIMPLE IMAGE PROCESSING:</strong><br>";

        // Prioritas 1: Upload file - VERSI SEDERHANA
        if (isset($_FILES["image_url"]) && $_FILES["image_url"]["error"] == 0) {
            $tempFile = $_FILES["image_url"]["tmp_name"];
            $fileSize = $_FILES["image_url"]["size"];
            $fileType = $_FILES["image_url"]["type"];

            echo "Temp file: " . $tempFile . "<br>";
            echo "File exists: " .
                (file_exists($tempFile) ? "✅ YES" : "❌ NO") .
                "<br>";

            if (file_exists($tempFile)) {
                echo "✅ File exists, attempting to read...<br>";

                // VERSI 1: Coba baca langsung
                $image_data = @file_get_contents($tempFile);
                if ($image_data !== false) {
                    echo "✅ File read successfully: " .
                        strlen($image_data) .
                        " bytes<br>";

                    // Encode ke base64
                    $base64_data = base64_encode($image_data);
                    echo "Base64 length: " .
                        strlen($base64_data) .
                        " chars<br>";

                    // Batasi max size untuk database
                    if (strlen($base64_data) < 500000) {
                        // ~500KB max
                        $image_url =
                            "data:" . $fileType . ";base64," . $base64_data;
                        echo "✅ Base64 created! Total length: " .
                            strlen($image_url) .
                            " chars<br>";
                    } else {
                        echo "⚠️ Base64 terlalu panjang (>500K chars)<br>";
                        // Coba compress sederhana
                        echo "🔄 Trying simple compression...<br>";
                        $image_url = createSimpleBase64($tempFile, $fileType);
                    }
                } else {
                    echo "❌ Failed to read file<br>";
                    $error = error_get_last();
                    echo "Error: " . ($error["message"] ?? "Unknown") . "<br>";
                }
            } else {
                echo "❌ Temp file doesn't exist<br>";
                $image_url = createSVGPlaceholder($name, "NO TEMP FILE");
            }
        }
        // Prioritas 2: URL dari input
        elseif (!empty($image_url_text)) {
            if (filter_var($image_url_text, FILTER_VALIDATE_URL)) {
                $image_url = $image_url_text;
                echo "✅ Menggunakan URL dari input<br>";
            } else {
                echo "❌ URL tidak valid<br>";
            }
        }

        // Jika masih kosong, gunakan SVG placeholder
        if (empty($image_url)) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ NO VALID IMAGE FOUND</strong><br>";
            $image_url = createSVGPlaceholder($name);
            echo "Menggunakan SVG placeholder<br>";
            echo "</div>";
        }

        echo "</div>";

        // Handle gambar tambahan
        $product_images = [];
        $images_text = $_POST["product_images"] ?? "";
        if (!empty($images_text)) {
            $urls = explode(",", $images_text);
            foreach ($urls as $url) {
                $url = trim($url);
                if (!empty($url)) {
                    if (
                        filter_var($url, FILTER_VALIDATE_URL) ||
                        strpos($url, "data:image") === 0
                    ) {
                        $product_images[] = $url;
                    }
                }
            }
        }

        // Siapkan data untuk Supabase
        $product_data = [
            "name" => $name,
            "category_id" => $category_id,
            "price" => $price,
            "stock" => $stock,
            "image_url" => $image_url,
            "short_description" => $short_description,
            "description" => $description,
            "likes_count" => 0,
            "created_at" => date("Y-m-d H:i:s"),
        ];

        // Tambahkan product_images hanya jika ada
        if (!empty($product_images)) {
            $product_data["product_images"] =
                "{" . implode(",", $product_images) . "}";
        }

        echo "<div class='info'>";
        echo "<strong>📦 FINAL DATA FOR SUPABASE:</strong><br>";
        echo "Image URL starts with: " . substr($image_url, 0, 50) . "...<br>";
        echo "Image URL length: " . strlen($image_url) . " chars<br>";
        echo "Image is base64: " .
            (strpos($image_url, "data:image") === 0 ? "✅ YES" : "❌ NO") .
            "<br>";
        echo "</div>";

        // Insert ke database
        echo "<div class='info'>";
        echo "<strong>🚀 Sending to Supabase...</strong><br>";

        $result = supabase("cust_products", "POST", $product_data);

        echo "<strong>📡 Supabase Response:</strong><br>";
        echo "<div class='debug'>";
        echo "Success: " . ($result["success"] ? "✅ YES" : "❌ NO") . "<br>";
        echo "HTTP Code: " . ($result["code"] ?? "N/A") . "<br>";
        echo "Error: " . ($result["error"] ?? "None") . "<br>";
        if (!empty($result["raw"])) {
            echo "Raw Response:<br>";
            echo "<pre>" . htmlspecialchars($result["raw"]) . "</pre>";
        }
        echo "</div>";
        echo "</div>";

        if (!$result["success"] || !empty($result["error"])) {
            throw new Exception(
                "Gagal menyimpan produk: " .
                    ($result["error"] ?? "Unknown error"),
            );
        }

        // SUCCESS!
        echo "<div class='success'>";
        echo "<h2>✅ PRODUK BERHASIL DITAMBAHKAN!</h2>";
        echo "<p><strong>Status Gambar:</strong> " .
            (strpos($image_url, "data:image") === 0
                ? "Base64 Image (" . strlen($image_url) . " chars)"
                : "External URL") .
            "</p>";
        echo "</div>";

        echo "<div style='margin-top: 20px;'>";
        echo "<a class='btn' href='products.php'>📋 Lihat Daftar Produk</a>";
        echo "<a class='btn btn-secondary' href='add_product.php'>➕ Tambah Produk Baru</a>";
        echo "</div>";

        $_SESSION["success"] =
            "Produk '" . htmlspecialchars($name) . "' berhasil ditambahkan!";

        echo "<script>
            setTimeout(function() {
                window.location.href = 'products.php';
            }, 5000);
        </script>";
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<h3>❌ ERROR: " . htmlspecialchars($e->getMessage()) . "</h3>";
        echo "<p><a class='btn' href='add_product.php'>↩️ Kembali ke Form Tambah Produk</a></p>";
        echo "</div>";

        $_SESSION["error"] = $e->getMessage();
    }

    echo "</body></html>";
} else {
    header("Location: add_product.php");
    exit();
}

// Fungsi sederhana untuk membuat base64
function createSimpleBase64($tempFile, $fileType)
{
    // Coba baca file
    $image_data = @file_get_contents($tempFile);
    if ($image_data === false) {
        return createSVGPlaceholder("", "READ ERROR");
    }

    // Untuk file besar, coba resample sederhana
    if (strlen($image_data) > 1000000) {
        // > 1MB
        // Coba kompresi sederhana tanpa GD
        return createSVGPlaceholder("", "FILE TOO BIG");
    }

    $base64 = base64_encode($image_data);
    return "data:" . $fileType . ";base64," . $base64;
}

// Fungsi untuk membuat SVG placeholder
function createSVGPlaceholder($productName = "", $status = "")
{
    $text = !empty($status)
        ? $status
        : (!empty($productName)
            ? substr($productName, 0, 15)
            : "PRODUCT");
    $text = urlencode($text);
    $color = "007bff"; // Biru default

    return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300'%3E%3Crect width='100%25' height='100%25' fill='%23$color'/%3E%3Ctext x='50%25' y='50%25' font-family='Arial' font-size='16' fill='white' text-anchor='middle' dy='.3em'%3E$text%3C/text%3E%3C/svg%3E";
}
?>

// Di process_add_product.php setelah sukses insert produk:
if (!$result["success"] || !empty($result["error"])) {
    throw new Exception(...);
}

// ========== CATAT STOCK HISTORY ==========
$product_id = $result['data']['id'] ?? ''; // Ambil ID produk yang baru dibuat

if (!empty($product_id)) {
    $stock_history_data = [
        "product_id" => $product_id,
        "type" => "initial", // Stok awal
        "quantity" => $stock,
        "previous_stock" => 0,
        "new_stock" => $stock,
        "notes" => "Stok awal dari penambahan produk",
        "created_by" => $_SESSION["admin_username"] ?? "system",
        "created_at" => date("Y-m-d H:i:s")
    ];
    
    // Simpan ke stock_history
    supabase("stock_history", "POST", $stock_history_data);
    
    echo "<div class='success'>";
    echo "<p>📝 <strong>Stock History:</strong> Catatan stok awal berhasil disimpan</p>";
    echo "</div>";
}
// ========== END STOCK HISTORY ==========