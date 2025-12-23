<?php
Auth::requireLogin();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Service Desk Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <h1>Dashboard</h1>
                <p class="text-muted">Welcome, <strong><?= Auth::validateInput($user['username']) ?></strong> (<?= Auth::validateInput($user['role']) ?>)</p>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Tickets</h5>
                        <p class="card-text">Create, view, and manage support tickets.</p>
                        <a href="/index.php?action=tickets" class="btn btn-primary">Go to Tickets</a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Assets</h5>
                        <p class="card-text">View and manage IT assets.</p>
                        <a href="/index.php?action=assets" class="btn btn-primary">Go to Assets</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
