<?php
require __DIR__ . "/config/db.php";
include __DIR__ . "/includes/header.php";

/* Luăm 6 produse pentru homepage */
$featuredProducts = $conn->query("
  SELECT p.id, p.name, p.description, p.price, p.size, p.image, c.name AS category_name
  FROM products p
  JOIN categories c ON c.id = p.category_id
  ORDER BY p.created_at DESC
  LIMIT 6
");

$placeholderImg = "assets/img/products/placeholder.jpg";
?>

<div class="container py-4">

  <!-- HERO -->
  <section class="mb-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <div class="pe-lg-4">
          <span class="badge text-bg-dark mb-3 px-3 py-2">Colecție feminină modernă</span>
          <h1 class="display-5 fw-bold mb-3">
            Eleganță, stil și inspirație pentru fiecare ținută
          </h1>
          <p class="text-muted fs-5 mb-4">
            TuryShop este un magazin online dedicat articolelor vestimentare și accesoriilor pentru femei,
            realizat ca proiect practic, cu accent pe design modern, funcționalitate și recomandarea produselor compatibile.
          </p>

          <div class="d-flex flex-wrap gap-2">
            <a href="/turyshop/products.php" class="btn btn-dark px-4">Vezi produsele</a>
            <a href="/turyshop/size-guide.php" class="btn btn-outline-dark px-4">Ghid mărimi</a>
          </div>
        </div>
      </div>

      <div class="col-lg-6">
        <div class="product-detail-box p-0 overflow-hidden">
          <img
            src="/turyshop/assets/img/products/geanta-mini-roz.jpg"
            alt="Colecție TuryShop"
            class="product-detail-image"
          >
        </div>
      </div>
    </div>
  </section>

  <!-- CATEGORII -->
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Categorii principale</h2>
      <a href="/turyshop/products.php" class="text-muted">Vezi tot catalogul</a>
    </div>

    <div class="row g-3">
      <div class="col-md-4 col-6">
        <a class="text-decoration-none" href="/turyshop/products.php?cat_id=1">
          <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover">
            <h5 class="fw-bold mb-1">Rochii</h5>
            <p class="text-muted mb-0">Modele elegante pentru evenimente, birou sau ieșiri speciale.</p>
          </div>
        </a>
      </div>

      <div class="col-md-4 col-6">
        <a class="text-decoration-none" href="/turyshop/products.php?cat_id=2">
          <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover">
            <h5 class="fw-bold mb-1">Sacouri</h5>
            <p class="text-muted mb-0">Piese feminine și versatile pentru ținute office sau smart casual.</p>
          </div>
        </a>
      </div>

      <div class="col-md-4 col-6">
        <a class="text-decoration-none" href="/turyshop/products.php?cat_id=3">
          <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover">
            <h5 class="fw-bold mb-1">Fuste</h5>
            <p class="text-muted mb-0">Croieli moderne și elegante pentru outfituri rafinate.</p>
          </div>
        </a>
      </div>

      <div class="col-md-4 col-6">
        <a class="text-decoration-none" href="/turyshop/products.php?cat_id=4">
          <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover">
            <h5 class="fw-bold mb-1">Accesorii</h5>
            <p class="text-muted mb-0">Detalii care completează și pun în valoare orice ținută.</p>
          </div>
        </a>
      </div>

      <div class="col-md-4 col-6">
        <a class="text-decoration-none" href="/turyshop/products.php?cat_id=5">
          <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover">
            <h5 class="fw-bold mb-1">Bluze</h5>
            <p class="text-muted mb-0">Articole feminine pentru ținute office, casual și elegante.</p>
          </div>
        </a>
      </div>

      <div class="col-md-4 col-6">
        <a class="text-decoration-none" href="/turyshop/products.php?cat_id=6">
          <div class="card h-100 border-0 shadow-sm rounded-4 p-4 card-hover">
            <h5 class="fw-bold mb-1">Genți</h5>
            <p class="text-muted mb-0">Modele practice și stilate pentru fiecare moment al zilei.</p>
          </div>
        </a>
      </div>
    </div>
  </section>

  <!-- PRODUSE EVIDENȚIATE -->
  <section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2 class="section-title mb-0">Produse evidențiate</h2>
      <a href="/turyshop/products.php" class="text-muted">Vezi toate produsele</a>
    </div>

    <div class="row g-4">
      <?php if ($featuredProducts && $featuredProducts->num_rows > 0): ?>
        <?php while ($p = $featuredProducts->fetch_assoc()): ?>
          <?php
            $img = !empty($p["image"]) ? trim($p["image"]) : $placeholderImg;

            if (strpos($img, "http://") === 0 || strpos($img, "https://") === 0) {
              $imgUrl = $img;
            } else {
              $imgUrl = "/turyshop/" . ltrim($img, "/");
            }

            $desc = trim($p["description"] ?? "");
          ?>

          <div class="col-sm-6 col-lg-4">
            <div class="card product-card h-100">
              <img
                src="<?php echo htmlspecialchars($imgUrl); ?>"
                alt="<?php echo htmlspecialchars($p["name"]); ?>"
                class="card-img-top"
              >

              <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                  <h5 class="card-title mb-0"><?php echo htmlspecialchars($p["name"]); ?></h5>
                  <span class="badge text-bg-dark"><?php echo htmlspecialchars($p["category_name"]); ?></span>
                </div>

                <p class="card-text">
                  <?php echo htmlspecialchars(mb_strimwidth($desc, 0, 95, "...")); ?>
                </p>

                <div class="d-flex justify-content-between align-items-center mb-3">
                  <div class="product-price mb-0">
                    <?php echo number_format((float)$p["price"], 2); ?> lei
                  </div>

                  <?php if (!empty($p["size"])): ?>
                    <span class="product-meta">Mărime: <?php echo htmlspecialchars($p["size"]); ?></span>
                  <?php endif; ?>
                </div>

                <div class="product-actions mt-auto">
                  <a class="btn btn-outline-dark w-100" href="/turyshop/product.php?id=<?php echo (int)$p["id"]; ?>">
                    Vezi detalii
                  </a>

                  <?php if (!empty($_SESSION["user"]) && (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin")): ?>
                    <form action="/turyshop/add_to_cart.php" method="post">
                      <input type="hidden" name="product_id" value="<?php echo (int)$p["id"]; ?>">
                      <input type="hidden" name="quantity" value="1">
                      <button type="submit" class="btn btn-dark w-100">Adaugă în coș</button>
                    </form>
                  <?php elseif (empty($_SESSION["user"])): ?>
                    <a class="btn btn-dark w-100" href="/turyshop/auth/login.php">Autentifică-te pentru comandă</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- DESPRE / BENEFICII -->
  <section class="mb-5">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h4 class="fw-bold mb-2">Selecție feminină</h4>
          <p class="text-muted mb-0">
            Produse organizate pe categorii relevante, pentru o experiență clară și ușor de parcurs.
          </p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h4 class="fw-bold mb-2">Experiență modernă</h4>
          <p class="text-muted mb-0">
            Magazinul include autentificare, coș de cumpărături, filtrare produse și interfață administrativă.
          </p>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h4 class="fw-bold mb-2">Modul personalizat</h4>
          <p class="text-muted mb-0">
            Elementul central al proiectului este recomandarea produselor compatibile cu articolul selectat.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section>
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center">
      <h2 class="fw-bold mb-3">Descoperă colecția TuryShop</h2>
      <p class="text-muted mb-4">
        Explorează articole vestimentare și accesorii gândite pentru ținute feminine, elegante și coerente.
      </p>
      <div class="d-flex justify-content-center flex-wrap gap-2">
        <a href="/turyshop/products.php" class="btn btn-dark px-4">Intră în magazin</a>
        <a href="/turyshop/about.php" class="btn btn-outline-dark px-4">Despre proiect</a>
      </div>
    </div>
  </section>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>