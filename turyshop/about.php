<?php include __DIR__ . "/includes/header.php"; ?>

<div class="container py-4">

  <!-- INTRO -->
  <section class="mb-5">
    <div class="row g-4 align-items-center">
      <div class="col-lg-6">
        <span class="badge text-bg-dark mb-3 px-3 py-2">Despre proiect</span>
        <h1 class="display-6 fw-bold mb-3">TuryShop – magazin online pentru articole vestimentare și accesorii feminine</h1>
        <p class="text-muted fs-5 mb-0">
          TuryShop este un proiect de tip e-commerce realizat pentru a oferi o experiență modernă de navigare,
          filtrare și prezentare a produselor vestimentare pentru femei, într-o interfață elegantă și coerentă vizual.
        </p>
      </div>

      <div class="col-lg-6">
        <div class="product-detail-box p-0 overflow-hidden">
          <img
            src="/turyshop/assets/img/products/sacou-cambrat-albastru.jpg"
            alt="Despre TuryShop"
            class="product-detail-image"
          >
        </div>
      </div>
    </div>
  </section>

  <!-- DESCRIERE -->
  <section class="mb-5">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="product-detail-box h-100">
          <h3 class="fw-bold mb-3">Conceptul TuryShop</h3>

          <p class="text-muted">
            TuryShop este conceput ca un magazin online dedicat femeilor care caută produse vestimentare
            elegante, feminine și ușor de asortat. Structura site-ului urmărește o organizare clară a categoriilor,
            o navigare intuitivă și o experiență plăcută de utilizare.
          </p>

          <p class="text-muted">
            Proiectul integrează funcționalități esențiale pentru o platformă e-commerce, precum autentificarea utilizatorilor,
            diferențierea între client și administrator, afișarea produselor din baza de date, filtrarea acestora,
            coșul de cumpărături și dashboard-ul de administrare.
          </p>

          <p class="text-muted mb-0">
            Elementul central al aplicației este modulul personalizat de recomandare a produselor compatibile,
            care urmărește să sugereze articole potrivite în funcție de produsul vizualizat de utilizator.
            Acest modul reprezintă componenta distinctivă a proiectului și baza practică a lucrării de licență.
          </p>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="product-detail-box h-100">
          <h3 class="fw-bold mb-3">Ce oferă platforma</h3>

          <ul class="footer-links">
            <li>Rochii, sacouri, fuste, bluze, accesorii și genți</li>
            <li>Filtrare după categorie, mărime și interval de preț</li>
            <li>Autentificare separată pentru client și administrator</li>
            <li>Coș de cumpărături pentru utilizatorii autentificați</li>
            <li>Dashboard administrativ cu indicatori și grafice</li>
            <li>Modul de recomandare a produselor compatibile</li>
          </ul>

          <hr>

          <p class="text-muted mb-0">
            Poți explora întreaga colecție în pagina
            <a href="/turyshop/products.php">Produse</a>.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- TEHNOLOGII -->
  <section class="mb-5">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h4 class="fw-bold mb-2">Frontend</h4>
          <p class="text-muted mb-0">
            Interfața este realizată cu HTML5, CSS3 și Bootstrap, pentru un aspect modern, responsive și ușor de utilizat.
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h4 class="fw-bold mb-2">Backend</h4>
          <p class="text-muted mb-0">
            Funcționalitatea aplicației este implementată în PHP, iar logica paginilor este conectată la baza de date în mod dinamic.
          </p>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
          <h4 class="fw-bold mb-2">Bază de date</h4>
          <p class="text-muted mb-0">
            Produsele, utilizatorii, comenzile și categoriile sunt gestionate în MySQL, prin intermediul mediului local XAMPP.
          </p>
        </div>
      </div>
    </div>
  </section>

  <!-- CTA -->
  <section>
    <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 text-center">
      <h2 class="fw-bold mb-3">Explorează colecția TuryShop</h2>
      <p class="text-muted mb-4">
        Descoperă produsele disponibile și navighează printr-o platformă construită pentru o experiență clară, elegantă și funcțională.
      </p>
      <div class="d-flex justify-content-center flex-wrap gap-2">
        <a href="/turyshop/products.php" class="btn btn-dark px-4">Vezi produsele</a>
        <a href="/turyshop/size-guide.php" class="btn btn-outline-dark px-4">Ghid mărimi</a>
      </div>
    </div>
  </section>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>