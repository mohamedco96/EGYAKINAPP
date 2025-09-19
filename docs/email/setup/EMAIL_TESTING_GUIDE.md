# Email Testing Guide for EGYAKIN

This guide provides comprehensive information about email configuration and testing in the EGYAKIN application.

## 📧 Email Configuration Review

### Current Configuration (`config/mail.php`)

Your email configuration is well-structured with the following key settings:

- **Default Mailer**: `smtp` (configurable via `MAIL_MAILER` env variable)
- **From Address**: Configurable via `MAIL_FROM_ADDRESS` env variable
- **From Name**: Configurable via `MAIL_FROM_NAME` env variable
- **Admin Email**: `mohamedco215@gmail.com` (fallback from `ADMIN_EMAIL` or `MAIL_ADMIN_EMAIL`)

### Supported Mail Drivers

Your configuration supports multiple mail drivers:
- **SMTP** (default)
- **SES** (Amazon Simple Email Service)
- **Mailgun**
- **Postmark**
- **Sendmail**
- **Log** (for testing)
- **Array** (for testing)
- **Failover** (SMTP with Log fallback)

### Environment Variables Required

To properly configure email, ensure these variables are set in your `.env` file:

```env
# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="EGYAKIN Team"

# Admin Email
ADMIN_EMAIL=admin@yourdomain.com
MAIL_ADMIN_EMAIL=admin@yourdomain.com
```

## 🧪 Email Testing Command

I've created a comprehensive email testing command: `php artisan mail:test`

### Command Usage

```bash
# Basic usage - will prompt for email address
php artisan mail:test

# Send to specific email
php artisan mail:test user@example.com

# Test different email types
php artisan mail:test user@example.com --type=simple
php artisan mail:test user@example.com --type=daily-report
php artisan mail:test user@example.com --type=verify-email

# Custom subject and body
php artisan mail:test user@example.com --subject="Custom Test" --body="Custom message"
```

### Command Options

- `email` (optional): Email address to send test email to
- `--type`: Type of test email (`simple`, `daily-report`, `verify-email`)
- `--subject`: Custom subject for the email
- `--body`: Custom body for the email

### What the Command Does

1. **Displays Current Configuration**: Shows your current mail settings
2. **Validates Email**: Ensures the email address format is correct
3. **Sends Test Email**: Sends the appropriate test email based on type
4. **Provides Feedback**: Shows success/failure status with detailed information
5. **Logs Errors**: Logs any failures for debugging

## 📁 Files Created

### 1. Test Command
- **File**: `app/Console/Commands/TestMailCommand.php`
- **Purpose**: Main command for testing email functionality

### 2. Test Mail Class
- **File**: `app/Mail/TestMail.php`
- **Purpose**: Mailable class for simple test emails

### 3. Test Email Template
- **File**: `resources/views/emails/test.blade.php`
- **Purpose**: HTML template for test emails

## 🔧 Testing Different Scenarios

### 1. Test SMTP Configuration
```bash
php artisan mail:test your-email@gmail.com --type=simple
```

### 2. Test Daily Report Email
```bash
php artisan mail:test admin@yourdomain.com --type=daily-report
```

### 3. Test Email Verification
```bash
php artisan mail:test user@example.com --type=verify-email
```

### 4. Test with Custom Content
```bash
php artisan mail:test user@example.com --subject="Custom Test" --body="This is a custom test message"
```

## 🐛 Troubleshooting

### Common Issues

1. **SMTP Authentication Failed**
   - Check your email credentials
   - Ensure you're using app passwords for Gmail
   - Verify SMTP settings

2. **Connection Timeout**
   - Check firewall settings
   - Verify SMTP host and port
   - Test network connectivity

3. **Email Not Received**
   - Check spam folder
   - Verify recipient email address
   - Check email provider's delivery logs

### Debug Mode

To enable detailed logging, set in your `.env`:
```env
LOG_LEVEL=debug
MAIL_LOG_CHANNEL=mail
```

### Using Log Driver for Testing

For development/testing without sending actual emails:
```env
MAIL_MAILER=log
```

This will log emails to `storage/logs/laravel.log` instead of sending them.

## 📊 Existing Mail Classes

Your application already has these mail classes:

1. **DailyReportMail** (`app/Mail/DailyReportMail.php`)
   - Comprehensive daily statistics report
   - Includes user, patient, consultation, and system metrics

2. **VerifyEmail** (`app/Mail/VerifyEmail.php`)
   - Email verification functionality
   - Simple verification email template

3. **WeeklySummaryMail** (`app/Mail/WeeklySummaryMail.php`)
   - Weekly summary reports

## 🚀 Next Steps

1. **Configure Environment Variables**: Set up your `.env` file with proper email credentials
2. **Test Basic Functionality**: Run `php artisan mail:test` to verify configuration
3. **Test Different Types**: Try all three email types to ensure they work
4. **Monitor Logs**: Check `storage/logs/laravel.log` for any issues
5. **Production Setup**: Configure production email service (SES, Mailgun, etc.)

## 📝 Notes

- The command includes comprehensive error handling and logging
- All test emails are clearly marked as test emails
- The command provides detailed feedback about the current configuration
- Email templates are responsive and professional-looking
- The system supports both HTML and plain text emails

## 🔒 Security Considerations

- Never commit email credentials to version control
- Use environment variables for all sensitive information
- Consider using app-specific passwords for Gmail
- Regularly rotate email credentials
- Monitor email sending limits and quotas

---

**Command Created**: `php artisan mail:test`
**Files Created**: 3 files (Command, Mail Class, Template)
**Status**: ✅ Ready for testing
