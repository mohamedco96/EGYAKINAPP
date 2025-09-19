# EGYAKIN Mail List System

## Overview
The EGYAKIN platform now features a unified mail list system that allows sending emails to multiple recipients with a single API call, reducing email quota usage and improving efficiency.

## Configuration

### Environment Variables
Add this single mail list to your `.env` file:

```env
# Admin Mail List - Used for ALL system notifications (comma-separated emails)
# Includes: Daily Reports, Weekly Summaries, Contact Requests, etc.
ADMIN_MAIL_LIST=admin@egyakin.com,manager@egyakin.com,support@egyakin.com
```

### Mail Configuration
The system automatically configures this in `config/mail.php`:

```php
'admin_mail_list' => env('ADMIN_MAIL_LIST', 'mohamedco215@gmail.com'),
```

## Mail List Service

### MailListService Class
The `App\Services\MailListService` provides centralized mail list management:

```php
use App\Services\MailListService;

// Get admin mail list (used for ALL notifications)
$adminEmails = MailListService::getAdminMailList();

// Convenience aliases (all return the same admin mail list)
$reportEmails = MailListService::getDailyReportMailList();
$weeklyEmails = MailListService::getWeeklyReportMailList();

// Get specific mail list
$emails = MailListService::getMailList('admin_mail_list');

// Get primary email (first in list)
$primaryEmail = MailListService::getPrimaryEmail('admin_mail_list');
```

### Key Features
- ✅ **Email Validation**: Automatically validates email formats
- ✅ **Comma-Separated Support**: Parses comma-separated email strings
- ✅ **Array Support**: Handles both string and array formats
- ✅ **Fallback System**: Graceful fallback to default emails
- ✅ **Empty Filtering**: Removes empty or invalid emails

## Enhanced Brevo API Service

### Multiple Recipients Support
New method added to `App\Services\BrevoApiService`:

```php
// Send to multiple recipients in one email
$result = $brevoService->sendEmailToMultipleRecipients(
    $recipients,        // Array of email addresses
    $subject,          // Email subject
    $htmlContent,      // HTML content
    $textContent,      // Text content (optional)
    $from             // From details (optional)
);
```

### Benefits
- 🎯 **Single API Call**: One email to multiple recipients
- 💰 **Quota Efficient**: Reduces API usage and costs
- 📊 **Better Logging**: Centralized recipient tracking
- ⚡ **Faster Delivery**: No loops, single request

## Updated Commands

### Daily Report Command
```bash
# Send to single email
php artisan reports:send-daily --email=admin@example.com

# Send to mail list (all recipients in one email)
php artisan reports:send-daily --mail-list

# Default behavior (uses admin email)
php artisan reports:send-daily
```

**Enhanced Features:**
- ✅ Uses `MailListService::getAdminMailList()` (via getDailyReportMailList alias)
- ✅ Sends one email with all recipients
- ✅ Improved logging with recipient list
- ✅ Automatic email validation

### Weekly Summary Command
```bash
# Send to single email
php artisan reports:send-weekly --email=admin@example.com

# Send to mail list (all recipients in one email)
php artisan reports:send-weekly --mail-list

# Default behavior (uses admin email)
php artisan reports:send-weekly
```

**Enhanced Features:**
- ✅ Uses `MailListService::getAdminMailList()`
- ✅ Sends one email with all recipients
- ✅ Improved logging with recipient list
- ✅ Automatic email validation

## Updated Notifications

### ContactRequestNotification
Enhanced to support mail list recipients:

```php
// Traditional usage (single recipient)
$user->notify(new ContactRequestNotification($recipientEmails, $message));

// With mail list support (automatic)
// Uses admin_mail_list from configuration if recipientEmails is empty
```

**Features:**
- ✅ **Automatic Mail List**: Uses admin mail list when no specific recipients provided
- ✅ **Multiple Recipients**: Sends to all recipients in one email
- ✅ **Fallback System**: Falls back to notifiable email if mail list empty

## Scheduled Tasks

### Updated Schedule Configuration
In `app/Console/Kernel.php`:

```php
// Daily Report - Send to mail list
$schedule->command('reports:send-daily --mail-list')
    ->dailyAt('09:00');

// Weekly Summary - Send to mail list  
$schedule->command('reports:send-weekly --mail-list')
    ->weeklyOn(1, '09:00');
```

### Benefits
- 📅 **Consistent Recipients**: Same mail list for all scheduled reports
- 🔄 **Centralized Management**: Update mail list in one place
- 📊 **Unified Reporting**: All stakeholders receive reports simultaneously

## Email Quota Optimization

