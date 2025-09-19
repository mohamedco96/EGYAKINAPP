# 📧 Daily Report Mail List Setup Guide

## 🎯 **Overview**

Your daily report system now supports sending reports to multiple email addresses. You can configure a mail list in your `.env` file and the system will automatically send daily reports to all recipients.

## 🔧 **Configuration**

### **Step 1: Update Your .env File**

Add this line to your server's `.env` file:

```env
# Daily Report Mail List (comma-separated emails)
DAILY_REPORT_MAIL_LIST="mohamedco215@gmail.com,admin@egyakin.com,support@egyakin.com"

# Or use individual emails (optional)
ADMIN_EMAIL="mohamedco215@gmail.com"
```

### **Step 2: Clear Laravel Cache**

```bash
cd /home/mipzp4cjitnd/public_html/test.egyakin.com
php artisan config:clear
php artisan config:cache
```

## 📋 **Usage Options**

### **Option 1: Send to Mail List (Default)**
```bash
# Send to all emails in the mail list
php artisan reports:send-daily --mail-list
```

### **Option 2: Send to Single Email**
```bash
# Send to specific email
php artisan reports:send-daily --email=specific@email.com
```

### **Option 3: Send to Admin Email (Default)**
```bash
# Send to admin email (if no options provided)
php artisan reports:send-daily
```

## 🚀 **Current Schedule**

Your daily reports are automatically scheduled to run:
- **Time**: Every day at 9:00 AM
- **Recipients**: All emails in `DAILY_REPORT_MAIL_LIST`
- **Method**: Uses `--mail-list` option

## 📊 **Example .env Configuration**

```env
# Mail Configuration
MAIL_MAILER=brevo-api
MAIL_FROM_ADDRESS="noreply@egyakin.com"
MAIL_FROM_NAME="EGYAKIN"

# Admin Email (fallback)
ADMIN_EMAIL="mohamedco215@gmail.com"

# Daily Report Mail List
DAILY_REPORT_MAIL_LIST="mohamedco215@gmail.com,admin@egyakin.com,support@egyakin.com,manager@egyakin.com"

# Brevo API
BREVO_API_KEY="your-brevo-api-key"
```

## 🧪 **Testing Commands**

### **Test Mail List**
```bash
# Test sending to mail list
php artisan reports:send-daily --mail-list
```

### **Test Single Email**
```bash
# Test sending to specific email
php artisan reports:send-daily --email=test@example.com
```

### **Check Configuration**
```bash
# Check mail list configuration
php artisan config:show mail.daily_report_mail_list
```

## 📈 **Expected Output**

When sending to multiple recipients, you'll see:

```
🚀 Starting daily report generation...
📧 Preparing to send daily report to 3 recipient(s)
📊 Generating report data...
📡 Sending via Brevo API...
📧 Sending to: mohamedco215@gmail.com
✅ Sent to mohamedco215@gmail.com - Message ID: <202509191900.123456789@smtp-relay.mailin.fr>
📧 Sending to: admin@egyakin.com
✅ Sent to admin@egyakin.com - Message ID: <202509191900.987654321@smtp-relay.mailin.fr>
📧 Sending to: support@egyakin.com
✅ Sent to support@egyakin.com - Message ID: <202509191900.456789123@smtp-relay.mailin.fr>
✅ Daily report sent successfully to 3 recipient(s)
```

## 🔍 **Logging**

All email activities are logged in:
- `storage/logs/cron.log` - Cron execution logs
- `storage/logs/laravel.log` - Detailed email logs

## 📝 **Adding/Removing Recipients**

To add or remove recipients:

1. **Edit .env file**:
   ```env
   DAILY_REPORT_MAIL_LIST="email1@example.com,email2@example.com,email3@example.com"
   ```

2. **Clear cache**:
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. **Test**:
   ```bash
   php artisan reports:send-daily --mail-list
   ```

## 🎯 **Benefits**

- ✅ **Multiple Recipients**: Send to entire team
- ✅ **Flexible Configuration**: Easy to add/remove emails
- ✅ **Fallback Options**: Single email or admin email fallback
- ✅ **Detailed Logging**: Track all email deliveries
- ✅ **Error Handling**: Individual email failure doesn't stop others
- ✅ **Performance**: Optimized queries + caching

## 🚨 **Troubleshooting**

### **If No Emails Received**
```bash
# Check configuration
php artisan config:show mail.daily_report_mail_list

# Test manually
php artisan reports:send-daily --mail-list

# Check logs
tail -20 storage/logs/cron.log
```

### **If Some Emails Fail**
- Check email addresses for typos
- Verify Brevo API limits
- Check individual email delivery logs

---

**🎉 Your daily reports will now be sent to all configured email addresses every day at 9:00 AM!**
