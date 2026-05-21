# Financial Assistance Program — Voucher Management System

A web-based system for managing educational financial assistance vouchers for senior high school students. It handles student record management, voucher generation with PDF export, signatory management, archiving, and audit logging — with role-based access for admins and staff users.

---

## Features

- **Authentication & Role-Based Access** — separate Admin and Staff (User) roles with route-level protection
- **Student Management** — add, edit, archive, and restore student records
- **Voucher Generation** — create and track vouchers per student; generate multi-slot PDF vouchers with embedded signatory signatures
- **Signatory Management** — manage signatories with uploaded signature images used on generated PDFs
- **Archive** — soft-archive students and signatories with restore capability
- **Audit Logs** — track user actions system-wide; admins see all logs, staff see their own
- **User Management** *(Admin only)* — create, archive, and restore staff accounts
- **Excel Import** — bulk-import student/voucher data via `.xlsx` files
- **Async PDF Queue** — PDF generation runs as a background job queue to avoid timeouts on large batches

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | CodeIgniter 4 (PHP 8.2+) |
| PDF Generation | mPDF 8.x |
| Spreadsheet Import | PhpSpreadsheet 5.x |
| Database | MySQL (via CodeIgniter's Query Builder) |
| Frontend | Bootstrap + vanilla JS |
| Testing | PHPUnit 10 |

---

## Folder Structure

```
FinancialAssistanceProgram_G2/
├── app/
│   ├── Config/             # App configuration (routes, filters, database, etc.)
│   ├── Controllers/
│   │   ├── Admin/          # Admin-only controllers (Dashboard, Voucher, Report)
│   │   ├── User/           # Staff controllers (Dashboard, Voucher)
│   │   ├── Authentication.php
│   │   ├── SignatoryController.php
│   │   ├── StudentController.php
│   │   ├── ArchiveController.php
│   │   ├── AuditLogController.php
│   │   ├── UsersController.php
│   │   └── VoucherImport.php
│   ├── Database/
│   │   └── Migrations/     # Database migration files
│   ├── Filters/            # Auth and role-check filters
│   ├── Helpers/            # Audit and voucher helper functions
│   ├── Libraries/          # VoucherPdf (mPDF wrapper), PdfJobRunner
│   ├── Models/             # Eloquent-style models for each entity
│   └── Views/
│       ├── admin/          # Admin-specific pages
│       ├── auth/           # Login page
│       ├── layouts/        # Base layout (main.php)
│       ├── partials/       # Shared sidebar, topbar, footer
│       ├── signatories/    # Signatory list and form
│       ├── vouchers/       # Voucher list, form, generate, view
│       └── archive/        # Archive index
├── public/                 # Web root (index.php, assets)
├── writable/               # Logs, cache, uploads (not committed)
├── composer.json
└── .env                    # Local environment config (not committed)
```

---

## Getting Started

### Prerequisites

- PHP 8.2 or higher with extensions: `intl`, `mbstring`, `json`, `mysqlnd`, `gd` (for mPDF)
- Composer
- MySQL 5.7+ or MariaDB 10.3+
- A local web server (Apache/Nginx) or PHP's built-in server

### Setup

1. **Clone the repository**

   ```bash
   git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
   cd FinancialAssistanceProgram_G2
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Configure environment**

   ```bash
   cp env .env
   ```

   Edit `.env` and set at minimum:

   ```ini
   CI_ENVIRONMENT = development

   app.baseURL = 'http://localhost:8080/'

   database.default.hostname = localhost
   database.default.database = your_db_name
   database.default.username = your_db_user
   database.default.password = your_db_password
   database.default.DBDriver = MySQLi
   ```

4. **Run migrations**

   ```bash
   php spark migrate
   ```

5. **Start the development server**

   ```bash
   php spark serve
   ```

   Open [http://localhost:8080](http://localhost:8080) in your browser.

### Default Credentials

Seed an admin account manually or via a database seeder. The password hash algorithm used is `PASSWORD_ARGON2ID`.

### PDF Generation

Voucher PDFs are generated asynchronously via a job queue. Run the queue worker in a separate terminal (or set it up as a cron job):

```bash
php spark pdf:process-queue
```