### Before (Individual Emails)
```
Daily Report: 3 separate API calls for 3 recipients
Weekly Summary: 3 separate API calls for 3 recipients
Contact Request: 1 API call per notification

Total: 6+ API calls per week
```

### After (Single Email with Multiple Recipients)
```
Daily Report: 1 API call for all recipients
Weekly Summary: 1 API call for all recipients  
Contact Request: 1 API call for all recipients

Total: 2+ API calls per week (70% reduction!)
```

## Usage Examples

### Environment Setup
```env
# Production setup - Single mail list for ALL notifications
ADMIN_MAIL_LIST=ceo@egyakin.com,cto@egyakin.com,admin@egyakin.com,support@egyakin.com

# Development setup - Single mail list for ALL notifications
ADMIN_MAIL_LIST=dev@example.com
```

### Code Usage
```php
// Get mail list in your code
$recipients = MailListService::getAdminMailList();
// Returns: ['admin@egyakin.com', 'support@egyakin.com']

// Send email to multiple recipients
$brevoService = new BrevoApiService();
$result = $brevoService->sendEmailToMultipleRecipients(
    $recipients,
    'Subject',
    '<h1>HTML Content</h1>',
    'Text Content'
);

if ($result['success']) {
    Log::info('Email sent to multiple recipients', [
        'recipients' => $recipients,
        'message_id' => $result['message_id']
    ]);
}
```

### Testing Commands
```bash
# Test daily report with mail list
php artisan reports:send-daily --mail-list

# Test weekly summary with mail list
php artisan reports:send-weekly --mail-list

# Test specific email override
php artisan reports:send-daily --email=test@example.com

# Test mail template with multiple recipients
php artisan mail:test-all test@example.com --type=specific --specific=ContactRequestNotification
```

## Migration Guide

### For Existing Installations

1. **Update Environment Variables**
   ```env
   # Add to .env file - Single mail list for ALL notifications
   ADMIN_MAIL_LIST=admin@yoursite.com,manager@yoursite.com,support@yoursite.com
   ```

2. **Update Scheduled Commands**
   - Commands automatically use `--mail-list` flag in scheduled tasks
   - No manual intervention required

3. **Test Configuration**
   ```bash
   # Test the new mail list system
   php artisan reports:send-daily --mail-list
   php artisan reports:send-weekly --mail-list
   ```

### Backward Compatibility
- ✅ **Existing Commands**: All existing command options still work
- ✅ **Single Email Mode**: `--email` option still supported
- ✅ **Default Behavior**: Falls back to admin email if mail list not configured
- ✅ **Notifications**: Existing notification usage remains unchanged

## Monitoring and Logs

### Enhanced Logging
```php
// Daily/Weekly Report Logs
Log::info('Daily report sent successfully via Brevo API', [
    'recipients' => ['admin@egyakin.com', 'manager@egyakin.com'],
    'message_id' => 'brevo-message-id-123',
    'timestamp' => '2025-09-19T10:00:00Z'
]);

// Contact Request Logs
Log::info('Brevo API email sent successfully to multiple recipients', [
    'recipients' => ['admin@egyakin.com', 'support@egyakin.com'],
    'subject' => 'New Contact Request',
    'message_id' => 'brevo-message-id-456'
]);
```

### Performance Monitoring
- 📊 **API Usage**: Monitor Brevo API call reduction
- ⏱️ **Delivery Time**: Track email delivery performance
- 📈 **Success Rate**: Monitor delivery success across recipients
- 🔍 **Error Tracking**: Centralized error logging for mail list failures

## Benefits Summary

### For Administrators
- 🎯 **Centralized Management**: Update recipient lists in one place
- 💰 **Cost Efficiency**: Reduced API usage and email quota consumption
- 📊 **Better Visibility**: All stakeholders receive reports simultaneously
- 🔧 **Easy Configuration**: Simple environment variable setup

### For Developers
- 🛠️ **Reusable Service**: `MailListService` for consistent mail list handling
- 📝 **Clean Code**: Centralized recipient logic
- 🧪 **Easy Testing**: Simple command-line testing options
- 📚 **Good Documentation**: Comprehensive usage examples

### For System Performance
- ⚡ **Faster Delivery**: Single API call instead of multiple loops
- 📉 **Reduced Load**: Less API overhead and processing time
- 🔄 **Better Reliability**: Fewer API calls mean fewer potential failure points
- 📊 **Improved Logging**: Centralized tracking and monitoring

The mail list system provides a robust, efficient, and cost-effective solution for managing multiple email recipients across all EGYAKIN email communications.
