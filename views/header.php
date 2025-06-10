<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="assets/b.png">
    <link rel="stylesheet" href="../sidebarhover.css">
    <script src="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.js"></script>
</head>

<body>
    <header class="navbar navbar-dark sticky-top bg-primary flex-md-nowrap p-0 shadow">
        <div class="container-fluid">
            <?php if (isset($_SESSION["is_admin"]) && $_SESSION["is_admin"] === true): ?>
                <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 d-flex align-items-center" href="admin_panel.php">
                    <i data-feather="arrow-left" class="feather-icon me-2"></i> Go back
                </a>
            <?php else: ?>
                <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php">
                    <i data-feather="package" class="feather-icon me-2"></i> Oyanib Shoes
                </a>
            <?php endif; ?>
            <div class="d-flex ms-auto">
                <div class="navbar-nav">
                    <div class="nav-item text-nowrap">
                        <a class="nav-link px-3 d-flex align-items-center logout-btn" href="#" data-bs-toggle="modal"
                            data-bs-target="#logoutModal">
                            <span class="d-none d-sm-inline">Sign out</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Improved Logout Modal with Clean UI Style -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-3 shadow"
                style="max-width: 320px; margin: 0 auto; background-color: white;">
                <div class="modal-body p-4">
                    <h4 class="mb-3 text-center text-primary fw-medium">Sign out?</h4>
                    <div class="border-bottom mb-3"></div>
                    <p class="text-center text-muted mb-4">Are you sure you want to sign out? You will need to log in
                        again to access the system.</p>

                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-light flex-grow-1 me-2 rounded-pill border"
                            data-bs-dismiss="modal">
                            No
                        </button>
                        <a href="logout.php" class="btn btn-primary flex-grow-1 ms-2 rounded-pill">
                            Yes
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Initialize Feather icons -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            feather.replace();
        });
    </script>
</body>

</html>