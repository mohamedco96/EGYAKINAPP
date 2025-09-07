<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EGYAKIN Mail Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 0 0 8px 8px;
        }
        .test-details {
            background-color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
            border-left: 4px solid #007bff;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚀 EGYAKIN Mail Test</h1>
    </div>
    
    <div class="content">
        <h2>Hello!</h2>
        
        <p>This is a test email from the <strong>EGYAKIN</strong> application.</p>
        
        <div class="test-details">
            <h3>📋 Test Details:</h3>
            <ul>
                <li><strong>Sent at:</strong> {{ now()->format('Y-m-d H:i:s') }}</li>
                <li><strong>Mail Driver:</strong> {{ config('mail.default') }}</li>
                <li><strong>Application:</strong> EGYAKIN</li>
                <li><strong>Environment:</strong> {{ app()->environment() }}</li>
                <li><strong>From Address:</strong> {{ config('mail.from.address') }}</li>
                <li><strong>From Name:</strong> {{ config('mail.from.name') }}</li>
            </ul>
        </div>
        
        <p class="success">✅ If you received this email, your mail configuration is working correctly!</p>
        
        <p>This test email was generated using the <code>php artisan mail:test</code> command.</p>
    </div>
    
    <div class="footer">
        <p>Best regards,<br>
        <strong>EGYAKIN Development Team</strong></p>
        
        <p><small>This is an automated test email. Please do not reply.</small></p>
    </div>
</body>
</html>
