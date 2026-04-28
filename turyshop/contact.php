<?php
include __DIR__ . "/includes/header.php";

$success = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = trim($_POST["name"] ?? "");
  $email = trim($_POST["email"] ?? "");
  $message = trim($_POST["message"] ?? "");

  if ($name === "" || $email === "" || $message === "") {
    $errors[] = "Completează toate câmpurile.";
  }

  if ($email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email invalid.";
  }

  if (!$errors) {
    $success = "Mesaj trimis cu succes! (Demo proiect)";
  }
}
?>

<div class="container" style="max-width: 700px;">
  <h2 class="fw-bold mb-3">Contact</h2>

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
      <label class="form-label">Nume</label>
      <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>">
    </div>

    <div class="mb-3">
      <label class="form-label">Mesaj</label>
      <textarea name="message" rows="5" class="form-control" required><?php echo htmlspecialchars($_POST["message"] ?? ""); ?></textarea>
    </div>

    <button class="btn btn-tury text-white w-100">Trimite</button>
  </form>
</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
