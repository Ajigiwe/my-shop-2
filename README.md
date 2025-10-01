# ASO Online Market

A complete PHP and MySQL e-commerce website built with Bootstrap for responsive design.

This document serves as the Developer Guide for ASO Online Market. It explains project setup, environment, important scripts (migrations), core features, and how the admin panel is structured.

## Features

### Frontend
- ✅ Homepage with featured products and categories
- ✅ Product listing with filters and pagination
- ✅ Product details page with add to cart functionality
- ✅ Shopping cart with quantity management
- ✅ Checkout process with billing and shipping forms
- ✅ Order confirmation page
- ✅ User authentication (register, login, logout, forgot password)
- ✅ Responsive design with Bootstrap
- ✅ Search functionality with autocomplete
- ✅ Contact and About pages

### Backend
- ✅ User registration and authentication
- ✅ Product management system (with subcategories)
- ✅ Shopping cart functionality
- ✅ Order processing and management
- ✅ Admin panel with CRUD for Products, Categories, Subcategories, Users
- ✅ Orders management with status updates
- ✅ Orders CSV export (Admin)
- ✅ Printable Invoice (Admin)
- ✅ Secure session management and admin guard
- ✅ Input validation and sanitization
- ✅ Password hashing with PHP's password_hash()

### Security Features
- ✅ Password hashing using password_hash()
- ✅ Input sanitization and validation
- ✅ Prepared statements to prevent SQL injection
- ✅ Session-based authentication
- ✅ CSRF protection ready

## Setup Instructions

