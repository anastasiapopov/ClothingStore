<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/includes/mailer.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user_id"])) {
    header("Location: /turyshop/auth/login.php");
    exit;
}

$cart = $_SESSION["cart"] ?? [];

if (empty($cart)) {
    header("Location: /turyshop/cart.php");
    exit;
}

/* calcul total */
$total = 0;
foreach ($cart as $item) {
    $total += ((float)$item["price"]) * ((int)$item["quantity"]);
}

/* verificăm încă o dată stocul înainte de finalizare */
foreach ($cart as $item) {
    $stmtCheck = $conn->prepare("
        SELECT name, stock
        FROM products
        WHERE id = ?
    ");
    $stmtCheck->bind_param("i", $item["id"]);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows !== 1) {
        $_SESSION["flash"] = [
            "type" => "danger",
            "message" => "Un produs din coș nu mai există."
        ];
        header("Location: /turyshop/cart.php");
        exit;
    }

    $productCheck = $resCheck->fetch_assoc();
    $availableStock = (int)$productCheck["stock"];
    $requestedQty = (int)$item["quantity"];

    if ($availableStock < $requestedQty) {
        $_SESSION["flash"] = [
            "type" => "danger",
            "message" => "Stoc insuficient pentru produsul „" . $productCheck["name"] . "”."
        ];
        header("Location: /turyshop/cart.php");
        exit;
    }
}

/* salvăm comanda */
$stmtOrder = $conn->prepare("
    INSERT INTO orders (user_id, total)
    VALUES (?, ?)
");
$stmtOrder->bind_param("id", $_SESSION["user_id"], $total);

if (!$stmtOrder->execute()) {
    $_SESSION["flash"] = [
        "type" => "danger",
        "message" => "A apărut o eroare la salvarea comenzii."
    ];
    header("Location: /turyshop/cart.php");
    exit;
}

$order_id = $conn->insert_id;

/* pregătim statement-urile */
$stmtItem = $conn->prepare("
    INSERT INTO order_items (order_id, product_id, quantity, price)
    VALUES (?, ?, ?, ?)
");

$stmtStock = $conn->prepare("
    UPDATE products
    SET stock = stock - ?
    WHERE id = ? AND stock >= ?
");

$stmtCheckStock = $conn->prepare("
    SELECT name, stock, min_stock
    FROM products
    WHERE id = ?
");

/* email admin - detalii comandă */
$adminEmail = "anastasia.popov.2023@gmail.com";

$orderMessage = "<h3>Comandă nouă primită</h3>";
$orderMessage .= "<p><b>Utilizator:</b> " . htmlspecialchars($_SESSION["user"]) . "</p>";
$orderMessage .= "<p><b>Total:</b> " . number_format($total, 2) . " lei</p>";
$orderMessage .= "<h4>Produse comandate:</h4><ul>";

foreach ($cart as $item) {
    $productId = (int)$item["id"];
    $quantity  = (int)$item["quantity"];
    $price     = (float)$item["price"];

    /* salvăm produsul în comandă */
    $stmtItem->bind_param("iiid", $order_id, $productId, $quantity, $price);
    $stmtItem->execute();

    /* scădem stocul */
    $stmtStock->bind_param("iii", $quantity, $productId, $quantity);
    $stmtStock->execute();

    /* completăm emailul comenzii */
    $orderMessage .= "<li>" . htmlspecialchars($item["name"]) . " x " . $quantity . "</li>";

    /* verificăm noul stoc */
    $stmtCheckStock->bind_param("i", $productId);
    $stmtCheckStock->execute();
    $resStock = $stmtCheckStock->get_result();

    if ($resStock && $resStock->num_rows === 1) {
        $stockRow = $resStock->fetch_assoc();

        $productName = $stockRow["name"];
        $newStock    = (int)$stockRow["stock"];
        $minStock    = (int)$stockRow["min_stock"];

        if ($newStock > 0 && $newStock <= $minStock) {
            $subject = "Alerta stoc minim - TuryShop";
            $body = "
                <h3>Produs cu stoc redus</h3>
                <p><b>Produs:</b> " . htmlspecialchars($productName) . "</p>
                <p><b>Stoc curent:</b> " . $newStock . "</p>
                <p><b>Prag minim:</b> " . $minStock . "</p>
                <p>Se recomandă reaprovizionarea produsului.</p>
            ";

            sendMail($adminEmail, $subject, $body);
        }

        if ($newStock === 0) {
            $subject = "Produs epuizat - TuryShop";
            $body = "
                <h3>Produs epuizat</h3>
                <p><b>Produs:</b> " . htmlspecialchars($productName) . "</p>
                <p>Stocul a ajuns la 0 în urma unei comenzi recente.</p>
            ";

            sendMail($adminEmail, $subject, $body);
        }
    }
}

$orderMessage .= "</ul>";

/* trimitem emailul pentru comanda nouă */
sendMail(
    $adminEmail,
    "Comanda noua TuryShop",
    $orderMessage
);

/* golim coșul */
unset($_SESSION["cart"]);

$_SESSION["flash"] = [
    "type" => "success",
    "message" => "Comanda a fost plasată cu succes."
];

header("Location: /turyshop/order_success.php");
exit;