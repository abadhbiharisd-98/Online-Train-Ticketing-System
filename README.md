# Online Train Station Ticketing System 🚆

A complete, production-ready Train Station Ticketing and Passenger Fare Management web application built with PHP and MySQLi, containerized with Docker, optimized for **Render Cloud** hosting, and connected to **Aiven Cloud MySQL** with SSL encryption.

---

## 🌟 Features & Highlights

- **Aiven Cloud MySQL Native Support**: Built-in TLS/SSL encryption (`MYSQLI_CLIENT_SSL`) compatible with Aiven's mandatory `require_secure_transport=ON`. Supports 5-digit custom ports (e.g. `26284`).
- **PHP 8.2 Compatibility**: Fully refactored for PHP 8.x strict standards with constructor-based class initialization, null-safety checks, and exception guards.
- **Render Reverse Proxy & Port 10000 Fixes**:
  - Dynamic `$PORT` binding in Apache startup script (`start.sh`).
  - Apache canonical redirect isolation (`UseCanonicalName Off`, `UseCanonicalPhysicalPort Off`, `ServerName localhost`) preventing the internal `:10000` port from leaking into public URLs.
  - Direct file routing (`login.php`, `index.php`) preventing directory trailing-slash redirect loops.
  - `.htaccess` smart port-stripping and SSL proto-detection.
- **1-Click Visual Database Importer (`db_setup.php`)**:
  - Live SSL health check showing MySQL server version, host, and SSL cipher.
  - 1-click SQL parser with `SET FOREIGN_KEY_CHECKS = 0` to import tables without manual phpMyAdmin hassle.
  - Automatic admin user verification.

---

## 🔑 Default Credentials

| Role | Username | Password | Access URL |
| :--- | :--- | :--- | :--- |
| **System Administrator** | `admin` | `admin123` | `/login.php` or `/index.php` |
| **Station 1 Staff** | `s1staff` | *(seeded)* | `/login.php` -> Ticketing Counter |
| **Station 2 Staff** | `s2staff` | *(seeded)* | `/login.php` -> Ticketing Counter |

---

## 🚀 Environment Variables (Aiven Cloud & Render)

Configure these environment variables in your Render Web Service dashboard:

| Variable Name | Description | Example Value |
| :--- | :--- | :--- |
| `DB_SERVER` | Aiven Cloud MySQL hostname | `mysql-3cxxxx-myproject.aivencloud.com` |
| `DB_PORT` | Aiven 5-digit custom port | `26284` |
| `DB_USERNAME` | Aiven database user | `avnadmin` |
| `DB_PASSWORD` | Aiven user password | `AVNS_xxxxxxxxx` |
| `DB_NAME` | MySQL database name | `defaultdb` or `tsts_db` |
| `DB_SSL` | Enforce SSL encryption flag | `true` |
| `BASE_URL` | Your public Render service URL | `https://your-service.onrender.com` |

---

## 🛠️ Deployment on Render

### Option A: 1-Click Render Blueprint (`render.yaml`)
1. In the [Render Dashboard](https://dashboard.render.com/), click **New +** and select **Blueprint**.
2. Connect your GitHub repository: `abadhbiharisd-98/Online-Train-Ticketing-System`.
3. Fill in your Aiven MySQL database credentials under Environment Variables.
4. Click **Apply** to trigger automatic Docker build and deployment.

### Option B: Manual Web Service
1. In the Render Dashboard, click **New +** > **Web Service**.
2. Connect your GitHub repository.
3. Select Environment: **Docker**.
4. Set Region to your preferred region (e.g., Oregon / Frankfurt).
5. Add the Environment Variables listed in the table above.
6. Click **Create Web Service**.

---

## 💾 1-Click Database Setup on Live URL

Once deployed on Render:
1. Open `https://<your-render-app>.onrender.com/db_setup.php` in your browser.
2. Confirm the green **Connected (SSL Active)** badge.
3. Click the green **1-Click Import SQL** button.
4. Once completed, click **Go to Login Page** and sign in with `admin` / `admin123`.

---

## 📁 Project Structure

```
├── Dockerfile                   # PHP 8.2 Apache Docker container spec
├── start.sh                     # Dynamic port & Reverse Proxy startup script
├── .htaccess                    # Reverse proxy SSL & port-stripping rules
├── render.yaml                  # Render Infrastructure-as-Code blueprint
├── db_connect.php               # Aiven SSL & PHP 8.x database connector
├── db_setup.php                 # 1-Click visual database installer & health check
├── login.php                    # Secure login page with direct routing
├── index.php                    # Admin Dashboard entry point
├── admin_class.php              # Backend business logic & CRUD controller
├── stations.php                 # Train stations management
├── prices.php                   # Destination rates & fare matrix
├── daily_report.php             # Daily sales & ticketing transaction reports
├── site_settings.php            # Ticketing branding & configuration
├── assets/                      # CSS, JS, Bootstrap, Barcode library
├── database/                    # SQL schema dump (tsts_db.sql)
└── ticketing/                   # Station staff ticketing booth portal
```

---

## 🛡️ Original File Backups

All original source files are safeguarded with `.original` backups:
- `db_connect.php.original`
- `admin_class.php.original`
- `login.php.original`
- `index.php.original`
- `stations.php.original`
- `prices.php.original`
- `site_settings.php.original`
- `users.php.original`
- `daily_report.php.original`
- `ticketing/home.php.original`
- `ticketing/topbar.php.original`
- `ticketing/index.php.original`
