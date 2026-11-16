# 💻 Cyber Cafe Management System (CCMS)
### Modernized for PHP 8.2 • Containerized for Render • Aiven Cloud MySQL Ready

A complete, feature-rich web-based **Cyber Cafe Management System** modernized for **PHP 8.2**, containerized with **Docker Apache**, and pre-configured for **Aiven Cloud MySQL** with SSL encryption and Render reverse proxy port protection.

---

## 🚀 Live App Endpoints

| Resource | URL Path | Description |
|---|---|---|
| **Home / Landing** | `/` or `/index.php` | Root direct router (redirects safely to login) |
| **Admin Login** | `/ccms/index.php` | Administrative login portal |
| **1-Click DB Setup** | `/db_setup.php` | Visual Aiven SSL health check & database importer |
| **Admin Dashboard** | `/ccms/dashboard.php` | Main CCMS administrative control panel |
| **Admin Direct Router** | `/admin/login.php` | Directory slash port-safe router |

---

## 🔑 Default Admin Credentials

| Role | Username | Password | Access Level |
|---|---|---|---|
| **Administrator** | `admin` | `admin` | Full system control, computers, users, reports |

---

## ⚙️ Aiven Cloud MySQL Configuration

| Variable | Recommended / Configured Value |
|---|---|
| `DB_SERVER` | `mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com` |
| `DB_PORT` | `12893` |
| `DB_USERNAME` | `avnadmin` |
| `DB_PASSWORD` | *(Configured via Render Environment Variables / Auto-configured)* |
| `DB_NAME` | `ccmsdb` *(Tables: `tbladmin`, `tblcomputers`, `tblusers`)* |
| `DB_SSL` | `true` *(Enforced by Aiven Cloud)* |
| `PORT` | `10000` |
| `BASE_URL` | `https://cyber-cafe-management-system.onrender.com` |

---

## 🛠️ Key Architectural Modernizations

### 1. Aiven Cloud MySQL Compatibility
* **SSL Encryption (`MYSQLI_CLIENT_SSL`)**: Aiven enforces TLS/SSL (`require_secure_transport=ON`). The modernized `dbconnection.php` uses `mysqli_init()`, sets SSL certificate options, and connects with `MYSQLI_CLIENT_SSL`.
* **5-Digit Custom Port Support**: Safely parses and handles Aiven's 5-digit custom ports (e.g. `12893`, `26284`).
* **Zero-Downtime Fallback**: Attempts connection to database directly; if missing, creates the database automatically or falls back gracefully.
* **Aiven Primary Key Compatibility**: Resolves `sql_require_primary_key=ON` by embedding primary keys directly in schema definitions and setting `SET SESSION sql_require_primary_key = 0`.

### 2. PHP 8.x Compatibility
* **Safe Property Initialization**: Class properties in `DBConnection` are cleanly declared and initialized inside `__construct()`, eliminating PHP 8.x fatal property initialization errors.
* **Strict Type & Null Checking**: Added `if ($query && $query instanceof mysqli_result)` before `mysqli_num_rows()` and `mysqli_fetch_array()` across all admin pages to prevent PHP 8.2 `TypeError` exceptions.
* **Fixed Array Comparison Bug**: Replaced deprecated/broken PHP 7 construct `if ($ret > 0)` with safe array validation `if ($ret && isset($ret['ID']))`.
* **Session Validation Modernization**: Fixed legacy `strlen($_SESSION['ccmsaid']==0)` bug across all authentication checks to modern `if (empty($_SESSION['ccmsaid']))`.

### 3. Render Reverse Proxy & Port 10000 Fixes
* **Dynamic Port Binding**: `start.sh` reads `${PORT:-10000}` dynamically assigned by Render and configures Apache `ports.conf` and `000-default.conf`.
* **Port 10000 Leak Prevention**: Configured `ServerName localhost`, `UseCanonicalName Off`, and `UseCanonicalPhysicalPort Off` to prevent Apache from appending internal container port `:10000` to HTTP 301/302 redirects.
* **Smart `.htaccess`**: Port-stripping trailing slash rules detect `X-Forwarded-Proto: https` and clean internal port numbers from browser redirects.
* **Direct File Routing**: Root `index.php` and `admin/login.php` route directly to files, preventing directory trailing slash issues.

