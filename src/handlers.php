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

        try {
            $id = Database::insert(
                'INSERT INTO tickets (title, description, priority, status, asset_id, created_by) 
                 VALUES (?, ?, ?, ?, ?, ?)',
                [$title, $description, $priority, 'open', $asset_id, $created_by]
            );
            return ['success' => true, 'id' => $id];
        } catch (PDOException) {
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

        if (!$ticket_id || !$status) {
            return ['error' => 'Invalid input.'];
        }

        try {
            Database::execute(
                'UPDATE tickets SET status = ?, updated_at = NOW() WHERE id = ?',
                [$status, $ticket_id]
            );
            return ['success' => true];
        } catch (PDOException) {
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

        try {
            Database::execute(
                'UPDATE tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?',
                [$assigned_to, $ticket_id]
            );
            return ['success' => true];
        } catch (PDOException) {
            return ['error' => 'Database error.'];
        }
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