### 1. Database Setup
1. Start XAMPP and ensure Apache and MySQL are running
2. Open phpMyAdmin (http://localhost/phpmyadmin)
3. Create a new database named `ecommerce_db`
4. Import the database schema:
   - Open the SQL tab in phpMyAdmin for `ecommerce_db`
   - Import `database_setup.sql`

5. Apply subcategories migration (adds `subcategories` table and optional sample rows):
   - Import `subcategories_migration.sql`

6. Optional: enable user activation toggles in Admin → Manage Users:
   - Run this SQL if you want Activate/Deactivate buttons to appear:
     ```sql
     ALTER TABLE users ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER role;
     ```

### 2. File Setup
1. Place all files in your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\My Shop\
   ```

2. Create placeholder images:
   - Run `create_images.php` in your browser or create placeholder images manually
   - Or copy the provided placeholder images to `assets/images/`

### 3. Configuration
1. Update database connection in `includes/db.php` if needed:
   ```php
   $host = 'localhost';
   $dbname = 'ecommerce_db';
   $username = 'root';
   $password = ''; // Default XAMPP password
   ```

### 4. Access the Website
1. Start XAMPP
2. Open browser and go to: `http://localhost/My Shop/`
3. Register a new account or login with admin credentials:
   - Email: admin@shop.com
   - Password: admin123

4. Admin URLs:
   - Admin Dashboard: `http://localhost/My Shop/admin/dashboard.php`
   - Manage Products: `http://localhost/My Shop/admin/manage_products.php`
   - Manage Categories: `http://localhost/My Shop/admin/manage_categories.php`
   - Manage Subcategories: `http://localhost/My Shop/admin/manage_subcategories.php`
   - Manage Orders: `http://localhost/My Shop/admin/manage_orders.php`
   - Manage Users: `http://localhost/My Shop/admin/manage_users.php`
   - Export Orders CSV: `http://localhost/My Shop/admin/export_orders.php`
   - Printable Invoice: `http://localhost/My Shop/admin/invoice.php?order_id=123`

## File Structure

```
My Shop/
├── index.php                 # Homepage
├── shop.php                  # Product listing
├── product.php               # Product details
├── cart.php                  # Shopping cart
├── checkout.php              # Checkout process
├── order_confirmation.php    # Order confirmation
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # User logout
├── forgot_password.php       # Password reset
├── about.php                 # About us page
├── contact.php               # Contact page
├── includes/
│   ├── db.php               # Database connection + helpers (sanitize, currency format, etc.)
│   ├── header.php           # HTML <head>, computes $base for nested paths, includes navbar
│   ├── navbar.php           # Navigation bar (uses $base and $site_name)
│   └── footer.php           # Footer
├── admin/
│   ├── dashboard.php            # Admin overview (stats, recent orders)
│   ├── manage_products.php      # CRUD products (+ image uploads)
│   ├── manage_categories.php    # CRUD categories
│   ├── manage_subcategories.php # CRUD subcategories (parent category linkage)
│   ├── manage_orders.php        # Orders list, status updates, details
│   ├── manage_users.php         # User roles, optional activate/deactivate, password reset
│   ├── invoice.php              # Printable invoice for an order
│   └── export_orders.php        # CSV export of orders
├── assets/
│   ├── css/
│   │   └── style.css        # Custom CSS
│   ├── js/
│   │   └── script.js        # Custom JavaScript
│   └── images/              # Product images
├── ajax/                    # AJAX handlers
│   ├── add_to_cart.php
│   ├── update_cart.php
│   ├── remove_from_cart.php
│   └── search.php
├── database_setup.sql           # Initial database schema
└── subcategories_migration.sql  # Adds subcategories + FK to products
```

## Default Admin Account
- **Email:** admin@shop.com
- **Password:** admin123
- **Role:** Admin

## Sample User Account
- **Email:** user@example.com
- **Password:** user123
- **Role:** Customer

## Technologies Used
- **Backend:** PHP 8.x
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, Bootstrap 5.3
- **JavaScript:** Vanilla JS with Fetch API
- **Icons:** Font Awesome 6.x

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Features in Detail

### User Management
- User registration with email validation
- Secure password hashing
- Login/logout functionality
- Session management
- Password reset (basic implementation)

### Product Management
- Product categories and subcategories
- Product search and filtering
- Product reviews and ratings (ready for implementation)
- Stock management
- Image upload ready

### Shopping Cart
- Add/remove products
- Quantity management
- Session-based cart for guests
- Database cart for logged-in users

### Checkout Process
- Billing and shipping address forms
- Payment method selection
- Order processing
- Order confirmation

### Admin Features
- Admin dashboard with store stats and recent orders
- Product, Category, and Subcategory management
- Order management with status updates
- User management (roles; optional activate/deactivate)
- Export Orders as CSV
- Printable Invoices per order (HTML; PDF-ready structure)

### Currency & Formatting
- Site uses Ghana Cedis (GH₵). A global helper is available in `includes/db.php`:
  - `formatCurrency($amount)` returns strings like `GH₵1,234.50`.
  - Use this helper instead of hardcoded currency symbols.

### Path Handling in Includes
- `includes/header.php` computes `$base` by detecting if the current script runs under `/admin/` or `/user/`.
- All links in `includes/navbar.php` use `<?php echo $base; ?>` so navigation works from nested folders.

## Security Considerations
- All inputs are sanitized and validated
- Passwords are hashed using password_hash()
- Prepared statements prevent SQL injection
- Session cookies are secure
- CSRF protection can be added

## Future Enhancements
- Payment gateway integration (PayPal, Paystack)
- Email notifications
- Product reviews and ratings
- Wishlist functionality
- Advanced search filters
- PDF invoice generation (dompdf/mpdf)
- Payment gateway integration (PayPal, Paystack)
- Email notifications
- Product reviews and ratings
- Wishlist functionality
- Advanced search filters
- Order tracking
- Inventory management
- Analytics dashboard
- Order tracking
- Inventory management
- Analytics dashboard

## Troubleshooting

### Common Issues:
1. **Database connection error:**
   - Check if MySQL is running in XAMPP
   - Verify database credentials in `includes/db.php`

2. **Images not loading:**
   - Run `create_images.php` to generate placeholders
   - Or upload actual product images to `assets/images/`

3. **Login issues:**
   - Clear browser cookies and cache
   - Check if sessions are enabled in PHP

4. **Cart not working:**
   - Ensure user is logged in
   - Check browser console for JavaScript errors

## Support
For issues or questions, please check the contact page or create an issue in the project repository.

---

**Note:** This is a demonstration e-commerce website. In production, additional security measures, error handling, and optimizations would be implemented.
# my-shop-2 initgit add README.mdgit commit -m first commitgit branch -M maingit remote add origin https://github.com/Ajigiwe/my-shop-2.gitgit push -u origin mainecho # my-shop-2
