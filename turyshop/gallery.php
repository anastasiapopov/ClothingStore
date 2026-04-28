<?php
require __DIR__ . "/config/db.php";
include __DIR__ . "/includes/header.php";

$stmt = $conn->prepare("
  SELECT id, name, image
  FROM products
  WHERE image IS NOT NULL AND image <> ''
  ORDER BY created_at DESC
");
$stmt->execute();
$res = $stmt->get_result();
?>

<div class="container">
  <h2 class="fw-bold mb-3">Galerie</h2>
  <p class="text-muted">Produsele noastre (imagini din baza de date).</p>

  <div class="row g-3">

    <?php if ($res->num_rows === 0): ?>
      <div class="col-12">
        <div class="alert alert-warning">
          Nu există încă imagini salvate în produse.<br>
          Adaugă poze în <code>assets/img/products</code> și completează coloana <code>image</code>.
        </div>
      </div>
    <?php endif; ?>

    <?php while ($p = $res->fetch_assoc()): ?>
      <div class="col-6 col-md-4 col-lg-3">
        <a class="text-decoration-none" href="product.php?id=<?php echo (int)$p["id"]; ?>">
          <div class="border rounded-4 overflow-hidden h-100 card-hover">
            <img src="<?php echo htmlspecialchars($p["image"]); ?>" class="img-fluid" alt="<?php echo htmlspecialchars($p["name"]); ?>">
            <div class="p-2">
              <div class="small fw-semibold"><?php echo htmlspecialchars($p["name"]); ?></div>
            </div>
          </div>
        </a>
      </div>
    <?php endwhile; ?>

  </div>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
