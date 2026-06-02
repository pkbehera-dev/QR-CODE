# QAMS — Smart QR Asset Management System

QAMS (Smart QR Asset Management System) is an enterprise-grade physical asset registry and tracking platform built with vanilla PHP and MySQL. It features role-based access control, automated sequential serial prefixing, client-side dynamic QR generation, sub-component mapping, and a millimeter-calibrated sticker printing engine.

## 🚀 Features

- **Multi-Tenant Asset Registry**: Isolated portals with specific controls for Admins, Staff/Teachers, and Students/Custodians.
- **Smart QR Code Generation**: Instant client-side high-resolution QR matrix rendering using `QRCodeJS` linked to dynamic scanning endpoints.
- ** millimetre-Calibrated Sticker Output**: Dynamic 3-column sticker print previews optimized for physical adhesive label stock.
- **Sub-Component Registry**: Hierarchical hardware accessory linking (e.g. CPU, RAM, Monitor) mapped under a single master QR serial key.
- **Mobile Telemetry Lookup**: Mobile-friendly scanner lookup endpoints allowing quick checks on physical asset status and assignments via any smartphone.
- **Advanced Security & Verification**: Integrated reCAPTCHA Enterprise verification, strict session CSRF protection, inputs sanitization, and SQL Injection prevention using PDO statements.
- **Modern UI Dashboard**: Rich dashboard experience built with custom CSS, containing stats, interactive forms, and quick filters.

## 🛠️ Technology Stack

- **Backend**: PHP 8.x (Vanilla, MVC Controller pattern)
- **Database**: MySQL / MariaDB (via PDO)
- **Frontend**: HTML5, Vanilla JavaScript, CSS3 (Custom design system), Bootstrap 5 (Forms and layouts)
- **Libraries**: [QRCodeJS](https://github.com/davidshimjs/qrcodejs)
- **Server**: Apache (with custom `.htaccess` clean URL rewrite engine)

## 📦 Installation & Setup

### Prerequisites
- Apache Web Server (with `mod_rewrite` enabled)
- PHP 8.0 or higher
- MySQL / MariaDB

### 1. Database Configuration
1. Import the database schema setup into your MySQL server:
   ```sql
   CREATE DATABASE qr_serial_db;
   USE qr_serial_db;
   /* Import your SQL dump tables here */
   ```

### 2. Environment Setup
1. Copy `.env.example` to create your custom `.env` file at the root:
   ```bash
   cp .env.example .env
   ```
2. Open `.env` and fill in your database, SMTP, and reCAPTCHA credentials:
   ```env
   # Database Credentials
   DB_HOST=localhost
   DB_NAME=qr_serial_db
   DB_USER=your_db_user
   DB_PASS=your_db_password

   # SMTP Mail Settings
   SMTP_HOST=your_mail_host
   SMTP_PORT=587
   SMTP_USER=your_smtp_user
   SMTP_PASS=your_smtp_password
   SMTP_FROM=your_sender_email

   # reCAPTCHA Enterprise Settings
   RECAPTCHA_SITE_KEY=your_site_key
   RECAPTCHA_SECRET_KEY=your_secret_key
   ```

### 3. Server Configuration
- Ensure your Apache Virtual Host is pointing to the project directory and has `AllowOverride All` enabled to let the `.htaccess` rules handle extensionless URLs (e.g. `/login` instead of `/login.php`).

## 📄 License

This project is licensed under the [GNU General Public License v3.0 (GPL-3.0)](LICENSE).
