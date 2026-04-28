<?php
require __DIR__ . "/config/db.php";
include __DIR__ . "/includes/header.php";

if (empty($_SESSION["user"])) {
    header("Location: /turyshop/auth/login.php");
    exit;
}

if (!empty($_SESSION["role"]) && $_SESSION["role"] === "admin") {
    header("Location: /turyshop/admin/dashboard.php");
    exit;
}

$cart = $_SESSION["cart"] ?? [];
$total = 0;
?>

<div class="container py-4">

    <!-- INTRO -->
    <section class="mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <div>
                <h2 class="fw-bold mb-1">Coșul meu</h2>
                <p class="text-muted mb-0">Verifică produsele selectate și actualizează cantitățile înainte de finalizarea comenzii.</p>
            </div>
        </div>
    </section>

    <?php if (empty($cart)): ?>
        <div class="product-detail-box text-center py-5">
            <h4 class="fw-bold mb-2">Coșul este gol</h4>
            <p class="text-muted mb-4">Nu ai adăugat încă produse în coș.</p>
            <a href="/turyshop/products.php" class="btn btn-dark px-4">Continuă cumpărăturile</a>
        </div>
    <?php else: ?>

        <div class="row g-4">
            <!-- LISTA PRODUSE -->
            <div class="col-lg-8">
                <div class="product-detail-box">
                    <div class="table-responsive">
                        <table class="table align-middle cart-table mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Produs</th>
                                    <th>Preț</th>
                                    <th>Cantitate</th>
                                    <th>Subtotal</th>
                                    <th>Acțiuni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                    <?php
                                    $subtotal = $item["price"] * $item["quantity"];
                                    $total += $subtotal;

                                    $img = !empty($item["image"]) ? $item["image"] : "assets/img/products/placeholder.jpg";
                                    if (strpos($img, "http://") === 0 || strpos($img, "https://") === 0) {
                                        $imgUrl = $img;
                                    } else {
                                        $imgUrl = "/turyshop/" . ltrim($img, "/");
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img
                                                    src="<?php echo htmlspecialchars($imgUrl); ?>"
                                                    alt="<?php echo htmlspecialchars($item["name"]); ?>"
                                                >
                                                <div>
                                                    <div class="fw-semibold mb-1"><?php echo htmlspecialchars($item["name"]); ?></div>
                                                    <div class="text-muted small">Produs selectat în coș</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td><?php echo number_format((float)$item["price"], 2); ?> lei</td>

                                        <td style="min-width: 190px;">
                                            <form action="/turyshop/update_cart.php" method="post" class="d-flex gap-2 align-items-center">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$item["id"]; ?>">
                                                <input
                                                    type="number"
                                                    name="quantity"
                                                    class="form-control"
                                                    value="<?php echo (int)$item["quantity"]; ?>"
                                                    min="1"
                                                    style="max-width: 90px;"
                                                >
                                                <button type="submit" class="btn btn-outline-dark btn-sm">Actualizează</button>
                                            </form>
                                        </td>

                                        <td class="fw-semibold"><?php echo number_format($subtotal, 2); ?> lei</td>

                                        <td>
                                            <form action="/turyshop/remove_from_cart.php" method="post">
                                                <input type="hidden" name="product_id" value="<?php echo (int)$item["id"]; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">Șterge</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- REZUMAT -->
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4 class="fw-bold mb-4">Rezumat comandă</h4>

                    <div class="summary-row">
                        <span class="summary-label">Număr produse</span>
                        <span class="summary-value"><?php echo count($cart); ?></span>
                    </div>

                    <div class="summary-row mb-0">
                        <span class="summary-label">Valoare totală</span>
                        <span class="summary-value"><?php echo number_format($total, 2); ?> lei</span>
                    </div>

                    <hr>

                    <div class="summary-total-label">Total de plată</div>
                    <div class="cart-total"><?php echo number_format($total, 2); ?> lei</div>

                    <div class="d-grid gap-3">
                        <a href="/turyshop/checkout.php" class="btn btn-dark btn-lg">Finalizează comanda</a>
                        <a href="/turyshop/products.php" class="btn btn-outline-secondary btn-lg">Continuă cumpărăturile</a>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>