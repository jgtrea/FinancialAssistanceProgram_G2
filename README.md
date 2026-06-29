# Financial Assistance Program — Voucher Management System

![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)
![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4-EF4223?style=flat-square&logo=codeigniter&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![Select2](https://img.shields.io/badge/Select2-latest-5897FB?style=flat-square)
![mPDF](https://img.shields.io/badge/mPDF-8.x-0D9276?style=flat-square)
![PhpSpreadsheet](https://img.shields.io/badge/PhpSpreadsheet-5.x-217346?style=flat-square)
![PHPUnit](https://img.shields.io/badge/PHPUnit-10-3F67A8?style=flat-square)

> A web-based voucher management system for educational financial assistance programs targeting senior high school students.

---

## What It Does

| | Feature | Access |
|---|---|---|
| 🎓 | **Student Management** — add, edit, view student records | Admin + Staff |
| 🏫 | **School Management** — maintain JHS/SHS school records that drive dropdowns | Admin + Staff |
| 📄 | **Voucher Generation** — create per-student vouchers, export multi-slot PDFs with signatures | Admin + Staff |
| 📥 | **Excel Import** — bulk-import students and vouchers from `.xlsx` | Admin + Staff |
| 👤 | **Profile** — each user manages their own account | Admin + Staff |
| 📋 | **Audit Logs** — full action history; admins see all, staff see their own | Admin + Staff |
| ✍️ | **Signatory Management** — manage signatories and signature images for PDFs | Admin only |
| 🗄️ | **Archive** — soft-archive and restore students and signatories | Admin only |
| 👥 | **User Management** — create and manage staff accounts | Admin only |
| ⚡ | **Async PDF Queue** — background job queue so voucher generation never blocks the UI; batches >501 students are auto-chunked and bundled as a ZIP | — |

---

## Documentation

| Document | Description |
|---|---|
| [SETUP.md](SETUP.md) | Step-by-step installation and deployment guide (dev + production/LAN) |
| [CONFIG.md](CONFIG.md) | Tech stack, folder structure, environment variables, worker configuration, troubleshooting |
| [MANUAL.md](MANUAL.md) | User manual _(coming soon)_ |

---

## Quick Start

```bash
git clone https://github.com/jgtrea/FinancialAssistanceProgram_G2.git
cd FinancialAssistanceProgram_G2
composer install
cp env .env          # edit .env with your DB credentials
php spark migrate
php spark serve      # open http://localhost:8080
```

In a separate terminal, start the PDF worker:

```bash
php spark run:json-pdf-queue 5
```

See [SETUP.md](SETUP.md) for the full guide including production/LAN deployment.

---

## License

This project is intended for internal academic use. See [LICENSE](LICENSE) for details.
