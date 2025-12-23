<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/handlers.php';

$action = $_GET['action'] ?? 'dashboard';
$message = null;
$error = null;

// Handle login page and authentication
if ($action === 'login') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $error = 'Invalid CSRF token.';
        } else {
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';

            if (Auth::login($username, $password)) {
                header('Location: /index.php?action=dashboard');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }

    $csrf_token = Auth::generateCsrfToken();
    include __DIR__ . '/views/login.php';
    exit;
}

// Handle logout
if ($action === 'logout') {
    Auth::logout();
    header('Location: /index.php?action=login');
    exit;
}

// Require login for all other routes
Auth::requireLogin();

// Route dispatcher
if ($action === 'dashboard') {
    include __DIR__ . '/views/dashboard.php';
} elseif ($action === 'tickets') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
        if ($_POST['_action'] === 'create' && Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $result = TicketHandler::create(
                $_POST['title'] ?? '',
                $_POST['description'] ?? '',
                $_POST['priority'] ?? 'medium',
                !empty($_POST['asset_id']) ? (int)$_POST['asset_id'] : null
            );
            $message = isset($result['success']) ? 'Ticket created successfully.' : ($result['error'] ?? 'Unknown error.');
            $error = isset($result['success']) ? null : $message;
        }
    }
    include __DIR__ . '/views/tickets.php';
} elseif ($action === 'ticket-detail') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
        if ($_POST['_action'] === 'update_status' && Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $result = TicketHandler::updateStatus(
                (int)($_GET['id'] ?? 0),
                $_POST['status'] ?? ''
            );
            if (isset($result['success'])) {
                $message = 'Status updated.';
            } else {
                $error = $result['error'] ?? 'Unknown error.';
            }
        } elseif ($_POST['_action'] === 'assign' && Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $result = TicketHandler::assign(
                (int)($_GET['id'] ?? 0),
                !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null
            );
            if (isset($result['success'])) {
                $message = 'Assignment updated.';
            } else {
                $error = $result['error'] ?? 'Unknown error.';
            }
        }
    }
    include __DIR__ . '/views/ticket-detail.php';
} elseif ($action === 'assets') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {
        if ($_POST['_action'] === 'create' && Auth::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $result = AssetHandler::create(
                $_POST['name'] ?? '',
                $_POST['asset_type'] ?? '',
                $_POST['serial_number'] ?? null,
                $_POST['location'] ?? null
            );
            if (isset($result['success'])) {
                $message = 'Asset created successfully.';
            } else {
                $error = $result['error'] ?? 'Unknown error.';
            }
        }
    }
    include __DIR__ . '/views/assets.php';
} else {
    http_response_code(404);
    die('Page not found.');
}
