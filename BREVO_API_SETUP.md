# Brevo API Setup for GoDaddy Shared Hosting

## 🎯 **Perfect Solution for GoDaddy SMTP Restrictions!**

Using Brevo's API bypasses all SMTP restrictions on GoDaddy shared hosting. This is the ideal solution for your situation.

## 🚀 **Quick Setup**

### 1. Add Brevo API Key to .env
```env
# Add this to your server's .env file
BREVO_API_KEY=value
```

### 2. Test Brevo API
```bash
# Test Brevo API directly
php artisan mail:test mohamedco215@gmail.com --api

# Or specify type directly
php artisan mail:test mohamedco215@gmail.com --type=brevo-api
```

## 📁 **Files Created**

1. **`app/Services/BrevoApiService.php`** - Main Brevo API service
2. **`app/Mail/BrevoApiMail.php`** - Brevo API mail class
3. **Updated `app/Console/Commands/TestMailCommand.php`** - Added Brevo API support
4. **Updated `config/services.php`** - Added Brevo configuration

## 🔧 **Configuration**

### Environment Variables
```env
# Required
BREVO_API_KEY=value

# Optional (uses mail config defaults)
MAIL_FROM_ADDRESS="noreply@egyakin.com"
MAIL_FROM_NAME="EGYAKIN"
```

### Services Configuration
```php
// config/services.php
'brevo' => [
    'api_key' => env('BREVO_API_KEY'),
],
```

## 🧪 **Testing Commands**

### Basic Brevo API Test
```bash
php artisan mail:test mohamedco215@gmail.com --api
```

### Specific Type Test
```bash
php artisan mail:test mohamedco215@gmail.com --type=brevo-api
```

### Custom Subject/Body
```bash
php artisan mail:test mohamedco215@gmail.com --api --subject="Custom Test" --body="Custom message"
```

## 📊 **Expected Output**

```
🚀 Starting Email Test...

📋 Current Mail Configuration:
   • Default Mailer: smtp
   • From Address: noreply@egyakin.com
   • From Name: EGYAKIN
   • Admin Email: mohamedco215@gmail.com
   • Brevo API Key: ***configured***

📧 Sending brevo-api test email to: mohamedco215@gmail.com

📡 Brevo API Response:
   • Message ID: 12345678-1234-1234-1234-123456789012

✅ Email sent successfully!

📋 Test Summary:
   • Email Type: brevo-api
   • Recipient: mohamedco215@gmail.com
   • Mail Driver: smtp
   • From Address: noreply@egyakin.com
   • From Name: EGYAKIN
```

## 🔧 **Using Brevo API in Your Code**

### Method 1: Direct Service Usage
```php
use App\Services\BrevoApiService;

$brevoService = new BrevoApiService();
$result = $brevoService->sendEmail(
    'user@example.com',
    'Subject',
    '<h1>HTML Content</h1>',
    'Plain text content'
);

if ($result['success']) {
    echo "Email sent! Message ID: " . $result['message_id'];
} else {
    echo "Error: " . $result['error'];
}
```

### Method 2: Using BrevoApiMail Class
```php
use App\Mail\BrevoApiMail;

$brevoMail = new BrevoApiMail(
    'user@example.com',
    'Subject',
    '<h1>HTML Content</h1>',
    'Plain text content'
);

$result = $brevoMail->sendViaBrevoApi();
```

## 🎯 **Advantages of Brevo API**

### ✅ **Benefits**
- **Bypasses SMTP restrictions** on GoDaddy shared hosting
- **No port blocking issues**
- **Reliable delivery**
- **Detailed tracking and analytics**
- **Professional email templates**
- **High deliverability rates**

### 📊 **API vs SMTP Comparison**

| Feature | SMTP | Brevo API |
|---------|------|-----------|
| GoDaddy Compatibility | ❌ Blocked | ✅ Works |
| Port Restrictions | ❌ Blocked | ✅ No ports needed |
| Delivery Tracking | ❌ Limited | ✅ Detailed |
| Analytics | ❌ None | ✅ Full analytics |
| Templates | ❌ Basic | ✅ Advanced |
| Reliability | ❌ Depends on server | ✅ High |

## 🔧 **Advanced Usage**

### Send Template Email
```php
$brevoService = new BrevoApiService();
$result = $brevoService->sendTemplateEmail(
    'user@example.com',
    123, // Template ID
    ['name' => 'John', 'company' => 'EGYAKIN']
);
```

### Test API Connection
```php
$brevoService = new BrevoApiService();
$result = $brevoService->testConnection();

if ($result['success']) {
    echo "Connected to: " . $result['account'];
} else {
    echo "Error: " . $result['error'];
}
```

## 🚀 **Migration from SMTP**

### Replace SMTP Calls
```php
// Old SMTP way
Mail::to($email)->send(new TestMail());

// New Brevo API way
$brevoMail = new BrevoApiMail($email, $subject, $htmlContent);
$result = $brevoMail->sendViaBrevoApi();
```

### Update Existing Mail Classes
You can modify existing mail classes to use Brevo API:

```php
class DailyReportMail extends Mailable
{
    // ... existing code ...
    
    public function sendViaBrevoApi()
    {
        $brevoService = new BrevoApiService();
        return $brevoService->sendEmail(
            config('mail.admin_email'),
            $this->envelope()->subject,
            view('emails.daily-report', ['data' => $this->reportData])->render()
        );
    }
}
```

## 📋 **Troubleshooting**

### Common Issues

1. **API Key Invalid**
   - Check if API key is correct
   - Verify key has email sending permissions

2. **Rate Limits**
   - Brevo has rate limits
   - Check your account limits

3. **Domain Verification**
   - Ensure your domain is verified in Brevo
   - Check SPF/DKIM records

### Debug Mode
```env
LOG_LEVEL=debug
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

## 🎯 **Next Steps**

1. **Add API key** to your server's .env file
2. **Test the API** with the command
3. **Update your application** to use Brevo API
4. **Monitor delivery** in Brevo dashboard
5. **Set up templates** for better emails

## 📞 **Support**

- **Brevo Documentation**: https://developers.brevo.com/
- **API Reference**: https://developers.brevo.com/reference
- **Rate Limits**: Check your Brevo account dashboard

## 🎯 **Summary**

**Problem**: GoDaddy blocks SMTP connections
**Solution**: Brevo API bypasses all restrictions
**Status**: ✅ Ready to implement
**Files Created**: 4 files (Service, Mail Class, Updated Command, Config)

This solution will work perfectly on your GoDaddy shared hosting!
