<?php
include __DIR__ . "/includes/header.php";
?>

<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="product-detail-box text-center p-5">
                <div class="mb-3">
                    <span class="badge text-bg-dark px-3 py-2">Comandă finalizată</span>
                </div>

                <h1 class="fw-bold mb-3">Comanda a fost plasată cu succes!</h1>

                <p class="text-muted fs-5 mb-4">
                    Îți mulțumim pentru achiziție. Comanda ta a fost înregistrată, iar produsele selectate
                    au fost procesate cu succes în cadrul aplicației.
                </p>

                <div class="row g-3 justify-content-center mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-2">Confirmare</h5>
                            <p class="text-muted mb-0">
                                Comanda a fost salvată în baza de date și înregistrată corect.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-2">Coș actualizat</h5>
                            <p class="text-muted mb-0">
                                Produsele comandate au fost eliminate automat din coșul de cumpărături.
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <h5 class="fw-bold mb-2">Continuare</h5>
                            <p class="text-muted mb-0">
                                Poți reveni în magazin pentru a explora și alte produse disponibile.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-center flex-wrap gap-2">
                    <a href="/turyshop/products.php" class="btn btn-dark px-4">
                        Continuă cumpărăturile
                    </a>
                    <a href="/turyshop/index.php" class="btn btn-outline-dark px-4">
                        Înapoi la pagina principală
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>