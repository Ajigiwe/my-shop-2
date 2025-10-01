# Changelog

All notable changes to ASO Online Market will be documented in this file.

## [Unreleased]
- Add PDF invoice generation.
- Integrate payment gateways (PayPal, Paystack).

## [2025-09-26] Currency Standardization, Subcategories, Admin Improvements
- Currency
  - Implemented global `formatCurrency($amount)` helper with GH₵ symbol in `includes/db.php`.
  - Replaced all `number_format(..., 2)` usages for prices with `formatCurrency()` across storefront and admin.
- Subcategories
  - Added `subcategories` table and `products.subcategory_id` via `subcategories_migration.sql`.
  - Implemented subcategory filters in `shop.php` and support in product CRUD.
- Admin Panel Enhancements
  - `admin/manage_orders.php`: status update action, order list, and order details.
  - `admin/export_orders.php`: CSV export for orders.
  - `admin/invoice.php`: printable invoice for orders.
  - `admin/dashboard.php`: stats cards and recent orders table.
  - Added docblocks and inline comments to all admin pages.
- Site Name & Navigation
  - Standardized site name to "ASO Online Market".
  - Added `$base` path logic in `includes/header.php`; updated `includes/navbar.php` to use `$base`.
- Documentation
  - Revamped `README.md` into a Developer Guide.
  - Added `CONTRIBUTING.md` with coding standards.
  - Added comments to major storefront and order flow pages.