---

## 📦 How to Deploy on Render

### Step 1: Push Code to GitHub
```bash
git init
git add .
git commit -m "Deploy: Cyber Cafe Management System for Render & Aiven MySQL"
git branch -M main
git remote add origin https://github.com/abadhbiharisd-98/Cyber-Caffe-Management-System.git
git push -u origin main --force
```

### Step 2: Create Web Service on Render
1. Go to your [Render Dashboard](https://dashboard.render.com/).
2. Click **New +** → **Web Service**.
3. Select your repository: `Cyber-Caffe-Management-System`.
4. Choose **Docker** as the Environment / Runtime.
5. In **Environment Variables**, verify:
   * `PORT`: `10000`
   * `DB_SERVER`: `mysql-32d19a6e-abadhbihari-33eb.f.aivencloud.com`
   * `DB_PORT`: `12893`
   * `DB_USERNAME`: `avnadmin`
   * `DB_PASSWORD`: `<your-aiven-password>`
   * `DB_NAME`: `ccmsdb`
   * `DB_SSL`: `true`
6. Click **Create Web Service**.

### Step 3: Run 1-Click Database Setup
Once Render completes building and deploying:
1. Open your browser and navigate to:
   ```
   https://<your-render-subdomain>.onrender.com/db_setup.php
   ```
2. Verify the green **SSL Active • Connected** badge.
3. Click the blue **"🚀 1-Click Import Database"** button.
4. All tables and default admin account will be verified/populated in seconds.
5. Click **"Go to Admin Login"** and sign in with `admin` / `admin`!

---

## 📂 Project Structure
```text
├── Dockerfile                  # PHP 8.2 Apache container configuration
├── start.sh                    # Dynamic port & reverse proxy startup script
├── render.yaml                 # Infrastructure as Code (IaC) Render Blueprint
├── .htaccess                   # Port-stripping & reverse-proxy trailing slash rules
├── index.php                   # Root safe direct landing router
├── db_setup.php                # Visual Aiven SSL health check & 1-click installer
├── README.md                   # Comprehensive deployment documentation
├── admin/                      # Direct file routers preventing slash redirects
│   ├── index.php
│   └── login.php
├── SQL File/
│   ├── ccmsdb.sql              # Modernized database schema
│   └── ccmsdb.sql.original     # Safe untouched backup
└── ccms/                       # Core CCMS Web Application
    ├── index.php               # Admin login portal
    ├── dashboard.php           # Main admin dashboard
    ├── add-computer.php        # Manage computers
    ├── manage-computer.php
    ├── edit-computer-detail.php
    ├── add-users.php           # Manage cafe users & sessions
    ├── manage-newusers.php
    ├── manage-olduser.php
    ├── view-allusers.php
    ├── view-user-detail.php
    ├── search.php              # Search user records
    ├── bwdates-report-ds.php   # Date-range reports
    ├── bwdates-reports-details.php
    ├── adminprofile.php        # Admin profile settings
    ├── change-password.php     # Password update
    ├── forgot-password.php     # Password recovery
    ├── resetpassword.php
    ├── logout.php              # Session logout
    └── includes/
        ├── dbconnection.php    # Modernized Aiven SSL & PHP 8.x database driver
        ├── header.php          # Header template
        └── sidebar.php         # Sidebar template
```

---

## 🔒 Security Notes
* Original files were securely preserved with `.original` extension prior to any modifications.
* Database traffic is strictly encrypted over TLS via `MYSQLI_CLIENT_SSL`.
* Sensitive files (`.sql`, `.original`, `.bak`) are protected from direct HTTP access in `.htaccess`.
