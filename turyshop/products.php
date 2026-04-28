<?php
require __DIR__ . "/config/db.php";
include __DIR__ . "/includes/header.php";

/* Luăm categoriile pentru dropdown */
$categories = [];
$resCat = $conn->query("SELECT id, name FROM categories ORDER BY name");
while ($row = $resCat->fetch_assoc()) {
  $categories[] = $row;
}

/* Filtre din URL */
$q         = trim($_GET["q"] ?? "");
$cat_id    = (int)($_GET["cat_id"] ?? 0);
$size      = trim($_GET["size"] ?? "");
$min_price = trim($_GET["min_price"] ?? "");
$max_price = trim($_GET["max_price"] ?? "");

/* Normalizăm prețurile */
$min_price_val = ($min_price !== "" && is_numeric($min_price)) ? (float)$min_price : null;
$max_price_val = ($max_price !== "" && is_numeric($max_price)) ? (float)$max_price : null;

/* Query produse */
$sql = "SELECT p.id, p.name, p.description, p.price, p.size, p.image, p.stock, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE 1=1";

$params = [];
$types  = "";

/* căutare după nume */
if ($q !== "") {
  $sql .= " AND p.name LIKE ?";
  $params[] = "%" . $q . "%";
  $types .= "s";
}

/* filtru categorie */
if ($cat_id > 0) {
  $sql .= " AND p.category_id = ?";
  $params[] = $cat_id;
  $types .= "i";
}

/* filtru mărime */
$allowed_sizes = ["XS","S","M","L","XL","XXL"];
if ($size !== "" && in_array($size, $allowed_sizes, true)) {
  $sql .= " AND p.size = ?";
  $params[] = $size;
  $types .= "s";
}

/* preț minim */
if ($min_price_val !== null) {
  $sql .= " AND p.price >= ?";
  $params[] = $min_price_val;
  $types .= "d";
}

/* preț maxim */
if ($max_price_val !== null) {
  $sql .= " AND p.price <= ?";
  $params[] = $max_price_val;
  $types .= "d";
}

$sql .= " ORDER BY p.created_at DESC";

/* Executăm query-ul */
$stmt = $conn->prepare($sql);
if (!empty($params)) {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

/* helper pentru selected */
function selected($a, $b) {
  return ($a === $b) ? "selected" : "";
}

$placeholderImg = "assets/img/products/placeholder.jpg";
?>

<div class="container py-4">

  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
    <div>
      <h2 class="fw-bold mb-1">Produse</h2>
      <p class="text-muted mb-0">Descoperă colecția TuryShop și filtrează produsele după preferințe.</p>
    </div>
    <a class="btn btn-outline-secondary" href="/turyshop/products.php">Resetează filtrele</a>
  </div>

  <!-- FILTRE -->
  <form method="get" class="card border-0 shadow-sm rounded-4 p-3 p-md-4 mb-4">
    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Caută produs</label>
        <input
          type="text"
          name="q"
          class="form-control"
          placeholder="Ex: rochie, geantă, sacou"
          value="<?php echo htmlspecialchars($q); ?>"
        >
      </div>

      <div class="col-md-3">
        <label class="form-label">Categorie</label>
        <select name="cat_id" class="form-select">
          <option value="0">Toate</option>
          <?php foreach ($categories as $c): ?>
            <option
              value="<?php echo (int)$c["id"]; ?>"
              <?php echo ((int)$cat_id === (int)$c["id"]) ? "selected" : ""; ?>
            >
              <?php echo htmlspecialchars($c["name"]); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2">
        <label class="form-label">Mărime</label>
        <select name="size" class="form-select">
          <option value="">Toate</option>
          <?php foreach (["XS","S","M","L","XL","XXL"] as $s): ?>
            <option value="<?php echo $s; ?>" <?php echo selected($size, $s); ?>>
              <?php echo $s; ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-1">
        <label class="form-label">Min</label>
        <input
          type="number"
          step="0.01"
          name="min_price"
          class="form-control"
          value="<?php echo htmlspecialchars($min_price); ?>"
        >
      </div>

      <div class="col-md-1">
        <label class="form-label">Max</label>
        <input
          type="number"
          step="0.01"
          name="max_price"
          class="form-control"
          value="<?php echo htmlspecialchars($max_price); ?>"
        >
      </div>

      <div class="col-md-1 d-grid">
        <label class="form-label d-none d-md-block">&nbsp;</label>
        <button class="btn btn-dark">Aplică</button>
      </div>
    </div>
  </form>

  <!-- LISTA PRODUSE -->
  <div class="row g-4">

    <?php if ($result->num_rows === 0): ?>
      <div class="col-12">
        <div class="alert alert-warning mb-0">
          Nu s-au găsit produse pentru filtrele selectate.
        </div>
      </div>
    <?php endif; ?>

    <?php while ($p = $result->fetch_assoc()): ?>
      <?php
        $img = !empty($p["image"]) ? trim($p["image"]) : $placeholderImg;

        if (strpos($img, "http://") === 0 || strpos($img, "https://") === 0) {
          $imgUrl = $img;
        } else {
          $imgUrl = "/turyshop/" . ltrim($img, "/");
        }

        $desc = trim($p["description"] ?? "");
        $stock = (int)($p["stock"] ?? 0);
      ?>

      <div class="col-sm-6 col-lg-4">
        <div class="card product-card h-100">

          <img
            src="<?php echo htmlspecialchars($imgUrl); ?>"
            class="card-img-top"
            alt="<?php echo htmlspecialchars($p["name"]); ?>"
          >

          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
              <h5 class="card-title mb-0">
                <?php echo htmlspecialchars($p["name"]); ?>
              </h5>
              <span class="badge text-bg-dark">
                <?php echo htmlspecialchars($p["category_name"]); ?>
              </span>
            </div>

            <p class="card-text">
              <?php echo htmlspecialchars(mb_strimwidth($desc, 0, 95, "...")); ?>
            </p>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="product-price mb-0">
                <?php echo number_format((float)$p["price"], 2); ?> lei
              </div>

              <?php if (!empty($p["size"])): ?>
                <span class="product-meta">Mărime: <?php echo htmlspecialchars($p["size"]); ?></span>
              <?php endif; ?>
            </div>

            <?php if ($stock > 0): ?>
              <div class="product-meta mb-3">Stoc disponibil: <?php echo $stock; ?></div>
            <?php else: ?>
              <div class="product-meta mb-3 text-danger fw-semibold">Stoc epuizat</div>
            <?php endif; ?>

            <div class="product-actions mt-auto">
              <a class="btn btn-outline-dark w-100" href="/turyshop/product.php?id=<?php echo (int)$p["id"]; ?>">
                Vezi detalii
              </a>

              <?php if (!empty($_SESSION["user"]) && (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin")): ?>
                <?php if ($stock > 0): ?>
                  <form action="/turyshop/add_to_cart.php" method="post">
                    <input type="hidden" name="product_id" value="<?php echo (int)$p["id"]; ?>">
                    <input type="hidden" name="quantity" value="1">
                    <button type="submit" class="btn btn-dark w-100">Adaugă în coș</button>
                  </form>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-secondary w-100" disabled>Stoc epuizat</button>
                <?php endif; ?>

              <?php elseif (empty($_SESSION["user"])): ?>
                <?php if ($stock > 0): ?>
                  <a class="btn btn-dark w-100" href="/turyshop/auth/login.php">Autentifică-te pentru comandă</a>
                <?php else: ?>
                  <button type="button" class="btn btn-outline-secondary w-100" disabled>Stoc epuizat</button>
                <?php endif; ?>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>
    <?php endwhile; ?>

  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>