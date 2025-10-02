<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Contact Form Submission</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4a6da7; color: white; padding: 15px; text-align: center; }
        .content { padding: 20px; background-color: #f9f9f9; }
        .footer { margin-top: 20px; font-size: 12px; text-align: center; color: #777; }
        .field { margin-bottom: 10px; }
        .field-label { font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>New Contact Form Submission</h2>
        </div>
        <div class="content">
            <div class="field">
                <span class="field-label">Name:</span> <?php echo htmlspecialchars($name ?? 'Not provided'); ?>
            </div>
            <div class="field">
                <span class="field-label">Email:</span> <?php echo htmlspecialchars($email ?? 'Not provided'); ?>
            </div>
            <div class="field">
                <span class="field-label">Phone:</span> <?php echo htmlspecialchars($phone ?? 'Not provided'); ?>
            </div>
            <div class="field">
                <span class="field-label">Subject:</span> <?php echo htmlspecialchars($subject ?? 'No subject'); ?>
            </div>
            <div class="field">
                <span class="field-label">Message:</span>
                <p><?php echo nl2br(htmlspecialchars($message ?? 'No message')); ?></p>
            </div>
            <?php if (isset($newsletter) && $newsletter): ?>
                <div class="field">
                    <em>This user has opted in to receive the newsletter.</em>
                </div>
            <?php endif; ?>
        </div>
        <div class="footer">
            <p>This email was sent from the contact form on <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
