# Contributing to ASO Online Market

Thank you for your interest in contributing! This document explains coding standards, workflows, and conventions for the project.

## Getting Started
- Clone the repo into your web root (e.g. `C:/xampp/htdocs/My Shop/`).
- Create the database `ecommerce_db` and import `database_setup.sql`.
- Apply `subcategories_migration.sql`.
- Optional: add `active` column to `users` per README instructions to enable user activation.

## Branching and Commits
- Use feature branches: `feature/<short-description>`, `fix/<short-description>`, `docs/<short-description>`.
- Write clear commit messages using imperative mood:
  - Example: `Add formatCurrency() helper and apply sitewide`

## Code Style
- PHP 8+.
- Prefer PDO with prepared statements; never concatenate untrusted input in SQL.
- Escape output using `htmlspecialchars()` when rendering user-provided text.
- Keep includes in `includes/` and admin-only logic under `admin/`.
- Use the provided helpers from `includes/db.php`:
  - `sanitizeInput($value)` for basic input cleaning.
  - `formatCurrency($amount)` for prices (Ghana Cedis, GH₵).
- Use `$base` from `includes/header.php` for asset and link paths in nested directories.

## PHPDoc & Comments
- File-level docblocks summarizing purpose and key behaviors.
- Function/method PHPDocs for parameters and returns when non-trivial.
- Inline comments for complex queries, branching logic, and security-sensitive code.

## Directory Conventions
- `includes/` shared resources (DB, header, navbar, footer, guards).
- `admin/` admin panel pages (CRUD, orders, users, exports, invoice).
- `assets/` static assets (CSS, JS, images).
- `ajax/` small request handlers if used.

## Database Conventions
- Use `INT AUTO_INCREMENT` primary keys named `<table>_id`.
- Foreign keys: enforce referential integrity with `ON DELETE CASCADE` where appropriate.
- Monetary columns: `DECIMAL(10,2)`.
- Status columns: `ENUM` with explicit allowed states.

## Security
- Always use prepared statements.
- Validate and sanitize user input server-side.
- Hash passwords with `password_hash()`; verify with `password_verify()`.
- Restrict admin pages with `includes/admin_guard.php`.

## Frontend
- Use Bootstrap 5.
- Keep custom styles in `assets/css/style.css`.
- Use Font Awesome icons via CDN.

## Testing Manual Flows
- Registration, login, logout.
- Cart add/update/remove (both with and without sufficient stock).
- Checkout happy path, and failure recovery (DB errors).
- Admin: CRUD for products/categories/subcategories, order status, users.
- CSV export and printable invoice.

## Submitting Changes
1. Ensure code runs locally without PHP errors.
2. Ensure currency formatting uses `formatCurrency()`.
3. Ensure paths work from root and nested folders using `$base`.
4. Open a PR with description, screenshots (if UI changes), and testing notes.

## Issue Reporting
- Include steps to reproduce, expected vs actual, screenshots/logs, and environment.
