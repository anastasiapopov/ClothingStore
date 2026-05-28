<?php
require __DIR__ . "/../config/db.php";
include __DIR__ . "/../includes/header.php";

$errors = [];
$success = "";

$counties = [
  "Alba", "Arad", "Argeș", "Bacău", "Bihor", "Bistrița-Năsăud", "Botoșani",
  "Brașov", "Brăila", "București", "Buzău", "Caraș-Severin", "Călărași",
  "Cluj", "Constanța", "Covasna", "Dâmbovița", "Dolj", "Galați", "Giurgiu",
  "Gorj", "Harghita", "Hunedoara", "Ialomița", "Iași", "Ilfov", "Maramureș",
  "Mehedinți", "Mureș", "Neamț", "Olt", "Prahova", "Satu Mare", "Sălaj",
  "Sibiu", "Suceava", "Teleorman", "Timiș", "Tulcea", "Vaslui", "Vâlcea",
  "Vrancea"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";
  $password2 = $_POST["password2"] ?? "";
  $age = (int)($_POST["age"] ?? 0);
  $county = trim($_POST["county"] ?? "");

  if ($username === "" || $password === "" || $password2 === "" || $age <= 0 || $county === "") {
    $errors[] = "Completează toate câmpurile.";
  }

  if (strlen($username) < 3) {
    $errors[] = "Username trebuie să aibă minim 3 caractere.";
  }

  if (strlen($password) < 4) {
    $errors[] = "Parola trebuie să aibă minim 4 caractere.";
  }

  if ($password !== $password2) {
    $errors[] = "Parolele nu coincid.";
  }

  if ($age < 14 || $age > 100) {
    $errors[] = "Vârsta trebuie să fie între 14 și 100 de ani.";
  }

  if ($county !== "" && !in_array($county, $counties, true)) {
    $errors[] = "Județul selectat nu este valid.";
  }

  if (!$errors) {
    // verificăm dacă username-ul există deja
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
      $errors[] = "Acest username este deja folosit.";
    } else {
      $hash = password_hash($password, PASSWORD_DEFAULT);

      $stmt2 = $conn->prepare("
        INSERT INTO users (username, password_hash, role, age, county)
        VALUES (?, ?, 'client', ?, ?)
      ");
      $stmt2->bind_param("ssis", $username, $hash, $age, $county);

      if ($stmt2->execute()) {
        $success = "Cont creat cu succes! Acum te poți loga.";
      } else {
        $errors[] = "A apărut o eroare la crearea contului.";
      }
    }
  }
}
?>

<div class="container py-4" style="max-width: 620px;">

  <div class="mb-4 text-center">
    <span class="badge text-bg-dark mb-2 px-3 py-2">Cont client</span>
    <h2 class="fw-bold mb-1">Înregistrare</h2>
    <p class="text-muted mb-0">
      Creează un cont pentru a putea adăuga produse în coș și finaliza comenzi.
    </p>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
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

  <form method="post" class="product-detail-box">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input
        type="text"
        name="username"
        class="form-control"
        required
        value="<?php echo htmlspecialchars($_POST["username"] ?? ""); ?>"
      >
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <div class="mb-3">
          <label class="form-label">Vârstă</label>
          <input
            type="number"
            name="age"
            class="form-control"
            min="14"
            max="100"
            required
            value="<?php echo htmlspecialchars($_POST["age"] ?? ""); ?>"
          >
        </div>
      </div>

      <div class="col-md-6">
        <div class="mb-3">
          <label class="form-label">Județ</label>
          <select name="county" class="form-select" required>
            <option value="">Alege județul</option>
            <?php foreach ($counties as $c): ?>
              <option
                value="<?php echo htmlspecialchars($c); ?>"
                <?php echo (($_POST["county"] ?? "") === $c) ? "selected" : ""; ?>
              >
                <?php echo htmlspecialchars($c); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Parolă</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirmă parola</label>
      <input type="password" name="password2" class="form-control" required>
    </div>

    <button class="btn btn-dark w-100">Creează cont</button>

    <div class="mt-3 text-center">
      <span class="text-muted">Ai deja cont?</span>
      <a href="/turyshop/auth/login.php">Autentificare</a>
    </div>
  </form>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>