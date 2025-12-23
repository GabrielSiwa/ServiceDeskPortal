<?php
Auth::requireLogin();
$user = Auth::user();

$ticket_id = (int)($_GET['id'] ?? 0);
$ticket = Database::fetch('
    SELECT 
        t.id, t.title, t.description, t.priority, t.status, 
        t.created_at, t.updated_at,
        u_created.username as created_by_name,
        u_assigned.username as assigned_to_name,
        a.name as asset_name
    FROM tickets t
    LEFT JOIN users u_created ON t.created_by = u_created.id
    LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.id
    LEFT JOIN assets a ON t.asset_id = a.id
    WHERE t.id = ?
', [$ticket_id]);

if (!$ticket) {
    http_response_code(404);
    die('Ticket not found.');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket <?= $ticket['id'] ?> - Service Desk Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h1>Ticket #<?= $ticket['id'] ?></h1>
            </div>
            <div class="col-md-6 text-end">
                <a href="/index.php?action=tickets" class="btn btn-secondary">Back to Tickets</a>
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

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= Auth::validateInput($ticket['title']) ?></h5>
                        <p class="card-text text-muted"><?= Auth::validateInput($ticket['description']) ?></p>
                        <hr>
                        <strong>Created:</strong> <?= $ticket['created_at'] ?><br>
                        <strong>Updated:</strong> <?= $ticket['updated_at'] ?><br>
                        <strong>Created By:</strong> <?= Auth::validateInput($ticket['created_by_name']) ?><br>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">Details</h6>
                        <p>
                            <strong>Priority:</strong><br>
                            <span class="badge bg-warning"><?= Auth::validateInput($ticket['priority']) ?></span>
                        </p>
                        <p>
                            <strong>Status:</strong><br>
                            <select class="form-select form-select-sm" id="statusSelect" onchange="updateTicketStatusViaAPI(<?= $ticket['id'] ?>, this.value)">
                                <option value="open" <?= $ticket['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                <option value="in_progress" <?= $ticket['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $ticket['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="closed" <?= $ticket['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                            </select>
                            <small class="text-muted">Changes via JSON-RPC API</small>
                        </p>
                        <p>
                            <strong>Assigned To:</strong><br>
                            <?php if ($user['role'] === 'admin'): ?>
                                <select class="form-select form-select-sm" id="assignSelect" onchange="assignTicketViaAPI(<?= $ticket['id'] ?>, this.value || null)">
                                    <option value="">Unassigned</option>
                                    <?php
                                    $techs = Database::fetchAll('SELECT id, username FROM users WHERE role = "tech"');
                                    foreach ($techs as $tech):
                                    ?>
                                        <option value="<?= $tech['id'] ?>" <?= $ticket['assigned_to_name'] === $tech['username'] ? 'selected' : '' ?>>
                                            <?= Auth::validateInput($tech['username']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Changes via JSON-RPC API</small>
                            <?php else: ?>
                                <span><?= $ticket['assigned_to_name'] ? Auth::validateInput($ticket['assigned_to_name']) : '<em>Unassigned</em>' ?></span>
                                <small class="text-muted d-block">(Only admins can assign)</small>
                            <?php endif; ?>
                        </p>
                        <p>
                            <strong>Asset:</strong><br>
                            <?= $ticket['asset_name'] ? Auth::validateInput($ticket['asset_name']) : '—' ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
