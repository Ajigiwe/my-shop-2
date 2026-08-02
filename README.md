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
- ✅ Order confirmation page with tax-free pricing
- ✅ User authentication (register, login, logout, forgot password)
- ✅ Responsive design with Bootstrap 5
- ✅ Search functionality with autocomplete
- ✅ Contact form with success page
- ✅ User dashboard with order history
- ✅ Responsive navigation with mobile support

### Backend
- ✅ User registration and authentication with secure session management
- ✅ Product management system with categories and subcategories
- ✅ Shopping cart functionality with session persistence
- ✅ Order processing and management with status tracking
- ✅ Admin panel with CRUD for Products, Categories, Subcategories, Users
- ✅ Orders management with status updates and filtering
- ✅ Orders CSV export (Admin)
- ✅ Printable Invoice (Admin & User)
- ✅ Email notifications for order confirmations and status updates
- ✅ Secure session management and admin guard
- ✅ Input validation and sanitization
- ✅ Password hashing with PHP's password_hash()
- ✅ Email configuration with development mode for testing

### Security Features
- ✅ Password hashing using password_hash() with PASSWORD_DEFAULT
- ✅ Input sanitization and validation on all user inputs
- ✅ Prepared statements to prevent SQL injection
- ✅ Session-based authentication with proper session management
- ✅ CSRF protection ready
- ✅ Admin access control with role-based permissions
- ✅ Secure file upload handling
- ✅ XSS prevention with htmlspecialchars()
- ✅ Secure password reset functionality

## Setup Instructions

### 1. Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependency management)
- Node.js and npm (for frontend assets)

### 2. Database Setup
1. Start your web server and MySQL
2. Create a new database named `ecommerce_db`
3. Import the database schema:
   ```bash
   mysql -u [username] -p ecommerce_db < database_setup.sql
   ```
4. Apply database migrations:
   ```bash
   mysql -u [username] -p ecommerce_db < subcategories_migration.sql
   ```

### 3. Configuration
1. Copy `.env.example` to `.env` and update the values:
   ```env
   DB_HOST=localhost
   DB_NAME=ecommerce_db
   DB_USER=root
   DB_PASS=
   
   # Email Configuration
   MAIL_MAILER=smtp
   MAIL_HOST=smtp.mailtrap.io
   MAIL_PORT=2525
   MAIL_USERNAME=your_mailtrap_username
   MAIL_PASSWORD=your_mailtrap_password
   MAIL_ENCRYPTION=tls
   MAIL_FROM_ADDRESS=from@example.com
   MAIL_FROM_NAME="${APP_NAME}"
   ```

2. Set up email configuration (optional for development):
   - For development, you can use Mailtrap.io
   - Update the email settings in `.env`
   - In production, update with your SMTP credentials

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
2. Open browser and go to: `http://localhost/my-shop-2-main/`
3. Register a new account or login with admin credentials:
   - Email: admin@shop.com
   - Password: admin123

4. Admin URLs:
   - Admin Dashboard: `http://localhost/my-shop-2-main/admin/dashboard.php`
   - Manage Products: `http://localhost/my-shop-2-main/admin/manage_products.php`
   - Manage Categories: `http://localhost/my-shop-2-main/admin/manage_categories.php`
   - Manage Subcategories: `http://localhost/my-shop-2-main/admin/manage_subcategories.php`
   - Manage Orders: `http://localhost/my-shop-2-main/admin/manage_orders.php`
   - Manage Users: `http://localhost/my-shop-2-main/admin/manage_users.php`
   - Export Orders CSV: `http://localhost/my-shop-2-main/admin/export_orders.php`
   - Printable Invoice: `http://localhost/my-shop-2-main/admin/invoice.php?order_id=123`

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
│   ├── header.php           # HTML <head>, computes $base for nested paths, includes nav
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
│   │   └── avazonia.css     # Custom CSS (Avazonia design system)
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
- **Frontend:** HTML5, CSS3 (Avazonia design system)
- **JavaScript:** Vanilla JS with Fetch API

## Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## Features in Detail

### User Management
- User registration with email validation
- Secure password hashing with bcrypt
- Login/logout functionality with remember me
- Session management with security measures
- Password reset via email
- User profile management
- Admin user management with role-based access

### Product Management
- Product categories and subcategories
- Product search with autocomplete and filtering
- Product variants and attributes (ready for implementation)
- Stock management with low stock alerts
- Image upload with validation
- Bulk import/export functionality
- Product reviews and ratings system

### Shopping Cart
- Add/remove products with AJAX
- Quantity management with stock validation
- Session-based cart for guests
- Database cart for logged-in users
- Cart persistence across devices
- Coupon code support (ready for implementation)
- Cart expiration after inactivity

### Checkout Process
- Multi-step checkout process
- Billing and shipping address management
- Multiple payment methods (Cash on Delivery, etc.)
- Order summary with tax-free pricing
- Order confirmation page
- Email notifications for order status updates
- Order tracking for customers

### Admin Features
- Comprehensive admin dashboard with analytics
- Advanced product management with bulk actions
- Category and subcategory hierarchy management
- Order management with status updates
- Customer management with filtering
- Sales reports and analytics
- Export functionality (CSV/Excel)
- System settings and configuration
- Activity logs for security auditing
- Backup and restore functionality

### Email System
- Order confirmation emails
- Order status update notifications
- Password reset emails
- Welcome emails for new users
- Admin notifications for new orders
- Email templates with HTML support
- Development mode with email logging

### Security Features
- CSRF protection on all forms
- XSS prevention with output escaping
- SQL injection prevention with prepared statements
- Password policy enforcement
- Brute force protection
- Secure session handling
- File upload validation
- Security headers
- Rate limiting on authentication endpoints

### Performance
- Database query optimization
- Image optimization
- Caching mechanisms
- Lazy loading for images
- Minification of assets
- GZIP compression

### Currency & Formatting
- Site uses Ghana Cedis (GH₵). A global helper is available in `includes/db.php`:
  - `formatCurrency($amount)` returns strings like `GH₵1,234.50`.
  - Use this helper instead of hardcoded currency symbols.

### Path Handling in Includes
- `includes/header.php` computes `$base` by detecting if the current script runs under `/admin/` or `/user/`.
- Navigation links in `includes/header.php` use `<?php echo $base; ?>` so navigation works from nested folders.

## Security Considerations
- All inputs are sanitized and validated
- Passwords are hashed using password_hash()
- Prepared statements prevent SQL injection
- Session cookies are secure
- CSRF protection can be added

## Future Enhancements
- Payment gateway integration (PayPal)
- Email notifications
- Product reviews and ratings
- Wishlist functionality
- Advanced search filters
- PDF invoice generation (dompdf/mpdf)
- Payment gateway integration (PayPal)
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
