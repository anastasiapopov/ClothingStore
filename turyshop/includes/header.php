<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">

      <ul class="navbar-nav me-auto gap-1">
        <li class="nav-item"><a class="nav-link" href="/turyshop/index.php">Acasă</a></li>
        <li class="nav-item"><a class="nav-link" href="/turyshop/about.php">Despre noi</a></li>
        <li class="nav-item"><a class="nav-link" href="/turyshop/products.php">Produse</a></li>
        <li class="nav-item"><a class="nav-link" href="/turyshop/gallery.php">Galerie</a></li>
        <li class="nav-item"><a class="nav-link" href="/turyshop/size-guide.php">Ghid mărimi</a></li>
      </ul>

      <ul class="navbar-nav ms-auto align-items-lg-center gap-2">
        <?php if (empty($_SESSION["user"])): ?>
          <li class="nav-item"><a class="nav-link" href="/turyshop/auth/login.php">Autentificare</a></li>
          <li class="nav-item"><a class="nav-link" href="/turyshop/auth/register.php">Înregistrare</a></li>
        <?php else: ?>

          <li class="nav-item">
            <span class="nav-link">Salut, <?php echo htmlspecialchars($_SESSION["user"]); ?></span>
          </li>

          <?php if (!empty($_SESSION["role"]) && $_SESSION["role"] === "admin"): ?>
            <li class="nav-item">
              <a class="nav-link" href="/turyshop/admin/dashboard.php">Dashboard</a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link cart-link" href="/turyshop/cart.php" aria-label="Coș">
                <i class="bi bi-bag fs-5"></i>
                <?php if ($cartCount > 0): ?>
                  <span class="cart-badge"><?php echo $cartCount; ?></span>
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

<main>
<main>