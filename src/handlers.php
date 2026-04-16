<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

/**
 * Input validation utility class
 */
class Validator
{
    /**
     * Validate and trim string
     * 
     * @param mixed $value Value to validate
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return string|null Trimmed string or null if invalid
     */
    public static function string(mixed $value, int $min = 1, int $max = 255): ?string
    {
        $trimmed = trim((string)$value);
        return (strlen($trimmed) >= $min && strlen($trimmed) <= $max) ? $trimmed : null;
    }

    /**
     * Validate enum value
     * 
     * @param mixed $value Value to validate
     * @param array $validValues Allowed values
     * @return string|null Value if valid, null otherwise
     */
    public static function enum(mixed $value, array $validValues): ?string
    {
        $str = (string)$value;
        return in_array($str, $validValues, true) ? $str : null;
    }

    /**
     * Validate integer
     * 
     * @param mixed $value Value to validate
     * @return int|null Integer if valid, null otherwise
     */
    public static function integer(mixed $value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        return ($int !== false) ? (int)$int : null;
    }

    /**
     * Validate text (longer string)
     * 
     * @param mixed $value Value to validate
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @return string|null Trimmed text or null if invalid
     */
    public static function text(mixed $value, int $min = 1, int $max = 5000): ?string
    {
        $trimmed = trim((string)$value);
        return (strlen($trimmed) >= $min && strlen($trimmed) <= $max) ? $trimmed : null;
    }
}

/**
 * Ticket business logic handler
 */
class TicketHandler
{
    /**
     * Insert a ticket audit event.
     *
     * @param int $ticketId Ticket ID
     * @param int|null $changedBy User ID who made the change
     * @param string $actionType Event type
     * @param string|null $oldValue Previous value
     * @param string|null $newValue New value
     * @return void
     */
    private static function logAuditEvent(
        int $ticketId,
        ?int $changedBy,
        string $actionType,
        ?string $oldValue = null,
        ?string $newValue = null
    ): void {
        Database::insert(
            'INSERT INTO ticket_audit_log (ticket_id, changed_by, action_type, old_value, new_value) VALUES (?, ?, ?, ?, ?)',
            [$ticketId, $changedBy, $actionType, $oldValue, $newValue]
        );
    }

    /**
     * Convert assignee ID into displayable label for audit records.
     *
     * @param int|null $userId Assigned user ID
     * @return string Assignee label
     */
    private static function assigneeLabel(?int $userId): string
    {
        if ($userId === null) {
            return 'Unassigned';
        }

        $user = Database::fetch('SELECT username FROM users WHERE id = ?', [$userId]);
        return $user ? (string)$user['username'] : 'User #' . $userId;
    }

