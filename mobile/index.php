<?php
// index.php - Halaman utama e-commerce parfum
require_once "../config.php";
//session_start(); // Tambahkan ini untuk session

// Ambil kategori dari database
$categoriesResult = supabase("cust_categories", "GET", null, [
    "select" => "id,name",
    "order" => "name.asc",
    "limit" => 10,
]);

$categories = [];
if (isset($categoriesResult["success"]) && $categoriesResult["success"]) {
    $categories = $categoriesResult["data"] ?? [];
}

// Ambil produk dari database
$productsResult = supabase("cust_products", "GET", null, [
    "select" =>
        "id,name,price,likes_count,image_url,short_description,category_id,cust_categories(name)",
    "order" => "created_at.desc",
    "limit" => 20,
]);

$products = [];
if (isset($productsResult["success"]) && $productsResult["success"]) {
    $products = $productsResult["data"] ?? [];
}

// Get URL parameters for filtering
$categoryFilter = $_GET["category"] ?? "";
$searchQuery = $_GET["search"] ?? "";

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

// Fungsi untuk menghitung total items di keranjang
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

// Fungsi untuk menghitung total harga di keranjang
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

// Fungsi untuk mendapatkan semua item di keranjang
function getCartItems()
{
    return $_SESSION["cart"] ?? [];
}

// Cek apakah produk ada di keranjang
function isProductInCart($productId)
{
    return isset($_SESSION["cart"][$productId]);
}

