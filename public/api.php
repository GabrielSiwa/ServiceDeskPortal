<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/handlers.php';

// JSON-RPC 2.0 error response helper
function jsonRpcError(int $code, string $message, mixed $id = null): string
{
    return json_encode([
        'jsonrpc' => '2.0',
        'error' => [
            'code' => $code,
            'message' => $message
        ],
        'id' => $id
    ]);
}

// JSON-RPC 2.0 success response helper
function jsonRpcResponse(mixed $result, mixed $id): string
{
    return json_encode([
        'jsonrpc' => '2.0',
        'result' => $result,
        'id' => $id
    ]);
}

// Validate HTTP method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(jsonRpcError(-32600, 'Invalid Request'));
}

// Parse JSON request
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!is_array($data) || !isset($data['jsonrpc']) || $data['jsonrpc'] !== '2.0') {
    die(jsonRpcError(-32600, 'Invalid Request'));
}

$method = $data['method'] ?? null;
$params = is_array($data['params'] ?? null) ? $data['params'] : [];
$id = $data['id'] ?? null;

if (!$method || !is_string($method)) {
    die(jsonRpcError(-32600, 'Invalid Request', $id));
}

// Require authentication
Auth::requireLogin();

// Route to method handlers
switch ($method) {
    case 'ticket.list':
        $status = isset($params['status']) ? Validator::enum($params['status'], VALID_STATUSES) : null;
        $assigned_to = isset($params['assigned_to']) ? Validator::integer($params['assigned_to']) : null;

        $sql = 'SELECT t.id, t.title, t.priority, t.status, u.username as assigned_to_name, a.name as asset_name 
                FROM tickets t 
                LEFT JOIN users u ON t.assigned_to = u.id 
                LEFT JOIN assets a ON t.asset_id = a.id 
                WHERE 1=1';
        $sqlParams = [];

        if ($status) {
            $sql .= ' AND t.status = ?';
            $sqlParams[] = $status;
        }

        if ($assigned_to) {
            $sql .= ' AND t.assigned_to = ?';
            $sqlParams[] = $assigned_to;
        }

        $sql .= ' ORDER BY t.created_at DESC';

        $tickets = Database::fetchAll($sql, $sqlParams);
        echo jsonRpcResponse($tickets, $id);
        break;

    case 'ticket.updateStatus':
        if (!isset($params['ticket_id']) || !isset($params['status'])) {
            die(jsonRpcError(-32602, 'Missing required params: ticket_id, status', $id));
        }

        $result = TicketHandler::updateStatus((int)$params['ticket_id'], (string)$params['status']);

        if (isset($result['error'])) {
            die(jsonRpcError(-32603, $result['error'], $id));
        }

        echo jsonRpcResponse(['success' => true], $id);
        break;

    case 'ticket.assign':
        if (!isset($params['ticket_id'])) {
            die(jsonRpcError(-32602, 'Missing required param: ticket_id', $id));
        }

        $assigned_to = isset($params['assigned_to']) && $params['assigned_to'] !== '' 
            ? (int)$params['assigned_to'] 
            : null;
        
        $result = TicketHandler::assign((int)$params['ticket_id'], $assigned_to);

        if (isset($result['error'])) {
            die(jsonRpcError(-32603, $result['error'], $id));
        }

        echo jsonRpcResponse(['success' => true], $id);
        break;

    default:
        die(jsonRpcError(-32601, "Method '{$method}' not found", $id));
}

?>