    /**
     * Create new ticket
     * 
     * @param string $title Ticket title
     * @param string $description Ticket description
     * @param string $priority Priority level
     * @param int|null $asset_id Related asset ID
     * @return array Success array or error array
     */
    public static function create(string $title, string $description, string $priority, ?int $asset_id = null): array
    {
        $title = Validator::string($title, 5, 255);
        $description = Validator::text($description, 0, 5000);
        $priority = Validator::enum($priority, VALID_PRIORITIES);
        $asset_id = $asset_id ? Validator::integer($asset_id) : null;
        $user = Auth::user();
        
        if (!$user || !$title || !$priority) {
            return ['error' => 'Invalid input data.'];
        }

        $created_by = $user['id'];
        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            $id = Database::insert(
                'INSERT INTO tickets (title, description, priority, status, asset_id, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$title, $description, $priority, 'open', $asset_id, $created_by]
            );

            self::logAuditEvent(
                (int)$id,
                (int)$created_by,
                'ticket_created',
                null,
                'Ticket created'
            );

            $pdo->commit();
            return ['success' => true, 'id' => $id];
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['error' => 'Database error.'];
        }
    }

    /**
     * Update ticket status
     * 
     * @param int $ticket_id Ticket ID
     * @param string $status New status
     * @return array Success or error array
     */
    public static function updateStatus(int $ticket_id, string $status): array
    {
        $ticket_id = Validator::integer($ticket_id);
        $status = Validator::enum($status, VALID_STATUSES);
        $user = Auth::user();

        if (!$user || !$ticket_id || !$status) {
            return ['error' => 'Invalid input.'];
        }

        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            $ticket = Database::fetch(
                'SELECT id, status, assigned_to FROM tickets WHERE id = ? FOR UPDATE',
                [$ticket_id]
            );

            if (!$ticket) {
                $pdo->rollBack();
                return ['error' => 'Ticket not found.'];
            }

            $oldStatus = (string)$ticket['status'];
            $assignedTo = $ticket['assigned_to'] !== null ? (int)$ticket['assigned_to'] : null;
            $currentUserId = (int)$user['id'];
            $isAdmin = $user['role'] === 'admin';

            if (!$isAdmin) {
                if ($status === 'closed') {
                    $pdo->rollBack();
                    return ['error' => 'Only admins can close tickets.'];
                }

                if ($assignedTo !== null && $assignedTo !== $currentUserId) {
                    $pdo->rollBack();
                    return ['error' => 'Ticket is assigned to another technician. Ask an admin to reassign it.'];
                }

                if ($status === 'in_progress' && $assignedTo === null) {
                    $updated = Database::execute(
                        'UPDATE tickets SET status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ? AND assigned_to IS NULL',
                        [$status, $currentUserId, $ticket_id]
                    );

                    if ($updated === 0) {
                        $refreshedTicket = Database::fetch(
                            'SELECT assigned_to FROM tickets WHERE id = ? FOR UPDATE',
                            [$ticket_id]
                        );

                        if (!$refreshedTicket) {
                            $pdo->rollBack();
                            return ['error' => 'Ticket not found.'];
                        }

                        $refreshedAssignedTo = $refreshedTicket['assigned_to'] !== null
                            ? (int)$refreshedTicket['assigned_to']
                            : null;

                        if ($refreshedAssignedTo !== $currentUserId) {
                            $pdo->rollBack();
                            return ['error' => 'Ticket is assigned to another technician. Ask an admin to reassign it.'];
                        }

                        Database::execute(
                            'UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?',
                            [$status, $ticket_id]
                        );
                    }

                    self::logAuditEvent(
                        $ticket_id,
                        $currentUserId,
                        'assignment_changed',
                        'Unassigned',
                        self::assigneeLabel($currentUserId)
                    );

                    if ($oldStatus !== $status) {
                        self::logAuditEvent(
                            $ticket_id,
                            $currentUserId,
                            'status_changed',
                            $oldStatus,
                            $status
                        );
                    }

                    $pdo->commit();

                    return ['success' => true];
                }

                if ($assignedTo !== $currentUserId) {
                    $pdo->rollBack();
                    return ['error' => 'You can only update tickets assigned to you.'];
                }
            }

            if ($oldStatus !== $status) {
                Database::execute(
                    'UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?',
                    [$status, $ticket_id]
                );

                self::logAuditEvent(
                    $ticket_id,
                    $currentUserId,
                    'status_changed',
                    $oldStatus,
                    $status
                );
            }

            $pdo->commit();
            return ['success' => true];
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['error' => 'Database error.'];
        }
    }

    /**
     * Assign ticket to technician (admin only)
     * 
     * @param int $ticket_id Ticket ID
     * @param int|null $assigned_to User ID to assign to
     * @return array Success or error array
     */
    public static function assign(int $ticket_id, ?int $assigned_to = null): array
    {
        $user = Auth::user();
        
        if (!$user || $user['role'] !== 'admin') {
            return ['error' => 'Only admins can assign tickets.'];
        }

        $ticket_id = Validator::integer($ticket_id);
        $assigned_to = $assigned_to ? Validator::integer($assigned_to) : null;

        if (!$ticket_id) {
            return ['error' => 'Invalid ticket ID.'];
        }

        $pdo = Database::connect();

        try {
            $pdo->beginTransaction();

            $ticket = Database::fetch(
                'SELECT id, assigned_to FROM tickets WHERE id = ? FOR UPDATE',
                [$ticket_id]
            );

            if (!$ticket) {
                $pdo->rollBack();
                return ['error' => 'Ticket not found.'];
            }

            $currentAssignedTo = $ticket['assigned_to'] !== null ? (int)$ticket['assigned_to'] : null;

            if ($currentAssignedTo === $assigned_to) {
                $pdo->commit();
                return ['success' => true];
            }

            $oldAssigneeLabel = self::assigneeLabel($currentAssignedTo);
            $newAssigneeLabel = self::assigneeLabel($assigned_to);

            Database::execute(
                'UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?',
                [$assigned_to, $ticket_id]
            );

            self::logAuditEvent(
                $ticket_id,
                (int)$user['id'],
                'assignment_changed',
                $oldAssigneeLabel,
                $newAssigneeLabel
            );

            $pdo->commit();
            return ['success' => true];
        } catch (PDOException) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['error' => 'Database error.'];
        }
    }

    /**
     * Get audit trail entries for a ticket.
     *
     * @param int $ticket_id Ticket ID
     * @param int $limit Max rows to return
     * @return array Audit entries
     */
    public static function getAuditTrail(int $ticket_id, int $limit = 50): array
    {
        $ticket_id = Validator::integer($ticket_id);
        $limit = Validator::integer($limit) ?? 50;

        if (!$ticket_id) {
            return [];
        }

        $limit = max(1, min($limit, 200));

        $sql = sprintf(
            'SELECT
                a.id,
                a.action_type,
                a.old_value,
                a.new_value,
                a.created_at,
                u.username AS changed_by_name
             FROM ticket_audit_log a
             LEFT JOIN users u ON a.changed_by = u.id
             WHERE a.ticket_id = ?
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT %d',
            $limit
        );

        return Database::fetchAll($sql, [$ticket_id]);
    }
}

/**
 * Asset business logic handler
 */
class AssetHandler
{
    /**
     * Create new asset (admin only)
     * 
     * @param string $name Asset name
     * @param string $asset_type Asset type/category
     * @param string|null $serial_number Serial number
     * @param string|null $location Physical location
     * @return array Success array or error array
     */
    public static function create(string $name, string $asset_type, ?string $serial_number = null, ?string $location = null): array
    {
        $user = Auth::user();
        
        if (!$user || $user['role'] !== 'admin') {
            return ['error' => 'Only admins can create assets.'];
        }

        $name = Validator::string($name, 3, 100);
        $asset_type = Validator::string($asset_type, 3, 50);
        $serial_number = $serial_number ? Validator::string($serial_number, 1, 100) : null;
        $location = $location ? Validator::string($location, 1, 100) : null;

        if (!$name || !$asset_type) {
            return ['error' => 'Invalid input data.'];
        }

        try {
            $id = Database::insert(
                'INSERT INTO assets (name, asset_type, serial_number, location, status) 
                 VALUES (?, ?, ?, ?, ?)',
                [$name, $asset_type, $serial_number, $location, 'active']
            );
            return ['success' => true, 'id' => $id];
        } catch (PDOException) {
            return ['error' => 'Database error.'];
        }
    }
}