// Get quantity produk di keranjang
function getCartQuantity($productId)
{
    return $_SESSION["cart"][$productId]["quantity"] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parfum Store - GPS Hybrid</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        /* TAMBAHAN UNTUK CART */
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background-color: #ff4757;
            color: white;
            font-size: 12px;
            font-weight: bold;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .header-icons {
            position: relative;
            display: flex;
            gap: 20px;
        }

        .icon-link {
            position: relative;
            color: inherit;
            text-decoration: none;
        }

        /* Quantity Selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            border: 1px solid #dbdbdb;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s;
        }

        .qty-btn:hover {
            background-color: #f5f5f5;
        }

        .qty-display {
            min-width: 30px;
            text-align: center;
            font-weight: 600;
            font-size: 16px;
        }

        .add-to-cart-btn {
            background-color: #0095f6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            flex: 1;
        }

        .add-to-cart-btn:hover {
            background-color: #0081d6;
        }

        .add-to-cart-btn.added {
            background-color: #4caf50;
        }

        .add-to-cart-btn.added:hover {
            background-color: #45a049;
        }

        /* Cart Preview Modal */
        .cart-preview {
            display: none;
            position: fixed;
            top: 60px;
            right: 16px;
            width: 320px;
            max-height: 400px;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            overflow: hidden;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .cart-preview-header {
            padding: 16px;
            border-bottom: 1px solid #dbdbdb;
            font-weight: 600;
            font-size: 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-preview-body {
            max-height: 300px;
            overflow-y: auto;
        }

        .cart-preview-item {
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-preview-img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
        }

        .cart-preview-details {
            flex: 1;
        }

        .cart-preview-name {
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .cart-preview-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cart-preview-qty-btn {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 1px solid #dbdbdb;
            background-color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 12px;
        }

        .cart-preview-qty-display {
            min-width: 20px;
            text-align: center;
        }

        .cart-preview-price {
            font-weight: 600;
            color: #f83e6b;
            font-size: 14px;
        }

        .cart-preview-footer {
            padding: 16px;
            border-top: 1px solid #dbdbdb;
        }

        .cart-preview-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-weight: 600;
        }

        .cart-preview-checkout {
            width: 100%;
            padding: 12px;
            background-color: #0095f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
        }

        .cart-preview-checkout:hover {
            background-color: #0081d6;
        }

        .cart-preview-empty {
            padding: 40px 20px;
            text-align: center;
            color: #666;
        }

        .cart-preview-empty i {
            font-size: 40px;
            margin-bottom: 10px;
            color: #ccc;
        }

        /* Checkout Section Revisi */
        .checkout-section {
            padding: 16px;
            background-color: white;
            border-top: 1px solid #dbdbdb;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .price-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .checkout-btns {
            display: flex;
            gap: 10px;
        }

        .checkout-btns button {
            flex: 1;
        }

        /* Floating Checkout Card Revisi */
        .checkout-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background-color: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 10px;
        }

        .checkout-item-img {
            width: 60px;
            height: 60px;
            border-radius: 8px;
            background-size: cover;
            background-position: center;
        }

        .checkout-item-details {
            flex: 1;
        }

        .checkout-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .checkout-item-qty {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkout-item-price {
            font-weight: 600;
            color: #f83e6b;
        }

        /* GPS Revisi */
        .gps-methods {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .gps-method-btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #dbdbdb;
            background-color: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .gps-method-btn:hover {
            background-color: #f5f5f5;
        }

        .gps-method-btn.active {
            border-color: #0095f6;
            background-color: #f0f8ff;
            color: #0095f6;
        }

        .gps-method-icon {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .gps-method-name {
            font-weight: 600;
            font-size: 13px;
        }

        /* Sembunyikan IP Location dan City Selection */
        .gps-method-btn[data-method="ip"],
        .gps-method-btn[data-method="city"],
        .city-selection {
            display: none !important;
        }

        /* Tombol hapus semua di keranjang */
        .clear-cart-btn {
            padding: 8px 16px;
            background-color: #ff4757;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            margin-left: 10px;
        }

        .clear-cart-btn:hover {
            background-color: #ff2e43;
        }

        .clear-cart-btn:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        /* Loading untuk GPS */
        .gps-loading {
            padding: 20px;
            text-align: center;
            color: #666;
            font-size: 14px;
        }

        .gps-loading i {
            margin-right: 8px;
            color: #0095f6;
            animation: spin 1s linear infinite;
        }

        /* Additional styles for live data */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0095f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }

        .empty-state i {
            font-size: 50px;
            margin-bottom: 15px;
            color: #ccc;
        }

        /* Toast Notification - VERSI CANTIK ELEGAN */
        .toast-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            pointer-events: none;
        }

        /* Base Toast Styling */
        .toast {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 18px 30px;
            box-shadow:
                0 20px 60px rgba(0, 149, 246, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.3) inset,
                0 10px 40px rgba(255, 255, 255, 0.1) inset;
            display: flex;
            align-items: center;
            gap: 15px;
            max-width: 400px;
            min-width: 300px;
            transform: scale(0.8) translateY(20px);
            opacity: 0;
            animation: toastIn 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards;
            pointer-events: auto;
            border: 1px solid rgba(0, 149, 246, 0.2);
            position: relative;
            overflow: hidden;
        }

        /* Success Toast */
        .toast-success {
            border-left: 4px solid #4caf50;
            box-shadow: 0 20px 60px rgba(76, 175, 80, 0.25);
        }

        .toast-success .toast-icon {
            background: linear-gradient(135deg,
                rgba(76, 175, 80, 0.2),
                rgba(56, 142, 60, 0.3));
            color: #4caf50;
        }

        .toast-error {
            border-left: 4px solid #f44336;
            box-shadow: 0 20px 60px rgba(244, 67, 54, 0.25);
        }

        .toast-error .toast-icon {
            background: linear-gradient(135deg,
                rgba(244, 67, 54, 0.2),
                rgba(183, 28, 28, 0.3));
            color: #f44336;
        }

        .toast-icon {
            font-size: 28px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: loveBeat 1.2s ease-in-out infinite,
                       heartFloat 3s ease-in-out infinite;
            position: relative;
            box-shadow:
                0 8px 25px rgba(0, 0, 0, 0.15),
                0 0 20px rgba(255, 255, 255, 0.5) inset;
            cursor: pointer;
        }

        @keyframes loveBeat {
            0%, 100% {
                transform: scale(1);
                box-shadow:
                    0 8px 25px rgba(0, 0, 0, 0.15),
                    0 0 20px rgba(255, 255, 255, 0.5) inset;
            }
            50% {
                transform: scale(1.15);
                box-shadow:
                    0 12px 35px rgba(0, 0, 0, 0.25),
                    0 0 25px rgba(255, 255, 255, 0.8) inset;
            }
        }

        @keyframes heartFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-5px);
            }
        }

        .toast-content {
            flex: 1;
            color: #333;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.5);
        }

        .toast-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 3px;
            letter-spacing: 0.5px;
        }

        .toast-message {
            font-size: 14px;
            color: #666;
            line-height: 1.4;
            opacity: 0.9;
        }

        .toast-close {
            background: linear-gradient(135deg,
                rgba(0, 149, 246, 0.3),
                rgba(0, 113, 214, 0.4));
            border: none;
            color: white;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.4s ease;
            margin-left: 5px;
            box-shadow: 0 4px 15px rgba(0, 149, 246, 0.3);
        }

        .toast-close:hover {
            background: linear-gradient(135deg,
                rgba(0, 149, 246, 0.5),
                rgba(0, 113, 214, 0.6));
            transform: rotate(180deg) scale(1.1);
            box-shadow: 0 6px 20px rgba(0, 149, 246, 0.5);
        }

        @keyframes toastIn {
            0% {
                transform: scale(0.8) translateY(20px);
                opacity: 0;
                filter: blur(10px);
            }
            70% {
                transform: scale(1.05) translateY(-5px);
                opacity: 1;
                filter: blur(0);
            }
            100% {
                transform: scale(1) translateY(0);
                opacity: 1;
                filter: blur(0);
            }
        }

        @keyframes toastOut {
            0% {
                transform: scale(1) translateY(0);
                opacity: 1;
            }
            30% {
                transform: scale(1.05) translateY(-5px);
                opacity: 1;
            }
            100% {
                transform: scale(0.8) translateY(20px);
                opacity: 0;
            }
        }

        /* Responsive adjustments */
        @media (max-width: 500px) {
            .cart-preview {
                width: calc(100% - 32px);
                right: 16px;
                left: 16px;
            }

            .checkout-btns {
                flex-direction: column;
            }

            .toast {
                min-width: 280px;
                padding: 15px 20px;
                border-radius: 18px;
                margin: 0 15px;
            }

            .toast-icon {
                width: 45px;
                height: 45px;
                font-size: 24px;
            }

            body {
                box-shadow: none;
            }

            .product-slider {
                height: 300px;
            }

            .delivery-options {
                flex-direction: column;
            }

            .gps-methods {
                flex-direction: column;
            }

            .toast {
                max-width: 90%;
                left: 5%;
                right: 5%;
            }
        }

        /* STYLE ASLI YANG TETAP */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family:
                -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial,
                sans-serif;
        }

        body {
            background-color: #fafafa;
            color: #262626;
            max-width: 500px;
            margin: 0 auto;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            min-height: 100vh;
            position: relative;
        }

        /* Header */
        .header {
            position: sticky;
            top: 0;
            background-color: white;
            border-bottom: 1px solid #dbdbdb;
            padding: 12px 16px;
            z-index: 100;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .logo {
            font-weight: bold;
            font-size: 24px;
            color: #262626;
            text-decoration: none;
        }

        .logo span {
            color: #f83e6b;
        }

        /* Search Bar */
        .search-container {
            padding: 12px 16px;
            background-color: white;
            border-bottom: 1px solid #dbdbdb;
        }

        .search-bar {
            width: 100%;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #dbdbdb;
            background-color: #fafafa;
            font-size: 16px;
        }

        .search-bar:focus {
            outline: none;
            border-color: #a8a8a8;
        }

        /* Filter Categories */
        .filter-container {
            padding: 12px 16px;
            background-color: white;
            border-bottom: 1px solid #dbdbdb;
            overflow-x: auto;
            white-space: nowrap;
            display: flex;
            gap: 8px;
        }

        .filter-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #dbdbdb;
            background-color: #fafafa;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background-color: #262626;
            color: white;
            border-color: #262626;
        }

        /* Product Card */
        .product-card {
            background-color: white;
            border-radius: 8px;
            margin: 16px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .card-header {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .card-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #f83e6b;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .card-username {
            font-weight: 600;
        }

        /* Product Image Slider */
        .product-slider {
            position: relative;
            height: 350px;
            overflow: hidden;
        }

        .slider-container {
            display: flex;
            height: 100%;
            transition: transform 0.5s ease;
        }

        .product-image {
            min-width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .slider-indicators {
            position: absolute;
            bottom: 15px;
            left: 0;
            right: 0;
            display: flex;
            justify-content: center;
            gap: 6px;
        }

        .indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.5);
        }

        .indicator.active {
            background-color: white;
        }

        .slider-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(255, 255, 255, 0.7);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
        }

        .slider-nav.prev {
            left: 15px;
        }

        .slider-nav.next {
            right: 15px;
        }

        /* Card Actions */
        .card-actions {
            padding: 12px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .action-icons {
            display: flex;
            gap: 16px;
        }

        .action-icons i {
            font-size: 24px;
            cursor: pointer;
            transition: transform 0.2s, color 0.2s;
        }

        .fa-heart.fas {
            color: #ed4956 !important;
        }

        .action-icons .fa-comment,
        .action-icons .fa-paper-plane {
            display: none !important;
        }

        /* Product Info */
        .product-info {
            padding: 0 16px 12px;
        }

        .likes-count {
            font-weight: 600;
            margin-bottom: 8px;
        }

        .product-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .product-category {
            color: #8e8e8e;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .product-description {
            font-size: 15px;
            line-height: 1.4;
            margin-bottom: 8px;
        }

        .more-text {
            color: #8e8e8e;
            cursor: pointer;
            font-size: 14px;
        }

        /* Checkout Button */
        .price {
            font-weight: bold;
            font-size: 20px;
            color: #f83e6b;
        }

        .checkout-btn {
            background-color: #0095f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .checkout-btn:hover {
            background-color: #0081d6;
        }

        /* Floating Checkout Card */
        .floating-card {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            max-width: 500px;
            margin: 0 auto;
            background-color: white;
            border-radius: 16px 16px 0 0;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.15);
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(100%);
            }
            to {
                transform: translateY(0);
            }
        }

        .floating-header {
            padding: 20px 16px;
            border-bottom: 1px solid #dbdbdb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .floating-title {
            font-weight: 600;
            font-size: 18px;
        }

        .close-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .floating-body {
            padding: 20px 16px;
            max-height: 70vh;
            overflow-y: auto;
        }

        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        /* Delivery Options */
        .delivery-options {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
        }

        .delivery-option {
            flex: 1;
            padding: 16px 12px;
            border-radius: 8px;
            border: 2px solid #dbdbdb;
            background-color: white;
            font-weight: 600;
            font-size: 15px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            transition: all 0.3s;
        }

        .delivery-option i {
            font-size: 24px;
            margin-bottom: 8px;
            color: #8e8e8e;
        }

        .delivery-option:hover,
        .delivery-option.active {
            border-color: #0095f6;
            background-color: #f0f8ff;
        }

        .delivery-option.active i {
            color: #0095f6;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-input {
            width: 100%;
            padding: 14px 16px;
            border-radius: 8px;
            border: 1px solid #dbdbdb;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #0095f6;
        }

        /* Product Selection in Checkout */
        .product-selection {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }

        .product-thumbnail {
            width: 50px;
            height: 50px;
            background-color: #e0e0e0;
            border-radius: 6px;
        }

        .product-details {
            flex: 1;
        }

        .product-name-text {
            font-weight: 600;
        }

        .product-size {
            color: #666;
            font-size: 14px;
        }

        .product-price {
            font-weight: 600;
            color: #f83e6b;
        }

        /* Location Selector */
        .location-selector {
            margin-bottom: 20px;
        }

        .location-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .location-title {
            font-weight: 600;
        }

        .location-buttons {
            display: flex;
            gap: 8px;
        }

        .location-btn {
            padding: 8px 12px;
            border-radius: 6px;
            border: 1px solid #dbdbdb;
            background-color: white;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .location-btn i {
            font-size: 14px;
        }

        .location-btn:hover {
            background-color: #f5f5f5;
        }

        .location-btn.active {
            background-color: #0095f6;
            color: white;
            border-color: #0095f6;
        }

        .location-input-container {
            display: none;
            margin-top: 12px;
        }

        .location-input-container.active {
            display: block;
        }

        /* GPS Methods */
        .gps-methods {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .gps-method-btn {
            flex: 1;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #dbdbdb;
            background-color: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .gps-method-btn:hover {
            background-color: #f5f5f5;
        }

        .gps-method-btn.active {
            border-color: #0095f6;
            background-color: #f0f8ff;
            color: #0095f6;
        }

        .gps-method-icon {
            font-size: 20px;
            margin-bottom: 5px;
        }

        .gps-method-name {
            font-weight: 600;
            font-size: 13px;
        }

        /* Accuracy Warning */
        .accuracy-warning {
            padding: 10px 12px;
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #856404;
            display: none;
        }

        .accuracy-warning i {
            margin-right: 8px;
            color: #ffc107;
        }

        /* GPS Search Container */
        .gps-search-container {
            display: flex;
            gap: 8px;
            margin-bottom: 12px;
        }

        .gps-search-input {
            flex: 1;
            padding: 12px 16px;
            border: 1px solid #dbdbdb;
            border-radius: 8px;
            font-size: 15px;
        }

        .gps-search-btn {
            background-color: #0095f6;
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* GPS Status */
        .gps-status {
            padding: 15px;
            background-color: #f0f8ff;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
            align-items: center;
            gap: 12px;
            border: 1px solid #cce7ff;
        }

        .gps-status i {
            color: #0095f6;
            font-size: 20px;
        }

        .gps-location-info {
            flex: 1;
        }

        .gps-location-name {
            font-weight: 600;
            margin-bottom: 3px;
            color: #0077cc;
        }

        .gps-location-address {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        .gps-location-source {
            font-size: 12px;
            color: #888;
            font-style: italic;
        }

        .gps-use-btn {
            background-color: #0095f6;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            white-space: nowrap;
        }

        /* GPS Results */
        .gps-results {
            max-height: 250px;
            overflow-y: auto;
            border: 1px solid #dbdbdb;
            border-radius: 8px;
            margin-top: 10px;
        }

        .gps-result-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .gps-result-item:hover {
            background-color: #f9f9f9;
        }

        .gps-result-item:last-child {
            border-bottom: none;
        }

        .gps-result-name {
            font-weight: 600;
            margin-bottom: 4px;
            color: #333;
        }

        .gps-result-address {
            font-size: 14px;
            color: #666;
            margin-bottom: 4px;
        }

        .gps-result-distance {
            font-size: 13px;
            color: #0095f6;
            font-weight: 600;
        }

        /* Loading States */
        .gps-loading {
            padding: 20px;
            text-align: center;
            color: #666;
        }

        .gps-loading i {
            margin-right: 8px;
            color: #0095f6;
        }

        .spinner {
            animation: spin 1s linear infinite;
            display: inline-block;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        /* Shipping Options */
        .shipping-options {
            margin-bottom: 20px;
        }

        .shipping-title {
            font-weight: 600;
            margin-bottom: 12px;
        }

        .shipping-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .shipping-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 16px;
            border: 1px solid #dbdbdb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .shipping-item:hover,
        .shipping-item.active {
            border-color: #0095f6;
            background-color: #f0f8ff;
        }

        .shipping-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .shipping-icon {
            width: 36px;
            height: 36px;
            background-color: #e6f7ff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0095f6;
        }

        .shipping-name {
            font-weight: 600;
        }

        .shipping-estimate {
            font-size: 14px;
            color: #666;
        }

        .shipping-price {
            font-weight: 600;
            color: #f83e6b;
        }

        /* Order Summary */
        .order-summary {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .summary-title {
            font-weight: 600;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 15px;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid #ddd;
            font-size: 17px;
        }

        .total-label {
            font-weight: 600;
        }

        .total-value {
            font-weight: 700;
            color: #f83e6b;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            background-color: #0095f6;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .submit-btn:hover {
            background-color: #0081d6;
        }

        /* Success Modal */
        .success-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .success-content {
            background-color: white;
            width: 90%;
            max-width: 400px;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            animation: modalOpen 0.3s ease;
        }

        @keyframes modalOpen {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .success-icon {
            font-size: 60px;
            color: #4caf50;
            margin-bottom: 20px;
        }

        .success-title {
            font-weight: 600;
            font-size: 22px;
            margin-bottom: 10px;
        }

        .success-message {
            color: #666;
            margin-bottom: 25px;
            line-height: 1.5;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: sticky;
            bottom: 0;
            background-color: white;
            border-top: 1px solid #dbdbdb;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            z-index: 100;
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #8e8e8e;
            text-decoration: none;
            font-size: 12px;
        }

        .nav-item.active {
            color: #262626;
        }

        .nav-item i {
            font-size: 22px;
            margin-bottom: 4px;
        }

    </style>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <a href="index.php" class="logo">Parfum<span>Store</span></a>
        <div class="header-icons">
            <a href="#" class="icon-link"><i class="far fa-heart"></i></a>
            <a href="#" class="icon-link" onclick="toggleCartPreview(event)">
                <i class="fas fa-shopping-cart"></i>
                <span class="cart-count" id="cart-count"><?= getCartCount() ?></span>
            </a>
        </div>
    </header>

    <!-- Cart Preview Modal -->
    <div class="cart-preview" id="cart-preview">
        <div class="cart-preview-header">
            <span>Keranjang Belanja</span>
            <button class="clear-cart-btn" onclick="clearCart()" <?= getCartCount() ==
            0
                ? "disabled"
                : "" ?>>
                Kosongkan
            </button>
        </div>
        <div class="cart-preview-body" id="cart-preview-body">
            <?php if (getCartCount() == 0): ?>
                <div class="cart-preview-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Keranjang belanja kosong</p>
                </div>
            <?php else: ?>
                <?php foreach (getCartItems() as $item): ?>
                    <div class="cart-preview-item" data-product-id="<?= $item[
                        "id"
                    ] ?>">
                        <div class="cart-preview-img" style="background-image: url('<?= htmlspecialchars(
                            $item["image"],
                        ) ?>')"></div>
                        <div class="cart-preview-details">
                            <div class="cart-preview-name"><?= htmlspecialchars(
                                $item["name"],
                            ) ?></div>
                            <div class="cart-preview-qty">
                                <button class="cart-preview-qty-btn" onclick="updateCartQuantity('<?= $item[
                                    "id"
                                ] ?>', -1, true)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span class="cart-preview-qty-display"><?= $item[
                                    "quantity"
                                ] ?></span>
                                <button class="cart-preview-qty-btn" onclick="updateCartQuantity('<?= $item[
                                    "id"
                                ] ?>', 1, true)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="cart-preview-price">
                            Rp <?= number_format(
                                $item["price"] * $item["quantity"],
                                0,
                                ",",
                                ".",
                            ) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if (getCartCount() > 0): ?>
            <div class="cart-preview-footer">
                <div class="cart-preview-total">
                    <span>Total:</span>
                    <span id="cart-preview-total">Rp <?= number_format(
                        getCartTotal(),
                        0,
                        ",",
                        ".",
                    ) ?></span>
                </div>
                <button class="cart-preview-checkout" onclick="openCheckoutFromCart()">
                    Checkout (<?= getCartCount() ?> item)
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Search Bar -->
    <div class="search-container">
        <form action="index.php" method="GET" id="searchForm">
            <input type="text" class="search-bar" name="search" placeholder="Cari parfum favoritmu..."
                   value="<?= htmlspecialchars($searchQuery) ?>"
                   onkeypress="if(event.keyCode==13) document.getElementById('searchForm').submit()">
        </form>
    </div>

    <!-- Filter Categories -->
    <div class="filter-container">
        <button class="filter-btn <?= empty($categoryFilter) ? "active" : "" ?>"
                onclick="window.location.href='index.php'">
            Semua
        </button>

        <?php foreach ($categories as $category): ?>
        <button class="filter-btn <?= $categoryFilter == $category["id"]
            ? "active"
            : "" ?>"
                onclick="window.location.href='index.php?category=<?= $category[
                    "id"
                ] ?>'">
            <?= htmlspecialchars($category["name"]) ?>
        </button>
        <?php endforeach; ?>
    </div>

    <!-- Product Cards -->
    <?php if (empty($products)): ?>
        <div class="empty-state">
            <i class="fas fa-wine-bottle"></i>
            <h3>Belum ada produk</h3>
            <p>Silakan coba kategori lain atau cari dengan kata kunci berbeda</p>
        </div>
    <?php

        // Check if product is in cart

        // Get additional images
        // Check if product is in cart
        // Get additional images
        // Check if product is in cart

        // Get additional images
        // Check if product is in cart
        // Get additional images
        else: ?>
        <?php
        $productIndex = 1;
        foreach ($products as $product):

            if ($categoryFilter && $product["category_id"] != $categoryFilter) {
                continue;
            }

            if (
                $searchQuery &&
                stripos($product["name"], $searchQuery) === false &&
                stripos($product["short_description"], $searchQuery) === false
            ) {
                continue;
            }

            $productId = $product["id"];
            $productName = $product["name"];
            $productPrice = $product["price"] ?? 0;
            $categoryName =
                $product["cust_categories"]["name"] ?? "Tidak ada kategori";

            $mainImage =
                $product["image_url"] ??
                "https://via.placeholder.com/500x500/6c757d/ffffff?text=No+Image";

            $inCart = isset($_SESSION["cart"][$productId]);
            $cartQuantity = $inCart
                ? $_SESSION["cart"][$productId]["quantity"]
                : 0;

            $additionalImages = [];
            if (
                isset($product["product_images"]) &&
                !empty($product["product_images"])
            ) {
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
            }

            $allImages = array_merge([$mainImage], $additionalImages);
            $totalImages = count($allImages);
            ?>
        <div class="product-card" id="product<?= $productIndex ?>">
            <!-- Card Header -->
            <div class="card-header">
                <div class="card-header-left">
                    <div class="card-avatar"><?= strtoupper(
                        substr($productName, 0, 1),
                    ) ?></div>
                    <div class="card-username">ParfumStore</div>
                </div>
                <i class="fas fa-ellipsis-h"></i>
            </div>

            <!-- Product Image Slider -->
            <div class="product-slider">
                <div class="slider-container" id="slider<?= $productIndex ?>">
                    <?php foreach ($allImages as $index => $imageUrl): ?>
                    <img src="<?= htmlspecialchars($imageUrl) ?>"
                         alt="<?= htmlspecialchars($productName) ?>"
                         class="product-image"
                         onerror="this.src='https://via.placeholder.com/500x500/6c757d/ffffff?text=Image+Error'">
                    <?php endforeach; ?>
                </div>

                <!-- Slider Indicators -->
                <div class="slider-indicators" id="indicators<?= $productIndex ?>">
                    <?php for ($i = 0; $i < $totalImages; $i++): ?>
                    <div class="indicator <?= $i === 0
                        ? "active"
                        : "" ?>"></div>
                    <?php endfor; ?>
                </div>

                <!-- Slider Navigation -->
                <?php if ($totalImages > 1): ?>
                <button class="slider-nav prev" onclick="prevSlide(<?= $productIndex ?>, <?= $totalImages ?>)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="slider-nav next" onclick="nextSlide(<?= $productIndex ?>, <?= $totalImages ?>)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <?php endif; ?>
            </div>

            <!-- Card Actions -->
            <div class="card-actions">
                <div class="action-icons">
                    <i class="far fa-heart like-btn"
                       data-product-id="<?= $productId ?>"
                       onclick="toggleLike(this, '<?= $productId ?>')"></i>
                </div>
                <i class="far fa-bookmark"></i>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <div class="likes-count" id="likes-count-<?= $productId ?>">
                    <?= number_format($product["likes_count"] ?? 0) ?> suka
                </div>
                <div class="product-name"><?= htmlspecialchars(
                    $productName,
                ) ?></div>
                <div class="product-category">Kategori: <?= htmlspecialchars(
                    $categoryName,
                ) ?></div>
                <div class="product-description">
                    <?= htmlspecialchars(
                        $product["short_description"] ??
                            "Deskripsi tidak tersedia",
                    ) ?>
                    <?php if (!empty($product["description"])): ?>
                    <span class="more-text" onclick="toggleDescription(<?= $productIndex ?>)">selengkapnya</span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($product["description"])): ?>
                <div id="full-description<?= $productIndex ?>" style="display: none;">
                    <?= nl2br(htmlspecialchars($product["description"])) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Checkout Section -->
            <div class="checkout-section">
                <div class="price-row">
                    <div class="price">Rp <?= number_format(
                        $productPrice,
                        0,
                        ",",
                        ".",
                    ) ?></div>
                    <?php if ($inCart): ?>
                        <div class="quantity-selector" style="justify-content: flex-end;">
                            <button class="qty-btn" onclick="updateCartQuantity('<?= $productId ?>', -1)">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="qty-display" id="qty-display-<?= $productId ?>"><?= $cartQuantity ?></span>
                            <button class="qty-btn" onclick="updateCartQuantity('<?= $productId ?>', 1)">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="checkout-btns">
                    <button class="add-to-cart-btn <?= $inCart
                        ? "added"
                        : "" ?>"
                            id="cart-btn-<?= $productId ?>"
                            onclick="<?= $inCart
                                ? "removeFromCart('$productId')"
                                : "addToCart('$productId', '$productName', $productPrice, '$mainImage')" ?>">
                        <?= $inCart
                            ? '<i class="fas fa-check"></i> Di Keranjang'
                            : '<i class="fas fa-cart-plus"></i> Tambah ke Keranjang' ?>
                    </button>
                    <button class="checkout-btn" onclick="openSingleCheckout('<?= $productId ?>', '<?= htmlspecialchars(
    addslashes($productName),
) ?>', <?= $productPrice ?>, '<?= htmlspecialchars(
    addslashes($mainImage),
) ?>')">
                        Beli Langsung
                    </button>
                </div>
            </div>
        </div>
        <?php $productIndex++;
        endforeach;
        ?>
    <?php endif; ?>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="index.php" class="nav-item active">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-search"></i>
            <span>Cari</span>
        </a>
        <a href="#" class="nav-item">
            <i class="fas fa-shopping-bag"></i>
            <span>Belanja</span>
        </a>
        <a href="#" class="nav-item">
            <i class="far fa-heart"></i>
            <span>Favorit</span>
        </a>
        <a href="#" class="nav-item">
            <i class="far fa-user"></i>
            <span>Profil</span>
        </a>
    </nav>

    <!-- Floating Checkout Card -->
    <div class="overlay" id="checkout-overlay" onclick="closeCheckout()"></div>
    <div class="floating-card" id="checkout-card">
        <div class="floating-header">
            <div class="floating-title">Checkout Produk</div>
            <button class="close-btn" onclick="closeCheckout()">&times;</button>
        </div>
        <div class="floating-body">
            <div class="delivery-options">
                <div class="delivery-option active" onclick="selectDeliveryOption('pickup')" id="pickup-option">
                    <i class="fas fa-store"></i>
                    Order di Sini
                </div>
                <div class="delivery-option" onclick="selectDeliveryOption('delivery')" id="delivery-option">
                    <i class="fas fa-truck"></i>
                    Dikirim
                </div>
            </div>

            <form id="checkout-form">
                <input type="hidden" id="checkout-type" name="checkout_type" value="single">
                <input type="hidden" id="checkout-product-id" name="product_id">

                <!-- Cart Items List (untuk multiple products) -->
                <div class="form-group" id="cart-items-list" style="display: none;">
                    <div class="form-label">Produk dalam Keranjang</div>
                    <!-- Items will be populated by JavaScript -->
                </div>

                <!-- Single Product Info (untuk single checkout) -->
                <div class="form-group" id="single-product-info">
                    <div class="form-label">Produk yang dipilih</div>
                    <div class="product-selection">
                        <div class="product-thumbnail" id="checkout-thumbnail"></div>
                        <div class="product-details">
                            <div class="product-name-text" id="checkout-product-name"></div>
                        </div>
                        <div class="product-price" id="checkout-product-price"></div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="form-group">
                    <label class="form-label" for="customer-name">Nama Lengkap</label>
                    <input type="text" id="customer-name" class="form-input" placeholder="Masukkan nama lengkap" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="customer-phone">Nomor WhatsApp</label>
                    <input type="tel" id="customer-phone" class="form-input" placeholder="Contoh: 081234567890" required>
                </div>

                <!-- Address Section -->
                <div class="form-group location-selector">
                    <div class="location-header">
                        <div class="location-title">Alamat</div>
                        <div class="location-buttons">
                            <button type="button" class="location-btn active" id="manual-btn" onclick="toggleAddressInput('manual')">
                                <i class="fas fa-edit"></i> Manual
                            </button>
                            <button type="button" class="location-btn" id="gps-btn" onclick="toggleAddressInput('gps')">
                                <i class="fas fa-map-marker-alt"></i> GPS Browser
                            </button>
                        </div>
                    </div>

                    <!-- Manual Address Input -->
                    <div class="location-input-container active" id="manual-address">
                        <textarea id="customer-address" class="form-input" placeholder="Masukkan alamat lengkap (jalan, RT/RW, kelurahan, kecamatan, kota)" rows="3" required></textarea>
                    </div>

                    <!-- GPS Address Input -->
                    <div class="location-input-container" id="gps-address">
                        <!-- Accuracy Warning -->
                        <div class="accuracy-warning" id="accuracy-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span id="warning-text">Lokasi mungkin kurang akurat. Periksa alamat sebelum digunakan.</span>
                        </div>

                        <!-- GPS Methods (HANYA GPS DEVICE) -->
                        <div class="gps-methods">
                            <div class="gps-method-btn active" data-method="gps" onclick="selectGPSMethod('gps')">
                                <div class="gps-method-icon">
                                    <i class="fas fa-satellite"></i>
                                </div>
                                <div class="gps-method-name">GPS Device</div>
                            </div>
                            <!-- IP Location dan City Selection dihilangkan -->
                        </div>

                        <!-- GPS Status -->
                        <div class="gps-status" id="gps-status">
                            <i class="fas fa-map-marker-alt"></i>
                            <div class="gps-location-info">
                                <div class="gps-location-name" id="gps-location-name">Lokasi Anda</div>
                                <div class="gps-location-address" id="gps-location-address">Mendeteksi lokasi...</div>
                                <div class="gps-location-source" id="gps-location-source">Sumber: GPS Device</div>
                            </div>
                            <button type="button" class="gps-use-btn" onclick="useGPSLocation()">Gunakan</button>
                        </div>

                        <!-- GPS Search -->
                        <div class="gps-search-container">
                            <input type="text" id="gps-search" class="gps-search-input" placeholder="Cari alamat, tempat, atau daerah...">
                            <button type="button" class="gps-search-btn" onclick="searchAddress()">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>

                        <!-- GPS Results -->
                        <div class="gps-results" id="gps-results">
                            <div class="gps-loading">
                                <i class="fas fa-spinner spinner"></i>
                                Memuat data lokasi...
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Shipping Options -->
                <div class="shipping-options" id="shipping-section" style="display: none;">
                    <div class="shipping-title">Pilih Pengiriman</div>
                    <div class="shipping-list">
                        <div class="shipping-item active" onclick="selectShipping('regular', 15000)">
                            <div class="shipping-info">
                                <div class="shipping-icon">
                                    <i class="fas fa-bike"></i>
                                </div>
                                <div>
                                    <div class="shipping-name">Reguler</div>
                                    <div class="shipping-estimate">1-2 hari kerja</div>
                                </div>
                            </div>
                            <div class="shipping-price">Rp 15.000</div>
                        </div>
                        <div class="shipping-item" onclick="selectShipping('express', 30000)">
                            <div class="shipping-info">
                                <div class="shipping-icon">
                                    <i class="fas fa-motorcycle"></i>
                                </div>
                                <div>
                                    <div class="shipping-name">Express</div>
                                    <div class="shipping-estimate">Hari ini</div>
                                </div>
                            </div>
                            <div class="shipping-price">Rp 30.000</div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="order-summary">
                    <div class="summary-title">Ringkasan Pesanan</div>
                    <div class="summary-row">
                        <div class="summary-label">Harga Produk</div>
                        <div class="summary-value" id="summary-product-price"></div>
                    </div>
                    <div class="summary-row" id="shipping-row" style="display: none;">
                        <div class="summary-label">Ongkos Kirim</div>
                        <div class="summary-value" id="summary-shipping">Rp 15.000</div>
                    </div>
                    <div class="summary-total">
                        <div class="total-label">Total Pembayaran</div>
                        <div class="total-value" id="summary-total"></div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="button" class="submit-btn" onclick="submitOrder()">Konfirmasi Pesanan</button>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="success-modal" id="success-modal">
        <div class="success-content">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-title">Pesanan Berhasil!</div>
            <div class="success-message" id="success-message">
                Pesanan Anda telah berhasil dibuat. Kami akan menghubungi Anda via WhatsApp untuk konfirmasi lebih lanjut.
            </div>
            <button class="submit-btn" onclick="closeSuccessModal()">Tutup</button>
        </div>
    </div>

    <script>
    // Global variables
    let currentProducts = []; // Untuk multiple products
    let currentProductId = '';
    let currentProductName = '';
    let currentProductPrice = 0;
    let currentProductImage = '';
    let deliveryOption = 'pickup';
    let shippingCost = 15000;
    let shippingType = 'regular';
    let gpsMethod = 'gps';
    let checkoutType = 'single'; // 'cart' atau 'single'

    // Slider state
    const sliders = {};

    // Initialize when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize product sliders
        initializeSliders();
    });

    function initializeSliders() {
        // Initialize slider state for each product
        document.querySelectorAll('.product-slider').forEach((slider, index) => {
            const productIndex = index + 1;
            const totalSlides = slider.querySelectorAll('.product-image').length;
            sliders[productIndex] = { currentIndex: 0, totalSlides: totalSlides };
        });
    }

    // Slider functions
    function nextSlide(productId, totalSlides) {
        const slider = sliders[productId];
        if (slider.currentIndex < totalSlides - 1) {
            slider.currentIndex++;
        } else {
            slider.currentIndex = 0;
        }
        updateSlider(productId);
    }

    function prevSlide(productId, totalSlides) {
        const slider = sliders[productId];
        if (slider.currentIndex > 0) {
            slider.currentIndex--;
        } else {
            slider.currentIndex = totalSlides - 1;
        }
        updateSlider(productId);
    }

    function updateSlider(productId) {
        const slider = sliders[productId];
        const sliderContainer = document.getElementById(`slider${productId}`);
        const indicators = document.getElementById(`indicators${productId}`).children;

        if (!sliderContainer || !indicators) return;

        sliderContainer.style.transform = `translateX(-${slider.currentIndex * 100}%)`;

        for (let i = 0; i < indicators.length; i++) {
            if (i === slider.currentIndex) {
                indicators[i].classList.add('active');
            } else {
                indicators[i].classList.remove('active');
            }
        }
    }

    // Toggle product description
    function toggleDescription(productId) {
        const fullDescription = document.getElementById(`full-description${productId}`);
        const moreText = document.querySelector(`#product${productId} .more-text`);

        if (!fullDescription || !moreText) return;

        if (fullDescription.style.display === 'none') {
            fullDescription.style.display = 'block';
            moreText.textContent = ' lebih sedikit';
        } else {
            fullDescription.style.display = 'none';
            moreText.textContent = 'selengkapnya';
        }
    }

    // Cart functions
    async function addToCart(productId, productName, productPrice, productImage) {
        try {
            const response = await fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'add',
                    product_id: productId,
                    product_name: productName,
                    product_price: productPrice,
                    product_image: productImage,
                    quantity: 1
                })
            });

            const data = await response.json();

            if (data.success) {
                updateCartUI(productId, data.quantity, data.total_items, data.cart_total);
                updateCartPreview();
                showToast('✅ Produk berhasil ditambahkan ke keranjang!', 'success');
            } else {
                showToast('❌ Gagal menambahkan ke keranjang', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('⚠️ Gagal terhubung ke server', 'error');
        }
    }

    async function updateCartQuantity(productId, delta, fromPreview = false) {
        try {
            const response = await fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'update',
                    product_id: productId,
                    delta: delta
                })
            });

            const data = await response.json();

            if (data.success) {
                if (data.quantity === 0) {
                    // Product removed from cart
                    removeCartItemUI(productId);
                } else {
                    updateCartUI(productId, data.quantity, data.total_items, data.cart_total);
                }

                if (!fromPreview) {
                    updateCartPreview();
                }

                const message = delta > 0 ? 'Jumlah produk ditambah' :
                              (data.quantity > 0 ? 'Jumlah produk dikurangi' : 'Produk dihapus dari keranjang');
                showToast(`✅ ${message}!`, 'success');
            } else {
                showToast(data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('⚠️ Gagal terhubung ke server', 'error');
        }
    }

    async function removeFromCart(productId) {
        await updateCartQuantity(productId, 0);
    }

    function updateCartUI(productId, quantity, totalItems, cartTotal) {
        // Update cart count
        document.getElementById('cart-count').textContent = totalItems;

        // Update product card
        const qtyDisplay = document.getElementById(`qty-display-${productId}`);
        const cartBtn = document.getElementById(`cart-btn-${productId}`);

        if (qtyDisplay) {
            qtyDisplay.textContent = quantity;
        }

        if (cartBtn) {
            if (quantity > 0) {
                cartBtn.innerHTML = '<i class="fas fa-check"></i> Di Keranjang';
                cartBtn.classList.add('added');
                cartBtn.onclick = () => removeFromCart(productId);
            } else {
                cartBtn.innerHTML = '<i class="fas fa-cart-plus"></i> Tambah ke Keranjang';
                cartBtn.classList.remove('added');
                cartBtn.onclick = () => addToCart(
                    productId,
                    cartBtn.dataset.productName || '',
                    parseFloat(cartBtn.dataset.productPrice) || 0,
                    cartBtn.dataset.productImage || ''
                );
            }
        }
    }

    function removeCartItemUI(productId) {
        const cartItem = document.querySelector(`.cart-preview-item[data-product-id="${productId}"]`);
        if (cartItem) {
            cartItem.remove();
        }

        // Update product card button
        const cartBtn = document.getElementById(`cart-btn-${productId}`);
        if (cartBtn) {
            cartBtn.innerHTML = '<i class="fas fa-cart-plus"></i> Tambah ke Keranjang';
            cartBtn.classList.remove('added');
            cartBtn.onclick = () => addToCart(
                productId,
                cartBtn.dataset.productName || '',
                parseFloat(cartBtn.dataset.productPrice) || 0,
                cartBtn.dataset.productImage || ''
            );
        }
    }

    async function clearCart() {
        if (confirm('Yakin ingin mengosongkan keranjang?')) {
            try {
                const response = await fetch('update_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'clear'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('cart-count').textContent = '0';
                    document.querySelectorAll('.cart-preview-item').forEach(item => item.remove());
                    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
                        btn.innerHTML = '<i class="fas fa-cart-plus"></i> Tambah ke Keranjang';
                        btn.classList.remove('added');
                    });
                    updateCartPreview();
                    showToast('🗑️ Keranjang berhasil dikosongkan!', 'success');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('⚠️ Gagal mengosongkan keranjang', 'error');
            }
        }
    }

    function toggleCartPreview(event) {
        event.preventDefault();
        const cartPreview = document.getElementById('cart-preview');
        if (cartPreview.style.display === 'block') {
            cartPreview.style.display = 'none';
        } else {
            updateCartPreview();
            cartPreview.style.display = 'block';
        }
    }

    function updateCartPreview() {
        // Update total in cart preview
        const cartTotal = document.getElementById('cart-preview-total');
        const cartCount = document.getElementById('cart-count');
        const clearBtn = document.querySelector('.clear-cart-btn');

        if (parseInt(cartCount.textContent) === 0) {
            // Show empty state
            document.getElementById('cart-preview-body').innerHTML = `
                <div class="cart-preview-empty">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Keranjang belanja kosong</p>
                </div>
            `;
            document.querySelector('.cart-preview-footer').style.display = 'none';
            if (clearBtn) clearBtn.disabled = true;
        } else {
            // Enable clear button
            if (clearBtn) clearBtn.disabled = false;
        }
    }

    // Checkout functions
    function openSingleCheckout(productId, productName, productPrice, productImage) {
        checkoutType = 'single';
        currentProductId = productId;
        currentProductName = productName;
        currentProductPrice = productPrice;
        currentProductImage = productImage;

        // Update checkout form for single product
        document.getElementById('checkout-type').value = 'single';
        document.getElementById('checkout-product-id').value = productId;
        document.getElementById('checkout-product-name').textContent = productName;
        document.getElementById('checkout-product-price').textContent = `Rp ${productPrice.toLocaleString()}`;
        document.getElementById('summary-product-price').textContent = `Rp ${productPrice.toLocaleString()}`;
        document.getElementById('summary-total').textContent = `Rp ${productPrice.toLocaleString()}`;

        // Update thumbnail
        const thumbnail = document.getElementById('checkout-thumbnail');
        thumbnail.style.backgroundImage = `url('${productImage}')`;
        thumbnail.style.backgroundSize = 'cover';
        thumbnail.style.backgroundPosition = 'center';

        // Show single product info, hide cart items
        document.getElementById('single-product-info').style.display = 'block';
        document.getElementById('cart-items-list').style.display = 'none';

        openCheckout();
    }

    async function openCheckoutFromCart() {
        checkoutType = 'cart';

        try {
            const response = await fetch('get_cart.php');
            const data = await response.json();

            if (data.success && data.items.length > 0) {
                currentProducts = data.items;

                // Update checkout form for cart
                document.getElementById('checkout-type').value = 'cart';
                document.getElementById('single-product-info').style.display = 'none';
                document.getElementById('cart-items-list').style.display = 'block';

                // Populate cart items
                populateCartItemsInCheckout(data.items);
                updateOrderSummaryForCart(data.items);

                openCheckout();
            } else {
                showToast('❌ Keranjang belanja kosong', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('⚠️ Gagal memuat keranjang', 'error');
        }
    }

    function populateCartItemsInCheckout(items) {
        const cartItemsList = document.getElementById('cart-items-list');
        if (!cartItemsList) return;

        let html = '<div class="form-label">Produk dalam Keranjang</div>';

        items.forEach(item => {
            html += `
                <div class="checkout-item">
                    <div class="checkout-item-img" style="background-image: url('${item.image}')"></div>
                    <div class="checkout-item-details">
                        <div class="checkout-item-name">${item.name}</div>
                        <div class="checkout-item-qty">
                            <span>Jumlah: ${item.quantity}</span>
                        </div>
                    </div>
                    <div class="checkout-item-price">
                        Rp ${(item.price * item.quantity).toLocaleString()}
                    </div>
                </div>
            `;
        });

        cartItemsList.innerHTML = html;
    }

    function openCheckout() {
        // Reset form
        document.getElementById('customer-name').value = '';
        document.getElementById('customer-phone').value = '';
        document.getElementById('customer-address').value = '';
        document.getElementById('gps-search').value = '';

        // Reset delivery option
        deliveryOption = 'pickup';
        document.getElementById('pickup-option').classList.add('active');
        document.getElementById('delivery-option').classList.remove('active');
        document.getElementById('shipping-section').style.display = 'none';
        document.getElementById('shipping-row').style.display = 'none';

        // Reset shipping
        shippingCost = 15000;
        shippingType = 'regular';

        // Reset address input
        toggleAddressInput('manual');

        // Clear GPS results
        const gpsResults = document.getElementById('gps-results');
        if (gpsResults) gpsResults.innerHTML = '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Memuat data lokasi...</div>';

        document.getElementById('gps-status').style.display = 'none';
        document.getElementById('accuracy-warning').style.display = 'none';

        // Show checkout card
        document.getElementById('checkout-overlay').style.display = 'block';
        document.getElementById('checkout-card').style.display = 'block';

        // Close cart preview if open
        document.getElementById('cart-preview').style.display = 'none';
    }

    // Close checkout card
    function closeCheckout() {
        document.getElementById('checkout-overlay').style.display = 'none';
        document.getElementById('checkout-card').style.display = 'none';
    }

    // Select delivery option
    function selectDeliveryOption(option) {
        deliveryOption = option;

        // Update UI
        document.getElementById('pickup-option').classList.remove('active');
        document.getElementById('delivery-option').classList.remove('active');

        if (option === 'pickup') {
            document.getElementById('pickup-option').classList.add('active');
            document.getElementById('shipping-section').style.display = 'none';
            document.getElementById('shipping-row').style.display = 'none';
        } else {
            document.getElementById('delivery-option').classList.add('active');
            document.getElementById('shipping-section').style.display = 'block';
            document.getElementById('shipping-row').style.display = 'flex';
        }

        updateOrderSummary();
    }

    // Update order summary
    function updateOrderSummary() {
        let total = checkoutType === 'single' ? currentProductPrice : calculateCartTotal();

        if (deliveryOption === 'delivery') {
            document.getElementById('summary-shipping').textContent = `Rp ${shippingCost.toLocaleString()}`;
            total += shippingCost;
        }

        document.getElementById('summary-total').textContent = `Rp ${total.toLocaleString()}`;
    }

    function updateOrderSummaryForCart(items) {
        let productTotal = calculateCartTotalFromItems(items);
        document.getElementById('summary-product-price').textContent = `Rp ${productTotal.toLocaleString()}`;
        updateOrderSummary();
    }

    function calculateCartTotal() {
        let total = 0;
        if (checkoutType === 'cart' && currentProducts.length > 0) {
            currentProducts.forEach(item => {
                total += item.price * item.quantity;
            });
        }
        return total;
    }

    function calculateCartTotalFromItems(items) {
        let total = 0;
        items.forEach(item => {
            total += item.price * item.quantity;
        });
        return total;
    }

    // Toggle like button
    function toggleLike(icon, productId) {
        const isLiked = icon.classList.contains('fas');
        const countElement = document.getElementById(`likes-count-${productId}`);

        if (!countElement) return;

        // Simpan state original
        const originalIconClass = isLiked ? 'fas' : 'far';
        const originalCount = countElement.textContent;

        // Tampilkan animasi langsung di UI
        if (isLiked) {
            // Unlike - ubah ke hati kosong
            icon.classList.remove('fas');
            icon.classList.add('far');
            // Update count di UI sementara
            const currentCount = parseInt(originalCount.replace(/[^\d]/g, '')) || 0;
            const newCount = Math.max(0, currentCount - 1);
            countElement.textContent = `${newCount.toLocaleString()} suka`;
        } else {
            // Like - ubah ke hati penuh
            icon.classList.remove('far');
            icon.classList.add('fas');
            // Update count di UI sementara
            const currentCount = parseInt(originalCount.replace(/[^\d]/g, '')) || 0;
            const newCount = currentCount + 1;
            countElement.textContent = `${newCount.toLocaleString()} suka`;
        }

        // Tambah animasi kecil
        icon.style.transform = 'scale(1.3)';
        setTimeout(() => {
            icon.style.transform = 'scale(1)';
        }, 300);

        // Kirim ke server
        updateLikeCount(icon, productId, isLiked ? -1 : 1, originalIconClass, originalCount);
    }

    // Update like count in database
    async function updateLikeCount(icon, productId, delta, originalIconClass, originalCount) {
        const countElement = document.getElementById(`likes-count-${productId}`);
        if (!countElement) return;

        try {
            // Tampilkan loading state pada icon
            icon.style.opacity = '0.6';
            icon.style.pointerEvents = 'none';

            // Kirim request ke server
            const response = await fetch('update_likes.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    delta: delta
                })
            });

            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('Gagal parse JSON:', e);
                throw new Error('Response bukan JSON valid');
            }

            if (data.success) {
                // Update UI dengan data terbaru dari server
                if (data.new_likes_count !== undefined) {
                    countElement.textContent = `${data.new_likes_count.toLocaleString()} suka`;
                }

                // Show success toast notification
                showToast(
                    delta > 0 ? '❤️ Produk berhasil disukai!' : '💔 Like berhasil dibatalkan!',
                    'success'
                );
            } else {
                console.error('Failed to update like:', data.error);
                // Revert UI change jika gagal
                revertLikeChanges(icon, countElement, delta, originalCount);
                showToast('😕 ' + (data.error || 'Gagal menyimpan like'), 'error');
            }
        } catch (error) {
            console.error('Network/Server Error:', error);
            // Revert UI change jika error
            revertLikeChanges(icon, countElement, delta, originalCount);
            showToast('⚠️ Gagal terhubung ke server', 'error');
        } finally {
            // Reset icon state
            icon.style.opacity = '1';
            icon.style.pointerEvents = 'auto';
        }
    }

    function revertLikeChanges(icon, countElement, delta, originalCount) {
        // Revert icon change
        if (delta > 0) {
            // Jika sebelumnya mencoba like (tambah), kembalikan ke unlike
            icon.classList.remove('fas');
            icon.classList.add('far');
        } else {
            // Jika sebelumnya mencoba unlike (kurang), kembalikan ke like
            icon.classList.remove('far');
            icon.classList.add('fas');
        }

        // Revert count change
        if (countElement) {
            countElement.textContent = originalCount;
        }
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        // Remove existing toast
        const existingToast = document.querySelector('.toast');
        if (existingToast) {
            existingToast.style.animation = 'toastOut 0.2s ease forwards';
            setTimeout(() => {
                if (existingToast.parentNode) {
                    existingToast.parentNode.removeChild(existingToast);
                }
            }, 200);
        }

        // Create toast container jika belum ada
        let toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            toastContainer = document.createElement('div');
            toastContainer.className = 'toast-container';
            document.body.appendChild(toastContainer);
        }

        // Set icon dan warna berdasarkan type
        let icon, title, emoji;
        if (type === 'success') {
            icon = 'fas fa-heart';
            title = 'Berhasil!';
            emoji = '🎉';
        } else {
            icon = 'fas fa-heart-broken';
            title = 'Oops!';
            emoji = '😕';
        }

        // Create toast element
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        toast.innerHTML = `
            <div class="toast-icon">
                <i class="${icon}"></i>
            </div>
            <div class="toast-content">
                <div class="toast-title">${emoji} ${title}</div>
                <div class="toast-message">${message}</div>
            </div>
            <button class="toast-close" onclick="removeToast(this)">
                <i class="fas fa-times"></i>
            </button>
        `;

        toastContainer.appendChild(toast);

        // Auto remove after 2.5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                removeToastElement(toast);
            }
        }, 2500);

        // Efek hover cepat
        toast.addEventListener('mouseenter', () => {
            toast.style.transform = 'scale(1.02)';
            toast.style.transition = 'transform 0.1s ease';
        });

        toast.addEventListener('mouseleave', () => {
            toast.style.transform = 'scale(1)';
        });
    }

    // Fungsi untuk remove toast
    function removeToast(closeButton) {
        const toast = closeButton.closest('.toast');
        if (toast) {
            removeToastElement(toast);
        }
    }

    function removeToastElement(toast) {
        toast.style.animation = 'toastOut 0.2s ease forwards';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 200);
    }

    // Toggle address input method
    function toggleAddressInput(method) {
        // Update button states
        document.getElementById('manual-btn').classList.remove('active');
        document.getElementById('gps-btn').classList.remove('active');

        // Show/hide input containers
        document.getElementById('manual-address').classList.remove('active');
        document.getElementById('gps-address').classList.remove('active');

        if (method === 'manual') {
            document.getElementById('manual-btn').classList.add('active');
            document.getElementById('manual-address').classList.add('active');
            document.getElementById('customer-address').required = true;
        } else {
            document.getElementById('gps-btn').classList.add('active');
            document.getElementById('gps-address').classList.add('active');
            document.getElementById('customer-address').required = false;

            // Auto-detect location when switching to GPS mode
            setTimeout(() => {
                detectLocation();
            }, 300);
        }
    }

    // Select GPS method
    function selectGPSMethod(method) {
        gpsMethod = method;

        // Update UI
        document.querySelectorAll('.gps-method-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');

        detectLocation();
    }

    // Detect location
    function detectLocation() {
        const statusElement = document.getElementById('gps-status');
        const resultsElement = document.getElementById('gps-results');

        if (!statusElement || !resultsElement) return;

        statusElement.style.display = 'flex';
        document.getElementById('gps-location-name').textContent = 'Mendeteksi lokasi...';
        document.getElementById('gps-location-address').textContent = 'Sedang mengambil data...';
        document.getElementById('gps-location-source').textContent = 'Sumber: GPS Device';

        resultsElement.innerHTML = '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Mendeteksi lokasi Anda...</div>';

        detectWithGPS();
    }

    // Detect with GPS
    function detectWithGPS() {
        if (!navigator.geolocation) {
            showLocationError('Browser tidak mendukung geolocation', 'Silakan gunakan input alamat manual');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy;

                try {
                    // Use OpenStreetMap Nominatim API for reverse geocoding
                    const response = await fetch(
                        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}&zoom=18&addressdetails=1`
                    );

                    const data = await response.json();

                    if (data && data.address) {
                        const address = data.address;
                        let locationName = '';
                        let fullAddress = '';

                        // Build location name
                        if (address.city) {
                            locationName = address.city;
                        } else if (address.town) {
                            locationName = address.town;
                        } else if (address.village) {
                            locationName = address.village;
                        } else if (address.municipality) {
                            locationName = address.municipality;
                        }

                        // Build full address
                        const addressParts = [];
                        if (address.road) addressParts.push(address.road);
                        if (address.suburb) addressParts.push(address.suburb);
                        if (address.city_district) addressParts.push(address.city_district);
                        if (address.city || address.town) addressParts.push(address.city || address.town);
                        if (address.state) addressParts.push(address.state);
                        if (address.country) addressParts.push(address.country);

                        fullAddress = addressParts.join(', ');

                        // Update UI
                        document.getElementById('gps-location-name').textContent = locationName || 'Lokasi Anda';
                        document.getElementById('gps-location-address').textContent = fullAddress;
                        document.getElementById('gps-location-source').textContent = `Sumber: GPS Device (Akurasi: ±${Math.round(accuracy)} meter)`;

                        // Show accuracy warning jika akurasi rendah
                        const warningElement = document.getElementById('accuracy-warning');
                        if (accuracy > 100) {
                            warningElement.style.display = 'flex';
                            document.getElementById('warning-text').textContent =
                                `Akurasi GPS ±${Math.round(accuracy)} meter. Periksa alamat sebelum digunakan.`;
                        } else {
                            warningElement.style.display = 'none';
                        }

                        // Clear loading
                        document.getElementById('gps-results').innerHTML = '';
                    } else {
                        throw new Error('Tidak dapat mendapatkan alamat dari koordinat');
                    }
                } catch (error) {
                    console.error('Reverse geocoding error:', error);
                    // Fallback to coordinates only
                    document.getElementById('gps-location-name').textContent = 'Lokasi Anda';
                    document.getElementById('gps-location-address').textContent =
                        `Koordinat: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
                    document.getElementById('gps-location-source').textContent =
                        `Sumber: GPS Device (Akurasi: ±${Math.round(accuracy)} meter)`;

                    document.getElementById('accuracy-warning').style.display = 'flex';
                    document.getElementById('warning-text').textContent =
                        'Tidak dapat mendapatkan nama lokasi. Koordinat GPS digunakan.';
                }
            },
            function(error) {
                let errorMessage = 'Gagal mendapatkan lokasi GPS';
                switch (error.code) {
                    case error.PERMISSION_DENIED:
                        errorMessage = 'Izin lokasi GPS ditolak. Silakan izinkan akses lokasi di browser settings.';
                        break;
                    case error.POSITION_UNAVAILABLE:
                        errorMessage = 'GPS tidak tersedia pada perangkat ini';
                        break;
                    case error.TIMEOUT:
                        errorMessage = 'Timeout mendapatkan lokasi GPS';
                        break;
                }
                showLocationError(errorMessage, 'Silakan gunakan input alamat manual');
            },
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    }

    function showLocationError(message, suggestion) {
        document.getElementById('gps-location-name').textContent = 'Gagal Mendeteksi Lokasi';
        document.getElementById('gps-location-address').textContent = message;
        document.getElementById('gps-location-source').textContent = suggestion;

        const resultsElement = document.getElementById('gps-results');
        resultsElement.innerHTML = `
            <div class="gps-loading">
                <i class="fas fa-exclamation-triangle"></i>
                <div>${message}</div>
                ${suggestion ? `<div style="margin-top: 5px; font-size: 12px;">${suggestion}</div>` : ''}
            </div>
        `;

        document.getElementById('accuracy-warning').style.display = 'flex';
        document.getElementById('warning-text').textContent = 'Gagal mendapatkan lokasi akurat. Silakan gunakan input alamat manual.';
    }

    // Search address
    function searchAddress() {
        const searchTerm = document.getElementById('gps-search').value.trim();
        const resultsElement = document.getElementById('gps-results');

        if (!resultsElement) return;

        resultsElement.innerHTML = '<div class="gps-loading"><i class="fas fa-spinner spinner"></i>Mencari...</div>';

        // Use OpenStreetMap Nominatim API for forward geocoding
        setTimeout(async () => {
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(searchTerm)}&limit=5&countrycodes=id`
                );

                const data = await response.json();

                if (data.length > 0) {
                    let html = '';
                    data.forEach(place => {
                        html += `
                            <div class="gps-result-item" onclick="selectGPSLocation('${place.display_name.replace(/'/g, "\\'")}', '${place.display_name.replace(/'/g, "\\'")}')">
                                <div class="gps-result-name">${place.display_name.split(',')[0]}</div>
                                <div class="gps-result-address">${place.display_name}</div>
                            </div>
                        `;
                    });
                    resultsElement.innerHTML = html;
                } else {
                    resultsElement.innerHTML = '<div class="gps-loading">Tidak ditemukan hasil untuk pencarian ini.</div>';
                }
            } catch (error) {
                console.error('Search error:', error);
                resultsElement.innerHTML = '<div class="gps-loading">Gagal melakukan pencarian.</div>';
            }
        }, 800);
    }

    function selectGPSLocation(name, address) {
        document.getElementById('gps-location-name').textContent = name.split(',')[0];
        document.getElementById('gps-location-address').textContent = address;
        document.getElementById('gps-location-source').textContent = 'Sumber: Pencarian Manual';
        document.getElementById('gps-status').style.display = 'flex';

        document.getElementById('accuracy-warning').style.display = 'flex';
        document.getElementById('warning-text').textContent = 'Alamat dipilih dari hasil pencarian. Periksa sebelum digunakan.';
    }

    function useGPSLocation() {
        const address = document.getElementById('gps-location-address').textContent;
        if (address) {
            document.getElementById('customer-address').value = address;
            toggleAddressInput('manual');
        }
    }

    // Select shipping option
    function selectShipping(type, cost) {
        shippingType = type;
        shippingCost = cost;

        document.querySelectorAll('.shipping-item').forEach(item => item.classList.remove('active'));
        event.currentTarget.classList.add('active');

        updateOrderSummary();
    }

    // Submit order
    async function submitOrder() {
        const name = document.getElementById('customer-name').value.trim();
        const phone = document.getElementById('customer-phone').value.trim();
        const address = document.getElementById('customer-address').value.trim();

        // Validation
        if (!name || !phone || !address) {
            showToast('❌ Harap lengkapi semua data yang diperlukan!', 'error');
            return;
        }

        // Validate phone number
        const phoneRegex = /^[0-9]{10,13}$/;
        if (!phoneRegex.test(phone)) {
            showToast('❌ Nomor WhatsApp tidak valid!', 'error');
            return;
        }

        if (checkoutType === 'single') {
            // Single product checkout
            await submitSingleOrder(name, phone, address);
        } else {
            // Cart checkout
            await submitCartOrder(name, phone, address);
        }
    }

    async function submitSingleOrder(name, phone, address) {
        // Calculate total
        let total = currentProductPrice;
        let deliveryInfo = '';

        if (deliveryOption === 'pickup') {
            deliveryInfo = 'Order di tempat (ambil di gerai/pameran)';
        } else {
            total += shippingCost;
            deliveryInfo = `Dikirim (${shippingType}) - Rp ${shippingCost.toLocaleString()}`;
        }

        try {
            // Save order to database via AJAX
            const response = await fetch('save_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    product_id: currentProductId,
                    product_name: currentProductName,
                    customer_name: name,
                    customer_phone: phone,
                    customer_address: address,
                    delivery_method: deliveryOption,
                    shipping_type: shippingType,
                    total_price: total,
                    status: 'pending'
                })
            });

            const data = await response.json();

            if (data.success) {
                // Prepare WhatsApp message
                const message =
                    `Halo, saya ingin memesan:\n\n` +
                    `Produk: ${currentProductName}\n` +
                    `Harga: Rp ${currentProductPrice.toLocaleString()}\n` +
                    `Metode: ${deliveryInfo}\n` +
                    `Total: Rp ${total.toLocaleString()}\n\n` +
                    `Data Diri:\n` +
                    `Nama: ${name}\n` +
                    `WhatsApp: ${phone}\n` +
                    `Alamat: ${address}`;

                const encodedMessage = encodeURIComponent(message);
                const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;

                // Show success message
                const successMessage = document.getElementById('success-message');
                successMessage.innerHTML =
                    `Pesanan Anda telah berhasil dibuat!<br><br>` +
                    `Produk: ${currentProductName}<br>` +
                    `Total: Rp ${total.toLocaleString()}<br><br>` +
                    `Kami akan menghubungi Anda via WhatsApp untuk konfirmasi lebih lanjut.`;

                // Open WhatsApp in new tab
                window.open(whatsappUrl, '_blank');

                // Show success modal
                closeCheckout();
                document.getElementById('success-modal').style.display = 'flex';
            } else {
                showToast('❌ Gagal menyimpan pesanan: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('⚠️ Terjadi kesalahan saat menyimpan pesanan.', 'error');
        }
    }

    async function submitCartOrder(name, phone, address) {
        // Calculate total
        let total = calculateCartTotal();
        let deliveryInfo = '';

        if (deliveryOption === 'pickup') {
            deliveryInfo = 'Order di tempat (ambil di gerai/pameran)';
        } else {
            total += shippingCost;
            deliveryInfo = `Dikirim (${shippingType}) - Rp ${shippingCost.toLocaleString()}`;
        }

        try {
            // Prepare order data
            const orderData = {
                customer_name: name,
                customer_phone: phone,
                customer_address: address,
                delivery_method: deliveryOption,
                shipping_type: shippingType,
                shipping_cost: deliveryOption === 'delivery' ? shippingCost : 0,
                total_price: total,
                status: 'pending',
                products: currentProducts
            };

            // Save order to database via AJAX (JSON format)
            const response = await fetch('save_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(orderData)
            });

            const data = await response.json();

            if (data.success) {
                // Prepare WhatsApp message
                let message = `Halo, saya ingin memesan:\n\n`;

                currentProducts.forEach(item => {
                    message += `• ${item.name} (${item.quantity}x)\n`;
                    message += `  Rp ${item.price.toLocaleString()} x ${item.quantity}\n`;
                    message += `  Subtotal: Rp ${(item.price * item.quantity).toLocaleString()}\n\n`;
                });

                message += `Metode: ${deliveryOption === 'pickup' ? 'Order di tempat' : `Dikirim (${shippingType})`}\n`;

                if (deliveryOption === 'delivery') {
                    message += `Ongkir: Rp ${shippingCost.toLocaleString()}\n`;
                }

                message += `Total: Rp ${total.toLocaleString()}\n\n`;
                message += `Data Diri:\n`;
                message += `Nama: ${name}\n`;
                message += `WhatsApp: ${phone}\n`;
                message += `Alamat: ${address}`;

                const encodedMessage = encodeURIComponent(message);
                const whatsappUrl = `https://wa.me/?text=${encodedMessage}`;

                // Clear cart
                await clearCart();

                // Show success message
                const successMessage = document.getElementById('success-message');
                successMessage.innerHTML = `
                    Pesanan Anda telah berhasil dibuat!<br><br>
                    Total: Rp ${total.toLocaleString()}<br>
                    ${currentProducts.length} produk<br><br>
                    Kami akan menghubungi Anda via WhatsApp untuk konfirmasi lebih lanjut.
                `;

                // Open WhatsApp in new tab
                window.open(whatsappUrl, '_blank');

                // Show success modal
                closeCheckout();
                document.getElementById('success-modal').style.display = 'flex';
            } else {
                showToast('❌ Gagal menyimpan pesanan: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('⚠️ Terjadi kesalahan saat menyimpan pesanan.', 'error');
        }
    }

    // Close success modal
    function closeSuccessModal() {
        document.getElementById('success-modal').style.display = 'none';
    }
    </script>
</body>
</html>
