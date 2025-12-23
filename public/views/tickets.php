<?php
Auth::requireLogin();
$user = Auth::user();

$tickets = Database::fetchAll('
    SELECT 
        t.id, t.title, t.priority, t.status, 
        u.username as assigned_to_name,
        a.name as asset_name
    FROM tickets t
    LEFT JOIN users u ON t.assigned_to = u.id
    LEFT JOIN assets a ON t.asset_id = a.id
    ORDER BY t.created_at DESC
');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tickets - Service Desk Portal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/navbar.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-md-6">
                <h1>Tickets</h1>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-info btn-sm" onclick="debouncedLoadTickets()" title="Load via JSON-RPC API">
                    ↻ Reload (API)
                </button>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTicketModal">
                    + New Ticket
                </button>
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
            <table class="table table-striped" id="ticketsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Asset</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tickets as $ticket): ?>
                        <tr>
                            <td><?= $ticket['id'] ?></td>
                            <td><?= Auth::validateInput($ticket['title']) ?></td>
                            <td>
                                <span class="badge bg-<?= 
                                    $ticket['priority'] === 'critical' ? 'danger' : 
                                    ($ticket['priority'] === 'high' ? 'warning' : 'info')
                                ?>">
                                    <?= Auth::validateInput($ticket['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?= 
                                    $ticket['status'] === 'open' ? 'danger' : 
                                    ($ticket['status'] === 'in_progress' ? 'warning' : 'success')
                                ?>">
                                    <?= Auth::validateInput($ticket['status']) ?>
                                </span>
                            </td>
                            <td><?= $ticket['assigned_to_name'] ? Auth::validateInput($ticket['assigned_to_name']) : '—' ?></td>
                            <td><?= $ticket['asset_name'] ? Auth::validateInput($ticket['asset_name']) : '—' ?></td>
                            <td>
                                <a href="/index.php?action=ticket-detail&id=<?= $ticket['id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Ticket Modal (scaffold) -->
    <div class="modal fade" id="createTicketModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Ticket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCsrfToken() ?>">
                        <input type="hidden" name="_action" value="create">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="priority" class="form-label">Priority</label>
                            <select class="form-select" id="priority" name="priority">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="asset_id" class="form-label">Asset (optional)</label>
                            <select class="form-select" id="asset_id" name="asset_id">
                                <option value="">None</option>
                                <?php
                                $assets = Database::fetchAll('SELECT id, name FROM assets WHERE status = "active"');
                                foreach ($assets as $asset):
                                ?>
                                    <option value="<?= $asset['id'] ?>"><?= Auth::validateInput($asset['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/js/app.js"></script>
</body>
</html>
