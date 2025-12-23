# Service Desk Portal

A full-stack PHP + MySQL web application for IT ticket and asset management. Built as a work sample demonstrating modern PHP practices, JSON-RPC 2.0 API, and vanilla JavaScript AJAX.

**Demo-ready in under 2 minutes.**

---

## Features

### Authentication

- Session-based login/logout
- Password hashing with bcrypt (`password_hash` / `password_verify`)
- Role-based access control (Admin vs Tech)
- CSRF token protection on all forms

**Role Permissions:**

| Action               | Admin | Tech |
| -------------------- | ----- | ---- |
| Create tickets       | ✓     | ✓    |
| View all tickets     | ✓     | ✓    |
| Update ticket status | ✓     | ✓    |
| Assign tickets       | ✓     | ✗    |
| Create assets        | ✓     | ✗    |
| View assets          | ✓     | ✓    |

### Tickets

- Create, list, view tickets
- Update status: open → in_progress → resolved → closed
- Assign to technician
- Link to assets (optional)
- Filter by status and assigned tech
- Real-time AJAX status updates via JSON-RPC API

### Assets

- Create, list, view IT assets
- Asset types: computer, printer, server, etc.
- Serial number tracking
- Location tracking
- Link tickets to assets

### JSON-RPC 2.0 API

Single endpoint: `/api.php`

Implemented methods:

- **ticket.list** — List tickets with optional filters
- **ticket.updateStatus** — Change ticket status
- **ticket.assign** — Assign ticket to technician

All methods use JSON-RPC 2.0 request/response format. No external dependencies.

### Security

- PDO prepared statements (all queries)
- Input validation (enums, type casting, length checks)
- Output escaping (htmlspecialchars)
- CSRF token validation
- Password hashing with bcrypt
- Session-based authentication
- XSS prevention in JavaScript (HTML escaping)

---

## Quick Start

### Prerequisites

- PHP 8.x with built-in server
- MySQL 8.x
- XAMPP or equivalent

### Setup (5 minutes)

**1. Clone repository:**

```bash
git clone https://github.com/yourusername/service-desk.git
cd service-desk
```

**2. Copy environment file:**

```bash
cp .env.example .env
```

**3. Edit `.env` with your database credentials:**

```
DB_HOST=localhost
DB_PORT=3306
DB_NAME=service_desk
DB_USER=root
DB_PASS=yourpassword
```

**4. Create database & schema:**

```bash
mysql -u root -p
CREATE DATABASE IF NOT EXISTS service_desk;
EXIT;
mysql -u root service_desk < schema.sql
```

**5. Reset demo user passwords:**

```bash
php setup.php
```

**6. Start PHP development server:**

```bash
php -S localhost:8000 -t public
```

**7. Open browser:**

```
http://localhost:8000
```

---

## Demo Credentials

| Username | Password  | Role  |
| -------- | --------- | ----- |
| admin    | Admin123! | Admin |
| tech1    | Tech123!  | Tech  |

---

## Project Structure

```
service-desk/
├── public/                 (web root)
│   ├── index.php          (main router)
│   ├── api.php            (JSON-RPC endpoint)
│   ├── css/style.css      (styling + animations)
│   ├── js/app.js          (API client + AJAX)
│   └── views/             (HTML templates)
│       ├── login.php
│       ├── dashboard.php
│       ├── navbar.php
│       ├── tickets.php
│       ├── ticket-detail.php
│       └── assets.php
├── src/                    (backend logic)
│   ├── config.php         (loads env config)
│   ├── env.php            (environment loader)
│   ├── db.php             (PDO connection)
│   ├── auth.php           (authentication)
│   └── handlers.php       (business logic)
├── .env.example           (template - DO commit)
├── .env                   (secrets - DO NOT commit)
├── .gitignore             (excludes .env)
├── schema.sql             (database setup)
├── setup.php              (password reset utility)
└── README.md              (this file)
```

---

## API Examples

### Request Format

All requests to `/api.php` use JSON-RPC 2.0:

```json
{
  "jsonrpc": "2.0",
  "method": "ticket.list",
  "params": {
    "status": "open"
  },
  "id": 1
}
```

### Example 1: List All Open Tickets

**cURL:**

```bash
curl -X POST http://localhost:8000/api.php \
  -H "Content-Type: application/json" \
  -H "Cookie: ServiceDeskSession=YOUR_SESSION" \
  -d '{
    "jsonrpc": "2.0",
    "method": "ticket.list",
    "params": {
      "status": "open"
    },
    "id": 1
  }'
```

**Response:**

```json
{
  "jsonrpc": "2.0",
  "result": [
    {
      "id": 1,
      "title": "Printer jam in reception",
      "priority": "medium",
      "status": "open",
      "assigned_to_name": "tech1",
      "asset_name": "Office Printer"
    },
    {
      "id": 3,
      "title": "Laptop not starting",
      "priority": "high",
      "status": "open",
      "assigned_to_name": null,
      "asset_name": "Dell Laptop"
    }
  ],
  "id": 1
}
```

### Example 2: Update Ticket Status

**cURL:**

