<?php
require __DIR__ . "/config/db.php";
include __DIR__ . "/includes/header.php";

$id = (int)($_GET["id"] ?? 0);

if ($id <= 0) {
  echo '<div class="container py-4"><div class="alert alert-danger">Produs invalid.</div></div>';
  include __DIR__ . "/includes/footer.php";
  exit;
}

$stmt = $conn->prepare("
  SELECT p.id, p.category_id, p.name, p.description, p.price, p.size, p.image, p.stock, p.min_stock, c.name AS category_name
  FROM products p
  JOIN categories c ON c.id = p.category_id
  WHERE p.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows !== 1) {
  echo '<div class="container py-4"><div class="alert alert-warning">Produsul nu a fost găsit.</div></div>';
  include __DIR__ . "/includes/footer.php";
  exit;
}

$p = $res->fetch_assoc();

/* Imagine produs */
$img = !empty($p["image"]) ? trim($p["image"]) : "assets/img/products/placeholder.jpg";

if (strpos($img, "http://") === 0 || strpos($img, "https://") === 0) {
  $imgUrl = $img;
} else {
  $imgUrl = "/turyshop/" . ltrim($img, "/");
}

/* Modul produse care se potrivesc */
$matchMap = [
  1 => [2, 4, 6], // Rochii -> Sacouri, Accesorii, Genți
  2 => [3, 5, 6], // Sacouri -> Fuste, Bluze, Genți
  3 => [2, 4, 5], // Fuste -> Sacouri, Accesorii, Bluze
  4 => [1, 3, 5], // Accesorii -> Rochii, Fuste, Bluze
  5 => [3, 4, 6], // Bluze -> Fuste, Accesorii, Genți
  6 => [1, 2, 5], // Genți -> Rochii, Sacouri, Bluze
];

$recommended = null;
$placeholderImg = "assets/img/products/placeholder.jpg";

$currentCategoryId = (int)$p["category_id"];

if (isset($matchMap[$currentCategoryId])) {
    $targetCategories = $matchMap[$currentCategoryId];

    $placeholders = implode(",", array_fill(0, count($targetCategories), "?"));
    $types = str_repeat("i", count($targetCategories)) . "i";

    $sqlRec = "
      SELECT id, name, price, image, stock
      FROM products
      WHERE category_id IN ($placeholders)
      AND id != ?
      ORDER BY RAND()
      LIMIT 4
    ";

    $stmtRec = $conn->prepare($sqlRec);

    $params = array_merge($targetCategories, [$id]);
    $stmtRec->bind_param($types, ...$params);

    $stmtRec->execute();
    $recommended = $stmtRec->get_result();
}

$stock = (int)($p["stock"] ?? 0);
$minStock = (int)($p["min_stock"] ?? 0);
?>

