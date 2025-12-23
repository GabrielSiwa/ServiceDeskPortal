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


-- Seed demo users

INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@servicedesk.local', '...', 'admin'),
('tech1', 'tech1@servicedesk.local', '...', 'tech')
ON DUPLICATE KEY UPDATE email=VALUES(email), password_hash=VALUES(password_hash), role=VALUES(role);

-- Seed demo assets

INSERT INTO assets (name, asset_type, serial_number, location, status) VALUES
('Office Printer', 'printer', 'PRN-001-2024', 'Floor 1 - Reception', 'active'),
('Server Rack', 'server', 'SRV-042-2024', 'Server Room', 'active'),
('Dell Laptop', 'computer', 'DLL-567-2024', 'Desk 5', 'active');

-- Seed demo tickets

INSERT INTO tickets (title, description, priority, status, asset_id, created_by, assigned_to) VALUES
('Printer jam in reception', 'Paper jam in office printer, needs clearing', 'medium', 'open', 1, 1, 2),
('Server backup verification', 'Check last backup logs and verify integrity', 'high', 'in_progress', 2, 1, 2),
('Laptop not starting', 'Dell laptop at desk 5 will not power on', 'high', 'open', 3, 1, NULL);
