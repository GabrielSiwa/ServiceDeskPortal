<?php
Auth::requireLogin();
$user = Auth::user();

$assets = Database::fetchAll('
    SELECT id, name, asset_type, serial_number, location, status
    FROM assets
    ORDER BY name ASC
');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assets - Service Desk Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h1>Assets</h1>
            </div>
            <div class="col-md-6 text-end">
                <?php if ($user['role'] === 'admin'): ?>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createAssetModal">
                        + New Asset
                    </button>
                <?php else: ?>
                    <small class="text-muted">Only admins can create assets</small>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= Auth::validateInput($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= Auth::validateInput($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Serial #</th>
                        <th>Location</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assets as $asset): ?>
                        <tr>
                            <td><?= Auth::validateInput($asset['name']) ?></td>
                            <td><?= Auth::validateInput($asset['asset_type']) ?></td>
                            <td><?= Auth::validateInput($asset['serial_number'] ?? '—') ?></td>
                            <td><?= Auth::validateInput($asset['location'] ?? '—') ?></td>
                            <td>
                                <span class="badge bg-<?= $asset['status'] === 'active' ? 'success' : 'secondary' ?>">
                                    <?= Auth::validateInput($asset['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Asset Modal (scaffold) -->
    <div class="modal fade" id="createAssetModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Asset</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                        <input type="hidden" name="_action" value="create">

                        <div class="mb-3">
                            <label for="name" class="form-label">Name *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>

                        <div class="mb-3">
                            <label for="asset_type" class="form-label">Type *</label>
                            <input type="text" class="form-control" id="asset_type" name="asset_type" placeholder="e.g., computer, printer, server" required>
                        </div>

                        <div class="mb-3">
                            <label for="serial_number" class="form-label">Serial Number</label>
                            <input type="text" class="form-control" id="serial_number" name="serial_number">
                        </div>

                        <div class="mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
