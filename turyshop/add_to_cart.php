<?php
require __DIR__ . "/config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user"])) {
    header("Location: /turyshop/auth/login.php");
    exit;
}

$productId = (int)($_POST["product_id"] ?? 0);
$quantity = (int)($_POST["quantity"] ?? 1);

if ($productId <= 0) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Produs invalid."
    ];
    header("Location: /turyshop/products.php");
    exit;
}

if ($quantity < 1) {
    $quantity = 1;
}

$stmt = $conn->prepare("
    SELECT id, name, price, image, stock
    FROM products
    WHERE id = ?
");
$stmt->bind_param("i", $productId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Produsul nu a fost găsit."
    ];
    header("Location: /turyshop/products.php");
    exit;
}

$product = $res->fetch_assoc();
$currentStock = (int)$product["stock"];

if ($currentStock <= 0) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Produsul nu mai este în stoc."
    ];
    $redirect = $_SERVER["HTTP_REFERER"] ?? "/turyshop/products.php";
    header("Location: " . $redirect);
    exit;
}

if (!isset($_SESSION["cart"])) {
    $_SESSION["cart"] = [];
}

$existingQty = 0;
if (isset($_SESSION["cart"][$productId])) {
    $existingQty = (int)$_SESSION["cart"][$productId]["quantity"];
}

if (($existingQty + $quantity) > $currentStock) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Nu poți adăuga o cantitate mai mare decât stocul disponibil."
    ];
    $redirect = $_SERVER["HTTP_REFERER"] ?? "/turyshop/products.php";
    header("Location: " . $redirect);
    exit;
}

if (isset($_SESSION["cart"][$productId])) {
    $_SESSION["cart"][$productId]["quantity"] += $quantity;
} else {
    $_SESSION["cart"][$productId] = [
        "id" => (int)$product["id"],
        "name" => $product["name"],
        "price" => (float)$product["price"],
        "image" => $product["image"],
        "quantity" => $quantity
    ];
}

$_SESSION["flash"] = [
    "type" => "success",
    "message" => "Produsul a fost adăugat în coș."
];

$redirect = $_SERVER["HTTP_REFERER"] ?? "/turyshop/products.php";
header("Location: " . $redirect);
exit;