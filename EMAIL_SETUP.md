# Email Functionality Setup Guide

This guide will help you set up the email functionality for the ASO Online Market website.

## Prerequisites

1. PHP 7.4 or higher
2. Composer (for installing PHPMailer)
3. SMTP credentials (Gmail, SendGrid, or other SMTP service)

## Installation Steps

### 1. Install Dependencies

Run the following command in your project root to install PHPMailer:

```bash
composer require phpmailer/phpmailer
```

### 2. Configure Email Settings

Edit the file `includes/config/mail_config.php` with your SMTP settings:

```php
return [
    'smtp' => [
        'host' => 'smtp.example.com',  // Your SMTP server
        'port' => 587,                // 587 for TLS, 465 for SSL
        'username' => 'your-email@example.com',
        'password' => 'your-email-password-or-app-password',
        'encryption' => 'tls',        // tls or ssl
        'from_email' => 'noreply@yourdomain.com',
        'from_name' => 'ASO Online Market',
        'admin_email' => 'admin@yourdomain.com', // Where contact form messages will be sent
    ],
    'debug' => 2, // 0 = off, 1 = client messages, 2 = client and server messages
];
```

### 3. Set Up Database Table

Run the SQL script to create the `contact_submissions` table:

```bash
mysql -u your_username -p your_database_name < sql/update_contact_table.sql
```

Or import the `update_contact_table.sql` file using phpMyAdmin.

### 4. Test the Contact Form

1. Go to the contact page
2. Fill out the form and submit it
3. Check if you receive both the admin notification and user confirmation emails

## Troubleshooting

### Emails Not Sending

1. Check your SMTP settings in `mail_config.php`
2. Verify your SMTP credentials
3. Check PHP error logs for any error messages
4. Make sure your server allows outbound SMTP connections (port 587 or 465)

### Gmail Users

If using Gmail, you'll need to:
1. Enable "Less secure app access" or
2. Use an App Password if you have 2FA enabled

### Common Issues

- **Authentication failed**: Double-check your username and password
- **Connection refused**: Check if your server allows outbound SMTP connections
- **Emails going to spam**: Configure SPF, DKIM, and DMARC records for your domain

## Security Considerations

1. Never commit your email credentials to version control
2. Consider using environment variables for sensitive information
3. Enable SSL/TLS for secure email transmission
4. Regularly check the `contact_submissions` table for any suspicious entries

## Support

If you encounter any issues, please check the following:
1. PHP error logs
2. SMTP server logs
3. Check if the `contact_submissions` table exists and has the correct structure

For additional help, please contact your system administrator or hosting provider.
