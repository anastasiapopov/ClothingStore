<?php
require __DIR__ . "/../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user"]) || empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /turyshop/auth/login.php");
    exit;
}

$errors = [];
$success = "";

/* Actualizare stoc */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $productId = (int)($_POST["product_id"] ?? 0);
    $stock = (int)($_POST["stock"] ?? 0);
    $minStock = (int)($_POST["min_stock"] ?? 0);

    if ($productId <= 0) {
        $errors[] = "Produs invalid.";
    }

    if ($stock < 0) {
        $errors[] = "Stocul nu poate fi negativ.";
    }

    if ($minStock < 0) {
        $errors[] = "Stocul minim nu poate fi negativ.";
    }

    if (!$errors) {
        $stmt = $conn->prepare("
            UPDATE products
            SET stock = ?, min_stock = ?
            WHERE id = ?
        ");
        $stmt->bind_param("iii", $stock, $minStock, $productId);

        if ($stmt->execute()) {
            $success = "Stocul produsului a fost actualizat cu succes.";
        } else {
            $errors[] = "A apărut o eroare la actualizarea stocului.";
        }
    }
}

/* Produse */
$products = $conn->query("
    SELECT p.id, p.name, p.price, p.stock, p.min_stock, p.image, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    ORDER BY c.name ASC, p.name ASC
");

include __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">

    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
        <div>
            <span class="badge text-bg-dark mb-2 px-3 py-2">Administrare</span>
            <h2 class="fw-bold mb-1">Gestionare stocuri</h2>
            <p class="text-muted mb-0">
                Actualizează stocul disponibil și pragul minim pentru fiecare produs din magazin.
            </p>
        </div>

        <a href="/turyshop/admin/dashboard.php" class="btn btn-outline-dark">
            Înapoi la dashboard
        </a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="chart-card">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produs</th>
                        <th>Categorie</th>
                        <th>Preț</th>
                        <th>Stoc curent</th>
                        <th>Stoc minim</th>
                        <th>Status</th>
                        <th>Acțiune</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && $products->num_rows > 0): ?>
                        <?php while ($p = $products->fetch_assoc()): ?>
                            <?php
                                $stock = (int)$p["stock"];
                                $minStock = (int)$p["min_stock"];

                                if ($stock <= 0) {
                                    $statusText = "Epuizat";
                                    $statusClass = "text-danger fw-semibold";
                                } elseif ($stock <= $minStock) {
                                    $statusText = "Stoc redus";
                                    $statusClass = "text-warning fw-semibold";
                                } else {
                                    $statusText = "Disponibil";
                                    $statusClass = "text-success fw-semibold";
                                }
                            ?>

                            <tr>
                                <td>
                                    <div class="fw-semibold">
                                        <?php echo htmlspecialchars($p["name"]); ?>
                                    </div>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($p["category_name"]); ?>
                                </td>

                                <td>
                                    <?php echo number_format((float)$p["price"], 2); ?> lei
                                </td>

                                <td style="width: 140px;">
                                    <form method="post" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="product_id" value="<?php echo (int)$p["id"]; ?>">

                                        <input
                                            type="number"
                                            name="stock"
                                            class="form-control"
                                            value="<?php echo $stock; ?>"
                                            min="0"
                                            style="max-width: 95px;"
                                        >
                                </td>

                                <td style="width: 140px;">
                                        <input
                                            type="number"
                                            name="min_stock"
                                            class="form-control"
                                            value="<?php echo $minStock; ?>"
                                            min="0"
                                            style="max-width: 95px;"
                                        >
                                </td>

                                <td>
                                    <span class="<?php echo $statusClass; ?>">
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>

                                <td>
                                        <button type="submit" class="btn btn-dark btn-sm">
                                            Salvează
                                        </button>
                                    </form>
                                </td>
                            </tr>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-muted">
                                Nu există produse înregistrate.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>