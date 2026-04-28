<?php
require __DIR__ . "/../config/db.php";
include __DIR__ . "/../includes/header.php";

$errors = [];
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $username = trim($_POST["username"] ?? "");
  $password = $_POST["password"] ?? "";
  $password2 = $_POST["password2"] ?? "";

  if ($username === "" || $password === "" || $password2 === "") {
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

      $stmt2 = $conn->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'client')");
      $stmt2->bind_param("ss", $username, $hash);
      $stmt2->execute();

      $success = "Cont creat cu succes! Acum te poți loga.";
    }
  }
}
?>

<div class="container" style="max-width: 520px;">
  <h2 class="fw-bold mb-3">Register</h2>

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

  <form method="post" class="border rounded-4 p-4">
    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" required
             value="<?php echo htmlspecialchars($_POST["username"] ?? ""); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Parolă</label>
      <input type="password" name="password" class="form-control" required>
    </div>

    <div class="mb-3">
      <label class="form-label">Confirmă parola</label>
      <input type="password" name="password2" class="form-control" required>
    </div>

    <button class="btn btn-tury text-white w-100">Creează cont</button>

    <div class="mt-3 text-center">
      <span class="text-muted">Ai deja cont?</span>
      <a href="/turyshop/auth/login.php">Login</a>
    </div>
  </form>
</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>