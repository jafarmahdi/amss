# Asset Management System

This repository contains the initial scaffolding for a professional enterprise-grade Asset Management System. The stack is PHP with a Laravel-like structure, Bootstrap 5 frontend, and MySQL database. The system tracks assets across HQ and multiple branches with full movement history, reporting, and user roles.

## Getting Started

1. Install PHP 8+, MySQL, and a web server (e.g., Apache or Nginx).
2. Configure your database and update the connection settings in \`.env\` (create one from .env.example).
3. Run migrations or import `database/schema.sql` to set up the schema.
4. Place the project in your web server's document root (e.g., `htdocs/asset`).

## Structure

- `app/Models/` - Eloquent-style model classes
- `database/` - SQL schema and future migrations
- `routes/` - Web and API route definitions
- `resources/views/` - Blade-like templates using Bootstrap 5

The system includes features such as:

- Multi-branch asset tracking
- Storage/warehouse inventory
- Asset movement history and audit logs
- Barcode/QR generation
- Role-based permissions
- Dashboard with statistics and reports

**Performance tip:**
The dashboard runs aggregation queries on the `assets` and `asset_movements` tables.  When the data set grows large it's important to make sure there are indexes on `assets.status` and `asset_movements.moved_at` (they are created in the supplied schema).  The stats method has also been rewritten to use a single query for speed.

Further implementation will flesh out controllers, services, and UI pages.

---

*This is a starting point; run `composer install` when dependencies become available.*