<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cartCount = 0;
if (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
    foreach ($_SESSION["cart"] as $item) {
        $cartCount += (int)($item["quantity"] ?? 0);
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TuryShop</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/turyshop/assets/css/custom.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<body>

<nav class="navbar navbar-expand-lg">
  <div class="container">

    <a class="navbar-brand" href="/turyshop/index.php">
      <span class="brand-mark">T</span>uryShop
    </a>

    <button
      class="navbar-toggler"
      type="button"
      data-bs-toggle="collapse"
      data-bs-target="#nav"
      aria-controls="nav"
      aria-expanded="false"
      aria-label="Deschide meniul"
    >
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">

      <ul class="navbar-nav me-auto gap-1">
        <li class="nav-item">
          <a class="nav-link" href="/turyshop/index.php">Acasă</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/turyshop/about.php">Despre noi</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/turyshop/products.php">Produse</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/turyshop/gallery.php">Galerie</a>
        </li>

        <li class="nav-item">
          <a class="nav-link" href="/turyshop/size-guide.php">Ghid mărimi</a>
        </li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">

        <?php if (empty($_SESSION["user"])): ?>

          <li class="nav-item">
            <a class="nav-link" href="/turyshop/auth/login.php">Autentificare</a>
          </li>

          <li class="nav-item">
            <a class="nav-link" href="/turyshop/auth/register.php">Înregistrare</a>
          </li>

        <?php else: ?>

          <li class="nav-item">
            <span class="nav-link">
              Salut, <?php echo htmlspecialchars($_SESSION["user"]); ?>
            </span>
          </li>

          <?php if (!empty($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>

            <li class="nav-item">
              <a class="nav-link" href="/turyshop/admin/dashboard.php">Dashboard</a>
            </li>

            <li class="nav-item">
              <a class="nav-link" href="/turyshop/admin/stock.php">Stocuri</a>
            </li>

          <?php else: ?>

            <li class="nav-item">
              <button
                type="button"
                class="nav-link gift-link border-0 bg-transparent"
                data-bs-toggle="modal"
                data-bs-target="#giftModal"
                aria-label="Surpriza zilei"
              >
                <i class="bi bi-gift fs-5"></i>
                <span class="gift-dot"></span>
              </button>
            </li>

            <li class="nav-item">
              <a class="nav-link cart-link" href="/turyshop/cart.php" aria-label="Coș">
                <i class="bi bi-bag fs-5"></i>

                <?php if ($cartCount > 0): ?>
                  <span class="cart-badge">
                    <?php echo $cartCount; ?>
                  </span>
                <?php endif; ?>
              </a>
            </li>

          <?php endif; ?>

          <li class="nav-item">
            <a class="nav-link" href="/turyshop/auth/logout.php">Ieșire</a>
          </li>

        <?php endif; ?>

      </ul>

    </div>
  </div>
</nav>

<?php if (!empty($_SESSION["flash"]) && is_array($_SESSION["flash"])): ?>
  <div class="container mt-3">
    <div class="alert alert-<?php echo htmlspecialchars($_SESSION["flash"]["type"]); ?> mb-0">
      <?php echo htmlspecialchars($_SESSION["flash"]["message"]); ?>
    </div>
  </div>

  <?php unset($_SESSION["flash"]); ?>
<?php endif; ?>

<?php if (!empty($_SESSION["user"]) && ($_SESSION["role"] ?? "") === "client"): ?>
  <div class="modal fade" id="giftModal" tabindex="-1" aria-labelledby="giftModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content rounded-4 border-0">
        <div class="modal-header border-0 pb-0">
          <div>
            <span class="badge text-bg-dark mb-2 px-3 py-2">Surpriza zilei</span>
            <h5 class="modal-title fw-bold" id="giftModalLabel">Descoperă cadoul tău</h5>
          </div>

          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Închide"></button>
        </div>

        <div class="modal-body text-center px-4 pb-4">
          <div class="gift-wheel mx-auto mb-3">
            <i class="bi bi-gift-fill"></i>
          </div>

          <p class="text-muted mb-3">
            Apasă pe buton și descoperă ce cadou primești pentru următoarea comandă.
          </p>

          <button type="button" class="btn btn-dark w-100 mb-3" id="spinGiftBtn">
            Descoperă surpriza
          </button>

          <div id="giftResult" class="gift-result d-none">
            <div class="fw-bold mb-1" id="giftTitle"></div>
            <div class="text-muted small" id="giftDescription"></div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const spinBtn = document.getElementById("spinGiftBtn");
      const resultBox = document.getElementById("giftResult");
      const giftTitle = document.getElementById("giftTitle");
      const giftDescription = document.getElementById("giftDescription");

      if (!spinBtn || !resultBox || !giftTitle || !giftDescription) {
        return;
      }

      const gifts = [
        {
          title: "Ai câștigat 10% reducere",
          description: "Folosește codul TURY10 la următoarea comandă."
        },
        {
          title: "Ai câștigat transport gratuit",
          description: "Cod cadou: FREEDELIVERY pentru comenzile de peste 300 lei."
        },
        {
          title: "Ai câștigat un cadou surpriză",
          description: "La următoarea comandă primești un accesoriu ales special pentru tine."
        },
        {
          title: "Ai câștigat 15% reducere la accesorii",
          description: "Cod cadou: ACCESORII15 pentru produsele din categoria Accesorii."
        },
        {
          title: "Ai câștigat o ofertă specială",
          description: "Cod cadou: TURYSHOP pentru un avantaj promoțional la următoarea comandă."
        }
      ];


      spinBtn.addEventListener("click", function () {
        const randomGift = gifts[Math.floor(Math.random() * gifts.length)];

        spinBtn.disabled = true;
        spinBtn.textContent = "Se pregătește surpriza...";

        setTimeout(function () {
          giftTitle.textContent = randomGift.title;
          giftDescription.textContent = randomGift.description;
          resultBox.classList.remove("d-none");

          spinBtn.textContent = "Surpriză descoperită";
        }, 700);
      });
    });
  </script>
<?php endif; ?>

<main>