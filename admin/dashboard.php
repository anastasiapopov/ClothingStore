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

/* Condiții SQL pentru perioadă */
$dateCondition = "";
$orderDateCondition = "";

if ($range === "7") {
    $dateCondition = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
    $orderDateCondition = " AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $dateCondition = " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
    $orderDateCondition = " AND o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

/* KPI-uri generale */
$resProducts = $conn->query("SELECT COUNT(*) AS total FROM products");
$totalProducts = $resProducts ? (int)($resProducts->fetch_assoc()["total"] ?? 0) : 0;

$resUsers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'client'");
$totalUsers = $resUsers ? (int)($resUsers->fetch_assoc()["total"] ?? 0) : 0;

$resOrders = $conn->query("SELECT COUNT(*) AS total FROM orders" . $dateCondition);
$totalOrders = $resOrders ? (int)($resOrders->fetch_assoc()["total"] ?? 0) : 0;

$resSales = $conn->query("SELECT COALESCE(SUM(total), 0) AS total_sales FROM orders" . $dateCondition);
$totalSales = $resSales ? (float)($resSales->fetch_assoc()["total_sales"] ?? 0) : 0;

$resAvgOrder = $conn->query("SELECT COALESCE(AVG(total), 0) AS avg_order FROM orders" . $dateCondition);
$avgOrderValue = $resAvgOrder ? (float)($resAvgOrder->fetch_assoc()["avg_order"] ?? 0) : 0;

/* KPI stoc */
$resOutOfStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock <= 0");
$totalOutOfStock = $resOutOfStock ? (int)($resOutOfStock->fetch_assoc()["total"] ?? 0) : 0;

$resLowStock = $conn->query("SELECT COUNT(*) AS total FROM products WHERE stock > 0 AND stock <= min_stock");
$totalLowStock = $resLowStock ? (int)($resLowStock->fetch_assoc()["total"] ?? 0) : 0;

/* Județ cu cele mai multe comenzi */
$topCountySql = "
    SELECT u.county, COUNT(o.id) AS total_orders
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE u.county IS NOT NULL AND u.county <> ''
    $orderDateCondition
    GROUP BY u.county
    ORDER BY total_orders DESC, u.county ASC
    LIMIT 1
";

$resTopCounty = $conn->query($topCountySql);
$topCounty = "N/A";
$topCountyOrders = 0;

if ($resTopCounty && $resTopCounty->num_rows > 0) {
    $row = $resTopCounty->fetch_assoc();
    $topCounty = $row["county"];
    $topCountyOrders = (int)$row["total_orders"];
}

/* Grupă de vârstă cu cele mai mari vânzări */
$topAgeGroupSql = "
    SELECT
        CASE
            WHEN u.age BETWEEN 14 AND 18 THEN '14-18'
            WHEN u.age BETWEEN 19 AND 25 THEN '19-25'
            WHEN u.age BETWEEN 26 AND 35 THEN '26-35'
            WHEN u.age BETWEEN 36 AND 50 THEN '36-50'
            ELSE '50+'
        END AS age_group,
        SUM(o.total) AS total_sales
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE u.age IS NOT NULL
    $orderDateCondition
    GROUP BY age_group
    ORDER BY total_sales DESC
    LIMIT 1
";

$resTopAgeGroup = $conn->query($topAgeGroupSql);
$topAgeGroup = "N/A";
$topAgeGroupSales = 0;

if ($resTopAgeGroup && $resTopAgeGroup->num_rows > 0) {
    $row = $resTopAgeGroup->fetch_assoc();
    $topAgeGroup = $row["age_group"];
    $topAgeGroupSales = (float)$row["total_sales"];
}

/* Comenzi recente */
$recentOrders = $conn->query("
    SELECT o.id, o.total, o.created_at, u.username, u.age, u.county
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC
    LIMIT 8
");

/* Top produse vândute */
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
    LIMIT 7
";

$topProductsResult = $conn->query($topProductsSql);
$topProductsRows = [];
$topProductLabels = [];
$topProductValues = [];

if ($topProductsResult && $topProductsResult->num_rows > 0) {
    while ($row = $topProductsResult->fetch_assoc()) {
        $topProductsRows[] = $row;
        $topProductLabels[] = $row["name"];
        $topProductValues[] = (int)$row["total_qty"];
    }
}

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

$salesLabels = [];
$salesValues = [];

if ($salesByDay && $salesByDay->num_rows > 0) {
    while ($row = $salesByDay->fetch_assoc()) {
        $salesLabels[] = $row["day"];
        $salesValues[] = (float)$row["total_sales"];
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

$productCategoryLabels = [];
$productCategoryValues = [];

if ($productsByCategory && $productsByCategory->num_rows > 0) {
    while ($row = $productsByCategory->fetch_assoc()) {
        $productCategoryLabels[] = $row["category_name"];
        $productCategoryValues[] = (int)$row["total_products"];
    }
}

/* Vânzări pe categorii */
$salesByCategorySql = "
    SELECT c.name AS category_name, SUM(oi.quantity * oi.price) AS total_sales
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    JOIN orders o ON o.id = oi.order_id
";

if ($range === "7") {
    $salesByCategorySql .= " WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $salesByCategorySql .= " WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

$salesByCategorySql .= "
    GROUP BY c.id, c.name
    ORDER BY total_sales DESC
";

$salesByCategory = $conn->query($salesByCategorySql);

$salesCategoryLabels = [];
$salesCategoryValues = [];

if ($salesByCategory && $salesByCategory->num_rows > 0) {
    while ($row = $salesByCategory->fetch_assoc()) {
        $salesCategoryLabels[] = $row["category_name"];
        $salesCategoryValues[] = (float)$row["total_sales"];
    }
}

/* Cantitate vândută pe categorii */
$quantityByCategorySql = "
    SELECT c.name AS category_name, SUM(oi.quantity) AS total_qty
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    JOIN categories c ON c.id = p.category_id
    JOIN orders o ON o.id = oi.order_id
";

if ($range === "7") {
    $quantityByCategorySql .= " WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $quantityByCategorySql .= " WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

$quantityByCategorySql .= "
    GROUP BY c.id, c.name
    ORDER BY total_qty DESC
";

$quantityByCategory = $conn->query($quantityByCategorySql);

$quantityCategoryLabels = [];
$quantityCategoryValues = [];

if ($quantityByCategory && $quantityByCategory->num_rows > 0) {
    while ($row = $quantityByCategory->fetch_assoc()) {
        $quantityCategoryLabels[] = $row["category_name"];
        $quantityCategoryValues[] = (int)$row["total_qty"];
    }
}

/* Distribuția comenzilor după valoare */
$orderValueDistributionSql = "
    SELECT
        CASE
            WHEN total < 150 THEN 'Sub 150 lei'
            WHEN total >= 150 AND total < 300 THEN '150-299 lei'
            WHEN total >= 300 AND total < 500 THEN '300-499 lei'
            ELSE 'Peste 500 lei'
        END AS value_group,
        COUNT(id) AS total_orders
    FROM orders
";

if ($range === "7") {
    $orderValueDistributionSql .= " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) ";
} elseif ($range === "30") {
    $orderValueDistributionSql .= " WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) ";
}

$orderValueDistributionSql .= "
    GROUP BY value_group
    ORDER BY
        CASE value_group
            WHEN 'Sub 150 lei' THEN 1
            WHEN '150-299 lei' THEN 2
            WHEN '300-499 lei' THEN 3
            ELSE 4
        END
";

$orderValueDistribution = $conn->query($orderValueDistributionSql);

$orderValueLabels = [];
$orderValueValues = [];

if ($orderValueDistribution && $orderValueDistribution->num_rows > 0) {
    while ($row = $orderValueDistribution->fetch_assoc()) {
        $orderValueLabels[] = $row["value_group"];
        $orderValueValues[] = (int)$row["total_orders"];
    }
}

/* Vânzări pe județe */
$salesByCountySql = "
    SELECT u.county, SUM(o.total) AS total_sales
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE u.county IS NOT NULL AND u.county <> ''
    $orderDateCondition
    GROUP BY u.county
    ORDER BY total_sales DESC
    LIMIT 8
";

$salesByCounty = $conn->query($salesByCountySql);

$countySalesLabels = [];
$countySalesValues = [];

if ($salesByCounty && $salesByCounty->num_rows > 0) {
    while ($row = $salesByCounty->fetch_assoc()) {
        $countySalesLabels[] = $row["county"];
        $countySalesValues[] = (float)$row["total_sales"];
    }
}

/* Comenzi pe județe */
$ordersByCountySql = "
    SELECT u.county, COUNT(o.id) AS total_orders
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE u.county IS NOT NULL AND u.county <> ''
    $orderDateCondition
    GROUP BY u.county
    ORDER BY total_orders DESC
    LIMIT 8
";

$ordersByCounty = $conn->query($ordersByCountySql);

$countyOrdersLabels = [];
$countyOrdersValues = [];

if ($ordersByCounty && $ordersByCounty->num_rows > 0) {
    while ($row = $ordersByCounty->fetch_assoc()) {
        $countyOrdersLabels[] = $row["county"];
        $countyOrdersValues[] = (int)$row["total_orders"];
    }
}

/* Vânzări pe grupe de vârstă */
$salesByAgeSql = "
    SELECT
        CASE
            WHEN u.age BETWEEN 14 AND 18 THEN '14-18'
            WHEN u.age BETWEEN 19 AND 25 THEN '19-25'
            WHEN u.age BETWEEN 26 AND 35 THEN '26-35'
            WHEN u.age BETWEEN 36 AND 50 THEN '36-50'
            ELSE '50+'
        END AS age_group,
        SUM(o.total) AS total_sales
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE u.age IS NOT NULL
    $orderDateCondition
    GROUP BY age_group
    ORDER BY
        CASE age_group
            WHEN '14-18' THEN 1
            WHEN '19-25' THEN 2
            WHEN '26-35' THEN 3
            WHEN '36-50' THEN 4
            ELSE 5
        END
";

$salesByAge = $conn->query($salesByAgeSql);

$ageSalesLabels = [];
$ageSalesValues = [];

if ($salesByAge && $salesByAge->num_rows > 0) {
    while ($row = $salesByAge->fetch_assoc()) {
        $ageSalesLabels[] = $row["age_group"];
        $ageSalesValues[] = (float)$row["total_sales"];
    }
}

/* Comenzi pe grupe de vârstă */
$ordersByAgeSql = "
    SELECT
        CASE
            WHEN u.age BETWEEN 14 AND 18 THEN '14-18'
            WHEN u.age BETWEEN 19 AND 25 THEN '19-25'
            WHEN u.age BETWEEN 26 AND 35 THEN '26-35'
            WHEN u.age BETWEEN 36 AND 50 THEN '36-50'
            ELSE '50+'
        END AS age_group,
        COUNT(o.id) AS total_orders
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE u.age IS NOT NULL
    $orderDateCondition
    GROUP BY age_group
    ORDER BY
        CASE age_group
            WHEN '14-18' THEN 1
            WHEN '19-25' THEN 2
            WHEN '26-35' THEN 3
            WHEN '36-50' THEN 4
            ELSE 5
        END
";

$ordersByAge = $conn->query($ordersByAgeSql);

$ageOrdersLabels = [];
$ageOrdersValues = [];

if ($ordersByAge && $ordersByAge->num_rows > 0) {
    while ($row = $ordersByAge->fetch_assoc()) {
        $ageOrdersLabels[] = $row["age_group"];
        $ageOrdersValues[] = (int)$row["total_orders"];
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
                    Bine ai venit, <?php echo htmlspecialchars($_SESSION["user"]); ?>. Urmărește activitatea magazinului, comenzile, clienții și stocurile.
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
                    <div class="stat-label">Clienți</div>
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

    <!-- KPI analiză -->
    <section class="mb-4">
        <div class="row g-4">
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Valoare medie comandă</div>
                    <div class="stat-value"><?php echo number_format($avgOrderValue, 2); ?> lei</div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Județ activ</div>
                    <div class="stat-value"><?php echo htmlspecialchars($topCounty); ?></div>
                    <div class="text-muted small mt-1"><?php echo $topCountyOrders; ?> comenzi</div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Segment vârstă principal</div>
                    <div class="stat-value"><?php echo htmlspecialchars($topAgeGroup); ?></div>
                    <div class="text-muted small mt-1"><?php echo number_format($topAgeGroupSales, 2); ?> lei</div>
                </div>
            </div>

            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-label">Stoc critic</div>
                    <div class="stat-value"><?php echo $totalOutOfStock + $totalLowStock; ?></div>
                    <div class="text-muted small mt-1">
                        <?php echo $totalOutOfStock; ?> epuizate, <?php echo $totalLowStock; ?> reduse
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Tabele -->
    <section class="mb-4">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Comenzi recente</h5>

                    <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Client</th>
                                        <th>Vârstă</th>
                                        <th>Județ</th>
                                        <th>Total</th>
                                        <th>Data</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($order = $recentOrders->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo (int)$order["id"]; ?></td>
                                            <td><?php echo htmlspecialchars($order["username"]); ?></td>
                                            <td><?php echo $order["age"] !== null ? (int)$order["age"] : "-"; ?></td>
                                            <td><?php echo htmlspecialchars($order["county"] ?? "-"); ?></td>
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

            <div class="col-lg-5">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Top produse vândute</h5>

                    <?php if (!empty($topProductsRows)): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produs</th>
                                        <th>Bucăți</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($topProductsRows as $product): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($product["name"]); ?></td>
                                            <td><?php echo (int)$product["total_qty"]; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">Nu există vânzări suficiente pentru clasament.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Grafice vânzări -->
    <section class="mb-4">
        <div class="mb-3">
            <h4 class="fw-bold mb-1">Analiza vânzărilor</h4>
            <p class="text-muted mb-0">
                Graficele evidențiază evoluția vânzărilor, produsele performante și categoriile cu impact comercial ridicat.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Vânzări pe zile</h5>
                    <canvas id="salesChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Top produse vândute</h5>
                    <canvas id="topProductsChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Vânzări pe categorii</h5>
                    <canvas id="salesCategoryChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Cantitate vândută pe categorii</h5>
                    <canvas id="quantityCategoryChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Distribuția comenzilor după valoare</h5>
                    <canvas id="orderValueChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Produse disponibile pe categorii</h5>
                    <canvas id="productCategoryChart" height="130"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Grafice clienți -->
    <section class="mb-4">
        <div class="mb-3">
            <h4 class="fw-bold mb-1">Analiza clienților</h4>
            <p class="text-muted mb-0">
                Datele din conturile clienților permit observarea zonelor geografice și a segmentelor de vârstă cu activitate mai ridicată.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Vânzări pe județe</h5>
                    <canvas id="countySalesChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Comenzi pe județe</h5>
                    <canvas id="countyOrdersChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Vânzări pe grupe de vârstă</h5>
                    <canvas id="ageSalesChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Comenzi pe grupe de vârstă</h5>
                    <canvas id="ageOrdersChart" height="130"></canvas>
                </div>
            </div>
        </div>
    </section>

    <!-- Stocuri -->
    <section class="mb-4">
        <div class="mb-3">
            <h4 class="fw-bold mb-1">Analiza stocurilor</h4>
            <p class="text-muted mb-0">
                Monitorizarea stocurilor ajută administratorul să identifice produsele epuizate sau aflate sub pragul minim.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Stoc total pe categorii</h5>
                    <canvas id="stockChart" height="130"></canvas>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="chart-card h-100">
                    <h5 class="fw-bold mb-3">Produse care necesită reaprovizionare</h5>

                    <?php if ($restockProducts && $restockProducts->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Produs</th>
                                        <th>Categorie</th>
                                        <th>Stoc</th>
                                        <th>Minim</th>
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
            </div>
        </div>
    </section>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const salesLabels = <?php echo json_encode($salesLabels); ?>;
const salesValues = <?php echo json_encode($salesValues); ?>;

const topProductLabels = <?php echo json_encode($topProductLabels); ?>;
const topProductValues = <?php echo json_encode($topProductValues); ?>;

const productCategoryLabels = <?php echo json_encode($productCategoryLabels); ?>;
const productCategoryValues = <?php echo json_encode($productCategoryValues); ?>;

const salesCategoryLabels = <?php echo json_encode($salesCategoryLabels); ?>;
const salesCategoryValues = <?php echo json_encode($salesCategoryValues); ?>;

const quantityCategoryLabels = <?php echo json_encode($quantityCategoryLabels); ?>;
const quantityCategoryValues = <?php echo json_encode($quantityCategoryValues); ?>;

const orderValueLabels = <?php echo json_encode($orderValueLabels); ?>;
const orderValueValues = <?php echo json_encode($orderValueValues); ?>;

const countySalesLabels = <?php echo json_encode($countySalesLabels); ?>;
const countySalesValues = <?php echo json_encode($countySalesValues); ?>;

const countyOrdersLabels = <?php echo json_encode($countyOrdersLabels); ?>;
const countyOrdersValues = <?php echo json_encode($countyOrdersValues); ?>;

const ageSalesLabels = <?php echo json_encode($ageSalesLabels); ?>;
const ageSalesValues = <?php echo json_encode($ageSalesValues); ?>;

const ageOrdersLabels = <?php echo json_encode($ageOrdersLabels); ?>;
const ageOrdersValues = <?php echo json_encode($ageOrdersValues); ?>;

const stockLabels = <?php echo json_encode($stockChartLabels); ?>;
const stockValues = <?php echo json_encode($stockChartValues); ?>;

function createChart(canvasId, type, labels, values, labelText) {
    const ctx = document.getElementById(canvasId);

    if (!ctx) {
        return;
    }

    new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                label: labelText,
                data: values,
                borderWidth: 2,
                tension: 0.35,
                fill: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom'
                }
            },
            scales: type === 'pie' || type === 'doughnut' ? {} : {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

createChart('salesChart', 'line', salesLabels, salesValues, 'Vânzări (lei)');
createChart('topProductsChart', 'bar', topProductLabels, topProductValues, 'Bucăți vândute');
createChart('salesCategoryChart', 'bar', salesCategoryLabels, salesCategoryValues, 'Vânzări (lei)');
createChart('quantityCategoryChart', 'bar', quantityCategoryLabels, quantityCategoryValues, 'Bucăți vândute');
createChart('orderValueChart', 'doughnut', orderValueLabels, orderValueValues, 'Comenzi');
createChart('productCategoryChart', 'doughnut', productCategoryLabels, productCategoryValues, 'Produse');

createChart('countySalesChart', 'bar', countySalesLabels, countySalesValues, 'Vânzări (lei)');
createChart('countyOrdersChart', 'bar', countyOrdersLabels, countyOrdersValues, 'Comenzi');
createChart('ageSalesChart', 'bar', ageSalesLabels, ageSalesValues, 'Vânzări (lei)');
createChart('ageOrdersChart', 'bar', ageOrdersLabels, ageOrdersValues, 'Comenzi');

createChart('stockChart', 'bar', stockLabels, stockValues, 'Stoc total');
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>