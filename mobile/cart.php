<?php
// cart.php - Fungsi untuk mengelola keranjang belanja
session_start();

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

/**
 * Tambah produk ke keranjang
 */
function addToCart(
    $productId,
    $productName,
    $productPrice,
    $productImage,
    $quantity = 1,
) {
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

    return count($_SESSION["cart"]);
}

/**
 * Update quantity produk di keranjang
 */
function updateCartQuantity($productId, $quantity)
{
    if (isset($_SESSION["cart"][$productId])) {
        if ($quantity <= 0) {
            // Hapus dari keranjang jika quantity <= 0
            removeFromCart($productId);
        } else {
            $_SESSION["cart"][$productId]["quantity"] = $quantity;
        }
    }
    return count($_SESSION["cart"]);
}

/**
 * Hapus produk dari keranjang
 */
function removeFromCart($productId)
{
    if (isset($_SESSION["cart"][$productId])) {
        unset($_SESSION["cart"][$productId]);
    }
    return count($_SESSION["cart"]);
}

/**
 * Kosongkan keranjang
 */
function clearCart()
{
    $_SESSION["cart"] = [];
}

/**
 * Get total items in cart
 */
function getCartCount()
{
    $total = 0;
    if (isset($_SESSION["cart"])) {
        foreach ($_SESSION["cart"] as $item) {
            $total += $item["quantity"];
        }
    }
    return $total;
}

/**
 * Get cart total price
 */
function getCartTotal()
{
    $total = 0;
    if (isset($_SESSION["cart"])) {
        foreach ($_SESSION["cart"] as $item) {
            $total += $item["price"] * $item["quantity"];
        }
    }
    return $total;
}

/**
 * Get all cart items
 */
function getCartItems()
{
    return $_SESSION["cart"] ?? [];
}
?>
