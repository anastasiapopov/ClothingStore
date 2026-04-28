<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user"])) {
    header("Location: /turyshop/auth/login.php");
    exit;
}

$productId = (int)($_POST["product_id"] ?? 0);

if ($productId > 0 && isset($_SESSION["cart"][$productId])) {
    unset($_SESSION["cart"][$productId]);

    $_SESSION["flash"] = [
        "type" => "success",
        "message" => "Produsul a fost eliminat din coș."
    ];
} else {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "Produsul nu a fost găsit în coș."
    ];
}

header("Location: /turyshop/cart.php");
exit;