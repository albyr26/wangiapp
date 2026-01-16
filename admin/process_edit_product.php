<?php
// process_edit_product.php
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

// Validasi input
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $_SESSION["error"] = "Metode request tidak valid!";
    header("refresh:3;url=products.php");
    exit();
}

$productId = $_POST["id"] ?? "";
if (empty($productId)) {
    $_SESSION["error"] = "ID produk tidak valid!";
    header("refresh:3;url=products.php");
    exit();
}

// 1. AMBIL DATA PRODUK SEKARANG DARI DATABASE
$currentProductResult = supabase("cust_products", "GET", null, [
    "id" => "eq." . $productId,
]);

if (!$currentProductResult["success"] || empty($currentProductResult["data"])) {
    $_SESSION["error"] = "Produk tidak ditemukan!";
    header("refresh:3;url=products.php");
    exit();
}

$currentProduct = $currentProductResult["data"][0];

// 2. PERTAHANKAN GAMBAR YANG SUDAH ADA
$image_url = $currentProduct["image_url"] ?? "";
$product_images = $currentProduct["product_images"] ?? [];

// 3. HANDLE UPLOAD GAMBAR UTAMA
if (isset($_FILES["image"]) && $_FILES["image"]["error"] === UPLOAD_ERR_OK) {
    $uploadDir = "../uploads/products/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileExtension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $fileName = "product_" . time() . "_" . uniqid() . "." . $fileExtension;
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $filePath)) {
        $image_url = "uploads/products/" . $fileName;

        // Hapus gambar lama jika ada
        if (
            !empty($currentProduct["image_url"]) &&
            strpos($currentProduct["image_url"], "uploads/products/") !==
                false &&
            $currentProduct["image_url"] !== $image_url
        ) {
            $oldImagePath = "../" . $currentProduct["image_url"];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
    }
}

// 4. HANDLE GAMBAR TAMBAHAN
if (
    isset($_FILES["additional_images"]) &&
    is_array($_FILES["additional_images"]["name"])
) {
    $uploadDir = "../uploads/products/additional/";

    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $uploadedAdditionalImages = [];

    for ($i = 0; $i < count($_FILES["additional_images"]["name"]); $i++) {
        if ($_FILES["additional_images"]["error"][$i] === UPLOAD_ERR_OK) {
            $fileExtension = pathinfo(
                $_FILES["additional_images"]["name"][$i],
                PATHINFO_EXTENSION,
            );
            $fileName =
                "additional_" .
                time() .
                "_" .
                $i .
                "_" .
                uniqid() .
                "." .
                $fileExtension;
            $filePath = $uploadDir . $fileName;

            if (
                move_uploaded_file(
                    $_FILES["additional_images"]["tmp_name"][$i],
                    $filePath,
                )
            ) {
                $uploadedAdditionalImages[] =
                    "uploads/products/additional/" . $fileName;
            }
        }
    }

    // Gabungkan dengan gambar tambahan yang sudah ada
    if (!empty($uploadedAdditionalImages)) {
        if (is_array($product_images) && !empty($product_images)) {
            $product_images = array_merge(
                $product_images,
                $uploadedAdditionalImages,
            );
        } else {
            $product_images = $uploadedAdditionalImages;
        }
    }
}

// 5. TANGANI "REMOVE CURRENT IMAGE" CHECKBOX
if (
    isset($_POST["remove_current_image"]) &&
    $_POST["remove_current_image"] == "1"
) {
    $image_url = "";

    // Hapus file fisik jika ada
    if (
        !empty($currentProduct["image_url"]) &&
        strpos($currentProduct["image_url"], "uploads/products/") !== false
    ) {
        $oldImagePath = "../" . $currentProduct["image_url"];
        if (file_exists($oldImagePath)) {
            unlink($oldImagePath);
        }
    }
}

