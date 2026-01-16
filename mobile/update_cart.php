<?php
// update_cart.php
session_start();

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    // Inisialisasi keranjang jika belum ada
    if (!isset($_SESSION["cart"])) {
        $_SESSION["cart"] = [];
    }

    switch ($action) {
        case "add":
            $productId = $_POST["product_id"] ?? "";
            $productName = $_POST["product_name"] ?? "";
            $productPrice = floatval($_POST["product_price"] ?? 0);
            $productImage = $_POST["product_image"] ?? "";
            $quantity = intval($_POST["quantity"] ?? 1);

            if (isset($_SESSION["cart"][$productId])) {
                // Jika produk sudah ada, tambah quantity
                $_SESSION["cart"][$productId]["quantity"] += $quantity;
            } else {
                // Jika produk baru, tambah ke keranjang
                $_SESSION["cart"][$productId] = [
                    "id" => $productId,
                    "name" => $productName,
                    "price" => $productPrice,
                    "image" => $productImage,
                    "quantity" => $quantity,
                ];
            }

            $totalItems = 0;
            $cartTotal = 0;
            foreach ($_SESSION["cart"] as $item) {
                $totalItems += $item["quantity"];
                $cartTotal += $item["price"] * $item["quantity"];
            }

            echo json_encode([
                "success" => true,
                "quantity" => $_SESSION["cart"][$productId]["quantity"] ?? 0,
                "total_items" => $totalItems,
                "cart_total" => $cartTotal,
                "message" => "Produk berhasil ditambahkan ke keranjang",
            ]);
            break;

        case "update":
            $productId = $_POST["product_id"] ?? "";
            $delta = intval($_POST["delta"] ?? 0);

            if (isset($_SESSION["cart"][$productId])) {
                $newQuantity =
                    $_SESSION["cart"][$productId]["quantity"] + $delta;

                if ($newQuantity <= 0) {
                    // Hapus dari keranjang jika quantity <= 0
                    unset($_SESSION["cart"][$productId]);
                } else {
                    $_SESSION["cart"][$productId]["quantity"] = $newQuantity;
                }

                $totalItems = 0;
                $cartTotal = 0;
                foreach ($_SESSION["cart"] as $item) {
                    $totalItems += $item["quantity"];
                    $cartTotal += $item["price"] * $item["quantity"];
                }

                $message =
                    $delta > 0
                        ? "Jumlah produk berhasil ditambah"
                        : ($newQuantity > 0
                            ? "Jumlah produk berhasil dikurangi"
                            : "Produk dihapus dari keranjang");

                echo json_encode([
                    "success" => true,
                    "quantity" => $newQuantity > 0 ? $newQuantity : 0,
                    "total_items" => $totalItems,
                    "cart_total" => $cartTotal,
                    "message" => $message,
                ]);
            } else {
                echo json_encode([
                    "success" => false,
                    "message" => "Produk tidak ditemukan di keranjang",
                ]);
            }
            break;

        case "clear":
            $_SESSION["cart"] = [];
            echo json_encode([
                "success" => true,
                "total_items" => 0,
                "cart_total" => 0,
                "message" => "Keranjang berhasil dikosongkan",
            ]);
            break;

        default:
            echo json_encode([
                "success" => false,
                "message" => "Aksi tidak valid",
            ]);
    }
}
?>
