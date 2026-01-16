<?php
// admin/process_delete_product.php
session_start();
require_once "../config.php";

// Cek login
if (
    !isset($_SESSION["admin_logged_in"]) ||
    $_SESSION["admin_logged_in"] !== true
) {
    header("Location: login.php");
    exit();
}

// Enable error reporting untuk debugging
error_reporting(E_ALL);
ini_set("display_errors", 1);

// LOG: Mulai proses delete
error_log("=== DELETE PRODUCT PROCESS START ===");
error_log("POST Data: " . print_r($_POST, true));
error_log("GET Data: " . print_r($_GET, true));

if (
    $_SERVER["REQUEST_METHOD"] == "POST" ||
    $_SERVER["REQUEST_METHOD"] == "GET"
) {
    // Ambil ID produk dari POST atau GET
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $productId = $_POST["id"] ?? "";
    } else {
        $productId = $_GET["id"] ?? "";
    }

    if (empty($productId)) {
        $_SESSION["error"] = "ID produk tidak valid!";
        header("Location: products.php");
        exit();
    }

    // 1. AMBIL DATA PRODUK SEBELUM DIHAPUS (UNTUK HAPUS FILE GAMBAR)
    error_log("Mengambil data produk sebelum delete...");
    $productResult = supabase("cust_products", "GET", null, [
        "id" => "eq." . $productId,
        "select" => "id,name,image_url,product_images",
    ]);

    if (!$productResult["success"] || empty($productResult["data"])) {
        $_SESSION["error"] = "Produk tidak ditemukan!";
        header("Location: products.php");
        exit();
    }

    $product = $productResult["data"][0];
    $productName = $product["name"] ?? "Unknown Product";

    error_log("Produk yang akan dihapus:");
    error_log("ID: " . $productId);
    error_log("Nama: " . $productName);
    error_log("Image URL: " . ($product["image_url"] ?? "NULL"));
    error_log(
        "Product Images: " .
            print_r($product["product_images"] ?? "NULL", true),
    );

    // 2. HAPUS FILE GAMBAR JIKA ADA
    try {
        // Hapus gambar utama
        if (!empty($product["image_url"])) {
            $imagePath = "";

            // Cek tipe path
            if (strpos($product["image_url"], "uploads/products/") === 0) {
                // Relative path
                $imagePath = "../" . $product["image_url"];
            } elseif (strpos($product["image_url"], "uploads/") === 0) {
                // Relative path tanpa subfolder
                $imagePath = "../" . $product["image_url"];
            } elseif (strpos($product["image_url"], "../") === 0) {
                // Sudah dengan ../
                $imagePath = $product["image_url"];
            } elseif (file_exists($product["image_url"])) {
                // Absolute path
                $imagePath = $product["image_url"];
            }

            if (!empty($imagePath) && file_exists($imagePath)) {
                if (unlink($imagePath)) {
                    error_log("Gambar utama berhasil dihapus: " . $imagePath);
                } else {
                    error_log("Gagal menghapus gambar utama: " . $imagePath);
                }
            }
        }

        // Hapus gambar tambahan jika ada
        if (!empty($product["product_images"])) {
            $additionalImages = [];

            // Parse array dari PostgreSQL format
            if (is_string($product["product_images"])) {
                $str = trim($product["product_images"], "{}");
                if (!empty($str)) {
                    $additionalImages = explode(",", $str);
                    $additionalImages = array_map(function ($url) {
                        return trim($url, '"');
                    }, $additionalImages);
                }
            } elseif (is_array($product["product_images"])) {
                $additionalImages = $product["product_images"];
            }

            foreach ($additionalImages as $imgUrl) {
                if (!empty($imgUrl)) {
                    $additionalPath = "";

                    if (strpos($imgUrl, "uploads/products/additional/") === 0) {
                        $additionalPath = "../" . $imgUrl;
                    } elseif (strpos($imgUrl, "uploads/") === 0) {
                        $additionalPath = "../" . $imgUrl;
                    } elseif (strpos($imgUrl, "../") === 0) {
                        $additionalPath = $imgUrl;
                    } elseif (file_exists($imgUrl)) {
                        $additionalPath = $imgUrl;
                    }

                    if (
                        !empty($additionalPath) &&
                        file_exists($additionalPath)
                    ) {
                        if (unlink($additionalPath)) {
                            error_log(
                                "Gambar tambahan berhasil dihapus: " .
                                    $additionalPath,
                            );
                        } else {
                            error_log(
                                "Gagal menghapus gambar tambahan: " .
                                    $additionalPath,
                            );
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error saat menghapus file gambar: " . $e->getMessage());
        // Lanjutkan proses delete meski gagal hapus gambar
    }

    // 3. HAPUS PRODUK DARI DATABASE
    error_log("Menghapus produk dari database...");
    $result = supabase("cust_products", "DELETE", null, [
        "id" => "eq." . $productId,
    ]);

    error_log("Delete Result: " . print_r($result, true));

    if (isset($result["success"]) && $result["success"]) {
        $_SESSION["success"] = "Produk '{$productName}' berhasil dihapus!";
        error_log("=== DELETE PRODUCT SUCCESS ===");
    } else {
        $errorMsg = $result["error"] ?? "Unknown error";
        $_SESSION["error"] = "Gagal menghapus produk: " . $errorMsg;
        error_log("=== DELETE PRODUCT FAILED ===");
        error_log("Error: " . $errorMsg);
    }

    // 4. REDIRECT KEMBALI
    header("Location: products.php");
    exit();
} else {
    $_SESSION["error"] = "Metode request tidak valid!";
    header("Location: products.php");
    exit();
}
?>