```bash
curl -X POST http://localhost:8000/api.php \
  -H "Content-Type: application/json" \
  -H "Cookie: ServiceDeskSession=YOUR_SESSION" \
  -d '{
    "jsonrpc": "2.0",
    "method": "ticket.updateStatus",
    "params": {
      "ticket_id": 1,
      "status": "in_progress"
    },
    "id": 2
  }'
```

**Response:**

```json
{
  "jsonrpc": "2.0",
  "result": {
    "success": true
  },
  "id": 2
}
```

### Example 3: JavaScript/Fetch

```javascript
const api = new APIClient();

// Load tickets
const tickets = await api.call("ticket.list", { status: "open" });
console.log(tickets);

// Update status
await api.call("ticket.updateStatus", {
  ticket_id: 1,
  status: "resolved",
});

// Assign ticket
await api.call("ticket.assign", {
  ticket_id: 1,
  assigned_to: 2,
});
```

---

## Usage Workflow

### 1. Login

- Navigate to `http://localhost:8000`
- Enter credentials (admin/Admin123! or tech1/Tech123!)
- Session token stored in browser cookie

### 2. Create Ticket

- Click "Tickets" → "+ New Ticket"
- Fill title, description, priority, optional asset
- Submit → Creates ticket, redirect to list

### 3. View Tickets

- List loads server-side on page load
- Click "↻ Reload (API)" → Loads via JSON-RPC (no page reload)
- Table updates dynamically with AJAX

### 4. Update Ticket

- Click ticket → Details page
- Change "Status" dropdown → AJAX call to ticket.updateStatus
- Change "Assigned To" dropdown → AJAX call to ticket.assign
- Toast notification shows result

### 5. Create Asset

- Click "Assets" → "+ New Asset"
- Fill name, type, serial, location
- Submit → Creates asset

### 6. Logout

- Click username dropdown → "Logout"
- Session destroyed, redirected to login

---

## Database Schema

### users

```
id INT PRIMARY KEY
username VARCHAR(50) UNIQUE
email VARCHAR(100)
password_hash VARCHAR(255)
role ENUM('admin', 'tech')
created_at TIMESTAMP
updated_at TIMESTAMP
```

### tickets

```
id INT PRIMARY KEY
title VARCHAR(255)
description TEXT
priority ENUM('low', 'medium', 'high', 'critical')
status ENUM('open', 'in_progress', 'resolved', 'closed')
asset_id INT (FK to assets)
created_by INT (FK to users)
assigned_to INT (FK to users)
created_at TIMESTAMP
updated_at TIMESTAMP
```

### assets

```
id INT PRIMARY KEY
name VARCHAR(100)
asset_type VARCHAR(50)
serial_number VARCHAR(100)
location VARCHAR(100)
status ENUM('active', 'inactive', 'retired')
created_at TIMESTAMP
updated_at TIMESTAMP
```

---

## Testing Checklist

- [ ] Login page loads, accepts credentials
- [ ] Login with admin/tech1 credentials
- [ ] Dashboard shows after login
- [ ] Ticket list loads (server-side)
- [ ] "↻ Reload (API)" button loads tickets via JSON-RPC
- [ ] Create ticket form works (visible in list)
- [ ] Update ticket status via dropdown (AJAX, no reload)
- [ ] Assign ticket via dropdown (AJAX, no reload)
- [ ] Toast notifications appear bottom-right
- [ ] Click reload button multiple times → warning on 2nd click (debounce)
- [ ] Assets page loads
- [ ] Create asset form works
- [ ] Logout works, returns to login
- [ ] Browser DevTools Network tab shows JSON-RPC requests to `/api.php`

---

## Security Notes

- All database queries use PDO prepared statements
- All user input validated before database operations
- All HTML output escaped with `htmlspecialchars()`
- CSRF tokens on all POST forms, verified server-side
- Passwords hashed with `password_hash()` (bcrypt)
- Sessions used for authentication, not URL tokens
- JavaScript includes `escapeHtml()` function for dynamic content
- No framework = no hidden vulnerabilities, auditable code

---

## Performance

- Single PDO connection (lazy-loaded, reused)
- Debounced API calls prevent server spam
- Prepared statements prevent N+1 queries
- Bootstrap CDN (no local CSS frameworks)
- Vanilla JavaScript (no jQuery, no heavy libraries)
- JSON responses suitable for mobile and SPA patterns

---

## What's Included

✓ Full authentication system  
✓ CRUD operations for tickets and assets  
✓ JSON-RPC 2.0 API with 3 methods  
✓ AJAX interactions without page reload  
✓ Security best practices (PDO, CSRF, XSS protection)  
✓ Professional UI with Bootstrap + custom CSS  
✓ Toast notifications with debouncing  
✓ Comprehensive error handling  
✓ Database schema with proper relations  
✓ Demo seed data

---

## License

Open source. Use as reference or in interviews.

---

## Author Notes

Built in one evening as a demonstration of:

- Modern PHP (8.x, PDO, no framework)
- Clean code organization
- Security-first practices
- JSON-RPC protocol implementation
- Vanilla JavaScript + fetch API
- Bootstrap theming
- Git workflow and commit messages
- Full-stack problem solving

Total lines of code: ~1500 (PHP, JS, SQL, CSS combined)  
Time to code: ~2 hours  
Time to demo: < 2 minutes
