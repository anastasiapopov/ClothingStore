<?php
require __DIR__ . "/../config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["user"]) || empty($_SESSION["role"]) || $_SESSION["role"] !== "admin") {
    header("Location: /turyshop/auth/login.php");
    exit;
}

/* Filtru perioadă */
$range = $_GET["range"] ?? "7";
$allowedRanges = ["7", "30", "all"];

if (!in_array($range, $allowedRanges, true)) {
    $range = "7";
}

/* Condiție SQL pentru perioadă */
$dateCondition = "";
if ($range === "7") {
    $dateCondition = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $dateCondition = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

/* KPI-uri generale */
$resProducts = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $resProducts ? (int)($resProducts->fetch_assoc()["total"] ?? 0) : 0;

$resUsers = $conn->query("SELECT COUNT(*) AS total FROM users");
$totalUsers = $resUsers ? (int)($resUsers->fetch_assoc()["total"] ?? 0) : 0;

/* KPI-uri pe perioadă */
$resOrders = $conn->query("SELECT COUNT(*) AS total FROM orders" . $dateCondition);
$totalOrders = $resOrders ? (int)($resOrders->fetch_assoc()["total"] ?? 0) : 0;

$resSales = $conn->query("SELECT COALESCE(SUM(total), 0) AS total_sales FROM orders" . $dateCondition);
$totalSales = $resSales ? (float)($resSales->fetch_assoc()["total_sales"] ?? 0) : 0;

/* KPI stoc */
$resOutOfStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock <= 0");
$totalOutOfStock = $resOutOfStock ? (int)($resOutOfStock->fetch_assoc()["total"] ?? 0) : 0;

$resLowStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock > 0 AND stock <= min_stock");
$totalLowStock = $resLowStock ? (int)($resLowStock->fetch_assoc()["total"] ?? 0) : 0;

/* Comenzi recente */
$recentOrders = $conn->query("
    SELECT o.id, o.total, o.created_at, u.username
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 5
");

/* Top produse vândute pe perioadă */
$topProductsSql = "
    SELECT p.name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN orders o ON o.id = oi.order_id
";

if ($range === "7") {
    $topProductsSql .= " WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $topProductsSql .= " WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

$topProductsSql .= "
    GROUP BY oi.product_id, p.name
    ORDER BY total_qty DESC, p.name ASC
    LIMIT 5
";

$topProducts = $conn->query($topProductsSql);

/* Vânzări pe zile */
$salesByDaySql = "
    SELECT DATE(created_at) AS day, SUM(total) AS total_sales
    FROM orders
";

if ($range === "7") {
    $salesByDaySql .= " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $salesByDaySql .= " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

$salesByDaySql .= "
    GROUP BY DATE(created_at)
    ORDER BY day ASC
";

$salesByDay = $conn->query($salesByDaySql);

$chartLabels = [];
$chartValues = [];

if ($salesByDay && $salesByDay->num_rows > 0) {
    while ($row = $salesByDay->fetch_assoc()) {
        $chartLabels[] = $row["day"];
        $chartValues[] = (float)$row["total_sales"];
    }
}

/* Produse pe categorii - catalog */
$productsByCategory = $conn->query("
    SELECT c.name AS category_name, COUNT(p.id) AS total_products
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY total_products DESC, c.name ASC
");

$pieLabels = [];
$pieValues = [];

if ($productsByCategory && $productsByCategory->num_rows > 0) {
    while ($row = $productsByCategory->fetch_assoc()) {
        $pieLabels[] = $row["category_name"];
        $pieValues[] = (int)$row["total_products"];
    }
}

/* Produse care trebuie reaprovizionate */
$restockProducts = $conn->query("
    SELECT p.name, p.stock, p.min_stock, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.stock <= p.min_stock
    ORDER BY p.stock ASC, p.name ASC
    LIMIT 10
");

/* Stoc pe categorii */
$stockByCategory = $conn->query("
    SELECT c.name AS category_name, COALESCE(SUM(p.stock), 0) AS total_stock
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name
    ORDER BY c.name ASC
");

$stockChartLabels = [];
$stockChartValues = [];

if ($stockByCategory && $stockByCategory->num_rows > 0) {
    while ($row = $stockByCategory->fetch_assoc()) {
        $stockChartLabels[] = $row["category_name"];
        $stockChartValues[] = (int)$row["total_stock"];
    }
}

include __DIR__ . "/../includes/header.php";
?>

<div class="container py-4">

    <section class="mb-4">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
            <div>
                <span class="badge text-bg-dark mb-2 px-3 py-2">Administrare</span>
                <h2 class="fw-bold mb-1">Dashboard Administrator</h2>
                <p class="text-muted mb-0">
                    Bine ai venit, <?php echo htmlspecialchars($_SESSION["user"]); ?>. Urmărește activitatea magazinului, comenzile și situația stocurilor.
                </p>
            </div>

            <form method="get" class="d-flex align-items-center gap-2">
                <label for="range" class="form-label mb-0 fw-semibold">Perioadă:</label>
                <select name="range" id="range" class="form-select" onchange="this.form.submit()" style="min-width: 180px;">
                    <option value="7" <?php echo $range === "7" ? "selected" : ""; ?>>Ultimele 7 zile</option>
                    <option value="30" <?php echo $range === "30" ? "selected" : ""; ?>>Ultimele 30 zile</option>
                    <option value="all" <?php echo $range === "all" ? "selected" : ""; ?>>Tot timpul</option>
                </select>
            </form>
        </div>
    </section>

    <!-- KPI principale -->
    <section class="mb-4">
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Produse</div>
                    <div class="stat-value"><?php echo $totalProducts; ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Utilizatori</div>
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Comenzi</div>
                    <div class="stat-value"><?php echo $totalOrders; ?></div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Vânzări totale</div>
                    <div class="stat-value"><?php echo number_format($totalSales, 2); ?> lei</div>
                </div>
            </div>
        </div>
    </section>

    <!-- KPI stoc -->
    <section class="mb-4">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-label">Produse fără stoc</div>
                    <div class="stat-value"><?php echo $totalOutOfStock; ?></div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="stat-card">
                    <div class="stat-label">Produse la stoc minim</div>
                    <div class="stat-value"><?php echo $totalLowStock; ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabele -->
    <section class="mb-4">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Comenzi recente</h5>

                    <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Utilizator</th>
                                        <th>Total</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recentOrders->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo (int)$order["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($order["username"]); ?></td>
                                            <td><?php echo number_format((float)$order["total"], 2); ?> lei</td>
                                            <td><?php echo htmlspecialchars($order["created_at"]); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nu există comenzi încă.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Top produse vândute</h5>

                    <?php if ($topProducts && $topProducts->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produs</th>
                                        <th>Bucăți vândute</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($product = $topProducts->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product["name"]); ?></td>
                                            <td><?php echo (int)$product["total_qty"]; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nu există vânzări suficiente pentru clasament în perioada selectată.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Reaprovizionare -->
    <section class="mb-4">
        <div class="chart-card">
            <h5 class="fw-bold mb-3">Produse care necesită reaprovizionare</h5>

            <?php if ($restockProducts && $restockProducts->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Produs</th>
                                <th>Categorie</th>
                                <th>Stoc curent</th>
                                <th>Stoc minim</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($rp = $restockProducts->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($rp["name"]); ?></td>
                                    <td><?php echo htmlspecialchars($rp["category_name"]); ?></td>
                                    <td><?php echo (int)$rp["stock"]; ?></td>
                                    <td><?php echo (int)$rp["min_stock"]; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted mb-0">Nu există produse care necesită reaprovizionare.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Grafice -->
    <section>
        <div class="row g-4">
            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Vânzări pe zile</h5>
                    <canvas id="salesChart" height="120"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Produse pe categorii</h5>
                    <canvas id="categoryChart" height="120"></canvas>
                </div>
            </div>

            <div class="col-12">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Stoc total pe categorii</h5>
                    <canvas id="stockChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const salesLabels = <?php echo json_encode($chartLabels); ?>;
const salesValues = <?php echo json_encode($chartValues); ?>;
const categoryLabels = <?php echo json_encode($pieLabels); ?>;
const categoryValues = <?php echo json_encode($pieValues); ?>;
const stockLabels = <?php echo json_encode($stockChartLabels); ?>;
const stockValues = <?php echo json_encode($stockChartValues); ?>;

const salesCtx = document.getElementById('salesChart');
if (salesCtx) {
    new Chart(salesCtx, {
        type: 'line',
        data: {
            labels: salesLabels,
            datasets: [{
                label: 'Vânzări (lei)',
                data: salesValues,
                tension: 0.4,
                fill: false,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

const categoryCtx = document.getElementById('categoryChart');
if (categoryCtx) {
    new Chart(categoryCtx, {
        type: 'pie',
        data: {
            labels: categoryLabels,
            datasets: [{
                data: categoryValues
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}

const stockCtx = document.getElementById('stockChart');
if (stockCtx) {
    new Chart(stockCtx, {
        type: 'bar',
        data: {
            labels: stockLabels,
            datasets: [{
                label: 'Stoc total',
                data: stockValues,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>