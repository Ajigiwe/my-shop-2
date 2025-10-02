<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thank You for Contacting Us</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a6da7; color: white; padding: 15px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 12px; text-align: center; color: #777; }
        .message { margin: 20px 0; padding: 15px; background-color: #e9f7fe; border-left: 4px solid #4a6da7; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Thank You for Contacting Us</h2>
        </div>
        <div class="content">
            <p>Dear <?php echo htmlspecialchars($name ?? 'Valued Customer'); ?>,</p>
            
            <p>Thank you for reaching out to us. We have received your message and our team will get back to you as soon as possible.</p>
            
            <div class="message">
                <p><strong>Your Message:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($message ?? '')); ?></p>
            </div>
            
            <p>If you have any additional information to add, please reply to this email.</p>
            
            <p>Best regards,<br>The ASO Online Market Team</p>
        </div>
        <div class="footer">
            <p>This is an automated message, please do not reply directly to this email.</p>
            <p>© <?php echo date('Y'); ?> ASO Online Market. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
