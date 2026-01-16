<?php
// get_cart.php
session_start();

header("Content-Type: application/json");

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$itemsArray = [];
$totalItems = 0;
$cartTotal = 0;

foreach ($_SESSION["cart"] as $item) {
    $itemsArray[] = [
        "id" => $item["id"],
        "name" => $item["name"],
        "price" => $item["price"],
        "image" => $item["image"],
        "quantity" => $item["quantity"],
    ];

    $totalItems += $item["quantity"];
    $cartTotal += $item["price"] * $item["quantity"];
}

echo json_encode([
    "success" => true,
    "items" => $itemsArray,
    "total_items" => $totalItems,
    "cart_total" => $cartTotal,
]);
?>
