-- Service Desk Portal Schema
-- MySQL 

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'tech') NOT NULL DEFAULT 'tech',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    asset_type VARCHAR(50) NOT NULL,
    serial_number VARCHAR(100) UNIQUE,
    location VARCHAR(100),
    status ENUM('active', 'inactive', 'retired') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_asset_type (asset_type),
    INDEX idx_status (status)
);

CREATE TABLE IF NOT EXISTS tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    asset_id INT NULL,
    created_by INT NULL,
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_assigned_to (assigned_to),
    INDEX idx_created_by (created_by),
    INDEX idx_asset_id (asset_id)
);

CREATE TABLE IF NOT EXISTS ticket_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    changed_by INT NULL,
    action_type ENUM('ticket_created', 'status_changed', 'assignment_changed') NOT NULL,
    old_value VARCHAR(255) NULL,
    new_value VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_audit_ticket_created_at (ticket_id, created_at),
    INDEX idx_audit_changed_by (changed_by)
);


-- Seed demo assets

INSERT INTO assets (name, asset_type, serial_number, location, status) VALUES
('Office Printer', 'printer', 'PRN-001-2024', 'Floor 1 - Reception', 'active'),
('Server Rack', 'server', 'SRV-042-2024', 'Server Room', 'active'),
('Dell Laptop', 'computer', 'DLL-567-2024', 'Desk 5', 'active')
ON DUPLICATE KEY UPDATE id = id;

-- Seed demo tickets

INSERT INTO tickets (title, description, priority, status, asset_id, created_by, assigned_to)
SELECT 'Printer jam in reception', 'Paper jam in office printer, needs clearing', 'medium', 'open', 1, 1, 2
WHERE NOT EXISTS (
    SELECT 1 FROM tickets WHERE title = 'Printer jam in reception'
);

INSERT INTO tickets (title, description, priority, status, asset_id, created_by, assigned_to)
SELECT 'Server backup verification', 'Check last backup logs and verify integrity', 'high', 'in_progress', 2, 1, 2
WHERE NOT EXISTS (
    SELECT 1 FROM tickets WHERE title = 'Server backup verification'
);

INSERT INTO tickets (title, description, priority, status, asset_id, created_by, assigned_to)
SELECT 'Laptop not starting', 'Dell laptop at desk 5 will not power on', 'high', 'open', 3, 1, NULL
WHERE NOT EXISTS (
    SELECT 1 FROM tickets WHERE title = 'Laptop not starting'
);
