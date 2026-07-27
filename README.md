# User Management System v2.0

> A production-ready, enterprise-grade user management dashboard built with **PHP 8.0+**, **MySQL**, and **vanilla JavaScript**. Features secure authentication, role-based access control, CSRF protection, rate limiting, and a modern glassmorphism UI.

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Security](https://img.shields.io/badge/Security-Enterprise%20Grade-green)]()
[![Architecture](https://img.shields.io/badge/Architecture-MVC%20%2B%20Singleton-orange)]()
[![PRs Welcome](https://img.shields.io/badge/PRs-Welcome-brightgreen.svg)](https://github.com/yourusername/user-management-system/pulls)

---

## 🎯 Overview

The **User Management System** is a full-stack web application that provides a secure, scalable, and maintainable solution for managing user accounts, roles, and permissions. It was built with a focus on **security best practices**, **clean architecture**, and **developer experience**.

### Key Highlights

| Category | Details |
|---|---|
| **Architecture** | MVC-inspired, Singleton DB pattern, dependency injection via `require_once` |
| **Security** | CSRF tokens, rate limiting, bcrypt password hashing, session security, CSP/HSTS headers |
| **RBAC** | Three-tier roles: `user`, `admin`, `super_admin` with privilege escalation protection |
| **Database** | Prepared statements, transactions, soft deletes, indexes, UTF8MB4 |
| **UI/UX** | Glassmorphism design, dark theme, RTL (Persian), responsive, Chart.js analytics |
| **Notifications** | Custom toast notification system with confirmation dialogs |

---

## ✨ Features

### Authentication & Authorization
- ✅ Secure login with username or email
- ✅ Registration with password strength validation
- ✅ Role-based access control (user / admin / super_admin)
- ✅ Session regeneration (prevents session fixation)
- ✅ Session timeout & IP/User-Agent validation
- ✅ Privilege escalation protection (only super_admin can assign admin roles)
- ✅ Soft delete with `deleted_at` column

### Security
- ✅ **CSRF Protection** — token-based, per-session, with expiry
- ✅ **Rate Limiting** — configurable limits on login, register, profile, and API endpoints (DB + file fallback)
- ✅ **Password Hashing** — `password_hash()` with bcrypt
- ✅ **HTTP Security Headers** — CSP, HSTS, X-Frame-Options, X-Content-Type-Options, Referrer-Policy, Permissions-Policy
- ✅ **Input Validation** — centralized `Validator` class with 12+ rules
- ✅ **SQL Injection Prevention** — all queries use prepared statements
- ✅ **XSS Prevention** — output escaping via `e()` helper
- ✅ **File Upload Security** — MIME type validation, size limits, extension whitelist

### User Management
- ✅ CRUD operations (Create, Read, Update, Delete)
- ✅ Search by username or email
- ✅ Filter by role and status
- ✅ Pagination (15 per page)
- ✅ Status toggle (active/inactive)
- ✅ Self-deletion prevention

### Profile System
- ✅ Avatar upload with image validation
- ✅ Password change with current password verification
- ✅ Email uniqueness check
- ✅ Session refresh after update

### Dashboard & Analytics
- ✅ Real-time statistics (total users, active/inactive, admins, weekly/monthly growth)
- ✅ Role distribution chart (Chart.js doughnut)
- ✅ User growth chart (Chart.js line)
- ✅ Recent activity log
- ✅ Admin-only chart access

### Admin Management (Super Admin Only)
- ✅ Create new admin accounts
- ✅ List all admins with status
- ✅ Soft delete admins (cannot delete super_admins)
- ✅ Self-deletion prevention

### Developer Experience
- ✅ Centralized error handler with structured JSON responses
- ✅ Structured `Response` class for API consistency
- ✅ Activity logging (login, register, CRUD, profile updates)
- ✅ Migration script for schema updates
- ✅ Singleton database connection with transactions
- ✅ Comprehensive helper functions

---

## 🏗️ Architecture

```
user-management-system/
├── .htaccess              # Security headers & access control
├── .gitignore             # Git ignore rules
├── README.md              # This file
├── LICENSE                # MIT License
├── database.sql           # Database schema (4 tables)
├── index.php              # Entry point (redirects to login)
├── logout.php             # Logout handler
├── migrate.php            # Database migration script
├── configs/               # Core infrastructure (12 files)
│   ├── app.php            # Application bootstrap
│   ├── env.php            # Environment config (GITIGNORED — use env.example.php)
│   ├── env.example.php    # Environment template
│   ├── Database.php       # Singleton PDO connection
│   ├── helpers.php        # Utility functions (10+ helpers)
│   ├── middleware.php     # Auth & session middleware
│   ├── csrf.php           # CSRF protection
│   ├── rate_limiter.php   # Rate limiting (DB + file fallback)
│   ├── Validator.php      # Input validation (12+ rules)
│   ├── Response.php       # Structured JSON responses
│   ├── ErrorHandler.php   # Centralized error handling
│   ├── login-db.php       # Login handler
│   ├── register-db.php    # Registration handler
│   ├── users_control.php  # User CRUD controller
│   └── profile_control.php # Profile controller
├── views/                 # Frontend views (11 files)
│   ├── header.php         # Shared header
│   ├── sidebar.php        # Shared sidebar (role-based)
│   ├── login.php          # Login page
│   ├── register.php       # Registration page
│   ├── dashboard.php      # Dashboard with charts
│   ├── users.php          # User management (search/filter/pagination)
│   ├── user_add.php       # Add user form
│   ├── user_edit.php      # Edit user form
│   ├── user_delete.php    # Delete confirmation
│   ├── admin.php          # Admin management (super_admin only)
│   └── profile.php        # User profile
├── assets/                # Static assets
│   ├── css/               # 5 CSS files (dashboard, login, users, profile, notifications)
│   └── js/                # 2 JS files (notifications, profile validation)
├── uploads/               # User uploads (GITIGNORED)
│   └── profiles/          # User avatars
├── storage/               # Runtime storage (GITIGNORED)
│   ├── runtime/
│   └── rate_limits/
└── logs/                  # Error logs (GITIGNORED)
```

### Design Patterns Used

| Pattern | Implementation |
|---|---|
| **Singleton** | `Database` class — single PDO connection |
| **Middleware** | `require_login()`, `require_admin()`, `require_super_admin()` |
| **Factory** | `Response::success()`, `Response::error()`, etc. |
| **Strategy** | Rate limiter with DB + file fallback strategies |
| **Template** | Shared header/sidebar with role-based rendering |

---

## 🛠️ Tech Stack

### Backend
| Technology | Version | Purpose |
|---|---|---|
| **PHP** | 7.4+ / 8.0+ | Server-side language |
| **MySQL** | 5.7+ / 8.0+ | Database |
| **PDO** | Built-in | Database abstraction |
| **Apache** | 2.4+ | Web server (with `.htaccess`) |

### Frontend
| Technology | Purpose |
|---|---|
| **Vanilla CSS** | Glassmorphism design, dark theme, RTL |
| **Vanilla JavaScript** | Notifications, form validation, charts |
| **Chart.js** | Analytics charts (CDN) |
| **Google Fonts** | Fraunces & Inter typography |

### Security Libraries
| Feature | Implementation |
|---|---|
| Password Hashing | `password_hash()` / `password_verify()` (bcrypt) |
| CSRF | Custom token-based system |
| Rate Limiting | Custom DB + file-based system |
| Input Validation | Custom `Validator` class |

---

## 🚀 Installation

### Prerequisites
- PHP 7.4 or higher (8.0+ recommended)
- MySQL 5.7 or higher (8.0+ recommended)
- Apache web server with `mod_rewrite` and `mod_headers`
- PHP extensions: `pdo_mysql`, `fileinfo`, `mbstring`, `gd`

### Steps

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/user-management-system.git
   cd user-management-system
   ```

2. **Create a MySQL database**
   ```sql
   CREATE DATABASE U_Management_Sys CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

3. **Import the database schema**
   ```bash
   mysql -u root -p U_Management_Sys < database.sql
   ```

4. **Configure environment**
   ```bash
   cp configs/env.example.php configs/env.php
   ```
   Edit `configs/env.php` and update your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'U_Management_Sys');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   ```

5. **Set directory permissions**
   ```bash
   chmod 755 storage/ storage/runtime/ storage/rate_limits/
   chmod 755 uploads/ uploads/profiles/
   chmod 755 logs/
   ```

6. **Run the migration script** (creates default super_admin)
   ```bash
   php -c php.ini migrate.php
   ```

7. **Open in browser**
   ```
   http://localhost/user-management-system/
   ```

### Default Credentials
After running the migration script:
- **Username:** `admin`
- **Email:** `admin@example.com`
- **Password:** `Admin@123`

> ⚠️ **Change this password immediately after first login!**

---

## 📖 Usage

### Login
Navigate to `index.php` — you'll be redirected to the login page. Enter your credentials.

### Dashboard
After logging in, you'll see the dashboard with:
- Statistics cards (total users, active/inactive, admins, growth)
- Charts (user growth, role distribution) — admin only
- Recent activity log

### User Management (Admin+)
- Navigate to **مدیریت کاربران** (Users Management)
- Search, filter by role/status, paginate
- Add, edit, delete (soft) users
- Toggle user status

### Admin Management (Super Admin Only)
- Navigate to **مدیریت ادمین‌ها** (Admin Management)
- Create new admin accounts
- View all admins
- Soft delete admins (cannot delete super_admins)

### Profile
- Navigate to **پروفایل** (Profile)
- Update username, email
- Upload avatar
- Change password (with current password verification)

---

## 🔒 Security

### Security Measures Implemented

1. **CSRF Protection**
   - Token generated per session with 32-byte random hex
   - Token expires after 1 hour
   - All forms include hidden CSRF token field
   - AJAX requests use meta tag token

2. **Rate Limiting**
   - Login: 5 attempts per 5 minutes
   - Registration: 3 attempts per hour
   - Profile updates: 10 attempts per minute
   - API: 60 requests per minute
   - Uses database with file-based fallback

3. **Session Security**
   - Custom session name (`ums_session`)
   - HTTP-only cookies
   - SameSite=Lax
   - Session ID regeneration on login
   - IP and User-Agent validation
   - 30-minute timeout

4. **Password Security**
   - Bcrypt hashing via `password_hash()`
   - Password strength validation (8+ chars, uppercase, lowercase, digit, special)
   - Current password verification for changes

5. **HTTP Security Headers**
   - Content-Security-Policy (CSP)
   - Strict-Transport-Security (HSTS)
   - X-Frame-Options: SAMEORIGIN
   - X-Content-Type-Options: nosniff
   - X-XSS-Protection: 1; mode=block
   - Referrer-Policy: strict-origin-when-cross-origin
   - Permissions-Policy: geolocation=(), microphone=(), camera=()

6. **Input Validation & Output Escaping**
   - Centralized `Validator` class with 12+ rules
   - All output escaped via `e()` helper (htmlspecialchars)
   - File upload validation (MIME type, size, extension)

7. **Database Security**
   - All queries use prepared statements
   - No string concatenation in SQL
   - Soft deletes (no data loss)
   - Proper indexing

8. **Access Control**
   - Role-based middleware (`require_login`, `require_admin`, `require_super_admin`)
   - Privilege escalation protection
   - Self-deletion/self-deactivation prevention

---

## 🗄️ Database Schema

### Tables

| Table | Description |
|---|---|
| `users` | User accounts with role, status, avatar, soft delete |
| `sessions` | Session tracking for revocation |
| `activity_log` | Audit trail of all actions |
| `rate_limits` | Rate limiting counters |

### Users Table
```sql
users (
    id          INT UNSIGNED  PRIMARY KEY AUTO_INCREMENT
    username    VARCHAR(50)   UNIQUE NOT NULL
    email       VARCHAR(255)  UNIQUE NOT NULL
    password    VARCHAR(255)  NOT NULL          -- bcrypt hash
    role        ENUM          DEFAULT 'user'    -- user|admin|super_admin
    status      ENUM          DEFAULT 'active'  -- active|inactive
    avatar      VARCHAR(255)  NULL              -- filename
    created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
    updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    deleted_at  TIMESTAMP     NULL              -- soft delete
)
```

---

## 🧪 Testing

### Manual Testing Checklist
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (rate limiting)
- [ ] Registration with weak password (validation)
- [ ] Registration with duplicate username/email
- [ ] CSRF token validation (expired token)
- [ ] User CRUD operations
- [ ] Role-based access control (user cannot access admin pages)
- [ ] Privilege escalation prevention
- [ ] Profile update with avatar upload
- [ ] Password change with current password verification
- [ ] Session timeout
- [ ] Rate limiting on login (5 attempts)

---

## 🤝 Contributing

Contributions are welcome! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add: description'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Coding Standards
- Follow PSR-12 coding standard
- Use meaningful variable and function names
- Add docblocks for all functions
- Validate all user input
- Escape all output
- Use prepared statements for all database queries

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## 👤 Author

Built as a portfolio project to demonstrate full-stack PHP development skills, with a focus on:
- Secure coding practices
- Clean architecture
- Modern UI/UX design
- Database design and optimization
- Security best practices

---

## ⭐ Show Your Support

If you find this project helpful, please give it a star on GitHub!

[![GitHub stars](https://img.shields.io/github/stars/yourusername/user-management-system?style=social)](https://github.com/yourusername/user-management-system/stargazers)