// 6. TANGANI "REMOVE ADDITIONAL IMAGES" (jika ada)
if (
    isset($_POST["remove_additional_images"]) &&
    is_array($_POST["remove_additional_images"])
) {
    $imagesToRemove = $_POST["remove_additional_images"];

    if (is_array($product_images)) {
        // Hapus file fisik terlebih dahulu
        foreach ($imagesToRemove as $imageToRemove) {
            if (
                !empty($imageToRemove) &&
                strpos($imageToRemove, "uploads/products/") !== false
            ) {
                $imagePath = "../" . $imageToRemove;
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
        }

        // Filter array untuk menghapus gambar yang dipilih
        $filteredImages = [];
        foreach ($product_images as $img) {
            if (!in_array($img, $imagesToRemove)) {
                $filteredImages[] = $img;
            }
        }

        $product_images = $filteredImages;
    }
}

// 7. PREPARE DATA UNTUK UPDATE
$data = [
    "name" => $_POST["name"] ?? "",
    "category_id" => $_POST["category_id"] ?? null,
    "price" => $_POST["price"] ?? 0,
    "stock" => $_POST["stock"] ?? 0,
    "short_description" => $_POST["short_description"] ?? "",
    "description" => $_POST["description"] ?? "",
    "updated_at" => date("Y-m-d H:i:s"),
];

// 8. HANYA UPDATE image_url JIKA BERUBAH
if ($image_url !== ($currentProduct["image_url"] ?? "")) {
    $data["image_url"] = $image_url;
}

// 9. HANYA UPDATE product_images JIKA BERUBAH
if ($product_images !== ($currentProduct["product_images"] ?? [])) {
    $data["product_images"] = $product_images;
}

// 10. EXECUTE UPDATE KE DATABASE
$updateResult = supabase("cust_products", "PATCH", $data, [
    "id" => "eq." . $productId,
]);

// 11. HANDLE RESPONSE - REDIRECT KE DAFTAR PRODUK DENGAN JEDA WAKTU
if ($updateResult["success"]) {
    $_SESSION["success"] = "Produk berhasil diperbarui!";

    // Tampilkan pesan sukses dengan jeda 3 detik sebelum redirect
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Berhasil Diperbarui</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }
            .message-box {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
            }
            .success-icon {
                color: #28a745;
                font-size: 48px;
                margin-bottom: 20px;
            }
            h2 {
                color: #28a745;
                margin-bottom: 10px;
            }
            .countdown {
                color: #666;
                font-size: 14px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <div class="success-icon">✓</div>
            <h2>Berhasil!</h2>
            <p>Produk berhasil diperbarui.</p>
            <p class="countdown">Redirect ke halaman produk dalam <span id="countdown">3</span> detik...</p>
        </div>

        <script>
            // Countdown timer
            let seconds = 3;
            const countdownElement = document.getElementById("countdown");

            const countdown = setInterval(() => {
                seconds--;
                countdownElement.textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(countdown);
                }
            }, 200);

            // Redirect setelah 3 detik
            setTimeout(() => {
                window.location.href = "products.php";
            }, 200);
        </script>
    </body>
    </html>';

    // Juga tambahkan header refresh untuk backup
    header("refresh:3;url=products.php");
    exit();
} else {
    $_SESSION["error"] =
        "Gagal memperbarui produk: " .
        ($updateResult["error"] ?? "Unknown error");

    // Tampilkan pesan error dengan jeda 3 detik sebelum redirect kembali
    echo '<!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Gagal Diperbarui</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
                background-color: #f5f5f5;
            }
            .message-box {
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                text-align: center;
                max-width: 400px;
            }
            .error-icon {
                color: #dc3545;
                font-size: 48px;
                margin-bottom: 20px;
            }
            h2 {
                color: #dc3545;
                margin-bottom: 10px;
            }
            .countdown {
                color: #666;
                font-size: 14px;
                margin-top: 20px;
            }
        </style>
    </head>
    <body>
        <div class="message-box">
            <div class="error-icon">✗</div>
            <h2>Gagal!</h2>
            <p>Gagal memperbarui produk.</p>
            <p class="countdown">Redirect ke halaman edit produk dalam <span id="countdown">3</span> detik...</p>
        </div>

        <script>
            // Countdown timer
            let seconds = 3;
            const countdownElement = document.getElementById("countdown");

            const countdown = setInterval(() => {
                seconds--;
                countdownElement.textContent = seconds;

                if (seconds <= 0) {
                    clearInterval(countdown);
                }
            }, 200);

            // Redirect setelah 3 detik
            setTimeout(() => {
                window.location.href = "edit_product.php?id=' .
        $productId .
        '";
            }, 200);
        </script>
    </body>
    </html>';

    // Juga tambahkan header refresh untuk backup
    header("refresh:3;url=edit_product.php?id=" . $productId);
    exit();
}
