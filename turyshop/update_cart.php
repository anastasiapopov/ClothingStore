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
$quantity  = (int)($_POST["quantity"] ?? 1);

if ($productId <= 0 || !isset($_SESSION["cart"][$productId])) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Produsul nu există în coș."
    ];
    header("Location: /turyshop/cart.php");
    exit;
}

if ($quantity < 1) {
    $quantity = 1;
}

$stmt = $conn->prepare("
    SELECT stock
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
    header("Location: /turyshop/cart.php");
    exit;
}

$product = $res->fetch_assoc();
$currentStock = (int)$product["stock"];

if ($currentStock <= 0) {
    unset($_SESSION["cart"][$productId]);

    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Produsul nu mai este în stoc și a fost eliminat din coș."
    ];
    header("Location: /turyshop/cart.php");
    exit;
}

if ($quantity > $currentStock) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Cantitatea solicitată depășește stocul disponibil."
    ];
    header("Location: /turyshop/cart.php");
    exit;
}

$_SESSION["cart"][$productId]["quantity"] = $quantity;

$_SESSION["flash"] = [
    "type" => "success",
    "message" => "Cantitatea produsului a fost actualizată."
];

header("Location: /turyshop/cart.php");
exit;