<div class="container py-4">

  <div class="mb-4">
    <a href="/turyshop/products.php" class="text-decoration-none">&larr; Înapoi la produse</a>
  </div>

  <div class="row g-4 align-items-start">
    <div class="col-lg-5">
      <div class="product-detail-box p-0 overflow-hidden">
        <img
          src="<?php echo htmlspecialchars($imgUrl); ?>"
          alt="<?php echo htmlspecialchars($p["name"]); ?>"
          class="product-detail-image"
        >
      </div>
    </div>

    <div class="col-lg-7">
      <div class="product-detail-box">
        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
          <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($p["name"]); ?></h2>
          <span class="badge text-bg-dark"><?php echo htmlspecialchars($p["category_name"]); ?></span>
        </div>

        <p class="text-muted mb-3">
          <?php echo nl2br(htmlspecialchars($p["description"] ?? "Descriere indisponibilă.")); ?>
        </p>

        <div class="product-price mb-3">
          <?php echo number_format((float)$p["price"], 2); ?> lei
        </div>

        <?php if (!empty($p["size"])): ?>
          <div class="mb-3">
            <span class="fw-semibold">Mărime:</span>
            <span class="product-meta"><?php echo htmlspecialchars($p["size"]); ?></span>
          </div>
        <?php endif; ?>

        <div class="mb-3">
          <?php if ($stock > 0): ?>
            <span class="fw-semibold">Stoc disponibil:</span>
            <span class="product-meta"><?php echo $stock; ?> buc.</span>
            <?php if ($stock <= $minStock): ?>
              <div class="text-warning mt-1 small fw-semibold">Atenție: produs cu stoc redus.</div>
            <?php endif; ?>
          <?php else: ?>
            <span class="fw-semibold text-danger">Stoc epuizat</span>
          <?php endif; ?>
        </div>

        <?php if (!empty($_SESSION["user"]) && (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin")): ?>
          <?php if ($stock > 0): ?>
            <form action="/turyshop/add_to_cart.php" method="post" class="mt-4">
              <input type="hidden" name="product_id" value="<?php echo (int)$p["id"]; ?>">

              <div class="mb-3" style="max-width: 140px;">
                <label class="form-label">Cantitate</label>
                <input
                  type="number"
                  name="quantity"
                  class="form-control"
                  value="1"
                  min="1"
                  max="<?php echo $stock; ?>"
                >
              </div>

              <div class="d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-dark">Adaugă în coș</button>
                <a href="/turyshop/products.php" class="btn btn-outline-secondary">Înapoi</a>
                <a href="/turyshop/size-guide.php" class="btn btn-outline-dark">Ghid mărimi</a>
              </div>
            </form>
          <?php else: ?>
            <div class="mt-4 d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-outline-secondary" disabled>Produs epuizat</button>
              <a href="/turyshop/products.php" class="btn btn-outline-secondary">Înapoi</a>
              <a href="/turyshop/size-guide.php" class="btn btn-outline-dark">Ghid mărimi</a>
            </div>
          <?php endif; ?>

        <?php elseif (empty($_SESSION["user"])): ?>
          <div class="mt-4 d-flex flex-wrap gap-2">
            <?php if ($stock > 0): ?>
              <a href="/turyshop/auth/login.php" class="btn btn-dark">Autentifică-te pentru comandă</a>
            <?php else: ?>
              <button type="button" class="btn btn-outline-secondary" disabled>Produs epuizat</button>
            <?php endif; ?>
            <a href="/turyshop/products.php" class="btn btn-outline-secondary">Înapoi</a>
            <a href="/turyshop/size-guide.php" class="btn btn-outline-dark">Ghid mărimi</a>
          </div>

        <?php else: ?>
          <div class="mt-4 d-flex flex-wrap gap-2">
            <a href="/turyshop/products.php" class="btn btn-outline-secondary">Înapoi</a>
            <a href="/turyshop/size-guide.php" class="btn btn-outline-dark">Ghid mărimi</a>
          </div>
        <?php endif; ?>
      </div>

      <div class="product-detail-box mt-3">
        <p class="text-muted mb-0">
          <strong>Notă:</strong> Acest proiect reprezintă un magazin online demonstrativ realizat cu PHP, MySQL și XAMPP, având un modul personalizat de recomandare a produselor compatibile.
        </p>
      </div>
    </div>
  </div>

  <?php if ($recommended && $recommended->num_rows > 0): ?>
    <div class="recommendation-section">
      <h3>Completează ținuta</h3>

      <div class="row g-4">
        <?php while ($r = $recommended->fetch_assoc()): ?>
          <?php
            $recImg = !empty($r["image"]) ? trim($r["image"]) : $placeholderImg;

            if (strpos($recImg, "http://") === 0 || strpos($recImg, "https://") === 0) {
              $recImgUrl = $recImg;
            } else {
              $recImgUrl = "/turyshop/" . ltrim($recImg, "/");
            }

            $recStock = (int)($r["stock"] ?? 0);
          ?>

          <div class="col-sm-6 col-lg-3">
            <div class="card product-card h-100">
              <img
                src="<?php echo htmlspecialchars($recImgUrl); ?>"
                alt="<?php echo htmlspecialchars($r["name"]); ?>"
                class="card-img-top"
              >

              <div class="card-body d-flex flex-column">
                <h6 class="fw-bold mb-2"><?php echo htmlspecialchars($r["name"]); ?></h6>

                <div class="product-price mb-2">
                  <?php echo number_format((float)$r["price"], 2); ?> lei
                </div>

                <?php if ($recStock > 0): ?>
                  <div class="product-meta mb-3">Stoc: <?php echo $recStock; ?></div>
                <?php else: ?>
                  <div class="product-meta mb-3 text-danger fw-semibold">Stoc epuizat</div>
                <?php endif; ?>

                <a
                  href="/turyshop/product.php?id=<?php echo (int)$r["id"]; ?>"
                  class="btn btn-outline-dark mt-auto"
                >
                  Vezi produs
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  <?php endif; ?>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>