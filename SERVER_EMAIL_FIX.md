# Server Email Configuration Fix

## 🚨 Problem Identified

**Server Error**: Connection timeout on port 587 with TLS
**Root Cause**: Server using different SMTP settings than local

## 📊 Configuration Comparison

| Setting | Local (Working) | Server (Failing) | Fix Required |
|---------|----------------|------------------|--------------|
| Port | 465 | 587 | ✅ Change to 465 |
| Encryption | ssl | tls | ✅ Change to ssl |
| Host | smtp-relay.brevo.com | smtp-relay.brevo.com | ✅ Same |
| Username | 9665a3002@smtp-brevo.com | 9665a3002@smtp-brevo.com | ✅ Same |

## 🔧 Server .env Fix

Update your server's `.env` file with these changes:

```env
# Email Configuration - FIX THESE LINES
MAIL_FROM_NAME="${APP_NAME}"
MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=465                    # ← CHANGE FROM 587 TO 465
MAIL_USERNAME=9665a3002@smtp-brevo.com
MAIL_PASSWORD=H03ISsVF2CNdPOQj
MAIL_ENCRYPTION=ssl              # ← CHANGE FROM tls TO ssl
MAIL_FROM_ADDRESS="noreply@egyakin.com"

# Admin Email (Keep as is)
ADMIN_EMAIL=mohamedco215@gmail.com
MAIL_ADMIN_EMAIL=mohamedco215@gmail.com
```

## 🚀 Steps to Fix

### 1. Update Server .env File
```bash
# SSH into your server
ssh your-server

# Navigate to your project
cd ~/public_html/test.egyakin.com

# Edit .env file
nano .env

# Make these changes:
# MAIL_PORT=465
# MAIL_ENCRYPTION=ssl

# Save and exit (Ctrl+X, Y, Enter)
```

### 2. Clear Configuration Cache
```bash
# Clear Laravel config cache
php artisan config:clear
php artisan config:cache
```

### 3. Test the Fix
```bash
# Test email again
php artisan mail:test mohamedco215@gmail.com
```

## 🔍 Why This Happened

1. **Different Environments**: Local vs Server had different configurations
2. **Port Mismatch**: 587 (TLS) vs 465 (SSL)
3. **Encryption Mismatch**: TLS vs SSL
4. **Brevo Requirements**: Brevo works better with SSL on port 465

## 📋 Alternative Brevo Configurations

If port 465 doesn't work, try these alternatives:

### Option 1: Port 587 with TLS (if 465 fails)
```env
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

### Option 2: Port 25 (if both fail)
```env
MAIL_PORT=25
MAIL_ENCRYPTION=null
```

### Option 3: Port 2525 (alternative)
```env
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
```

## 🧪 Testing Commands

After making changes, test with:

```bash
# Basic test
php artisan mail:test mohamedco215@gmail.com

# Test with verbose output
php artisan mail:test mohamedco215@gmail.com -v

# Test different types
php artisan mail:test mohamedco215@gmail.com --type=daily-report
```

## 🔧 Additional Server Fixes

### Fix Locale Warning (Optional)
```bash
# Add to server .env
LC_ALL=en_US.UTF-8
LANG=en_US.UTF-8
LANGUAGE=en_US.UTF-8
```

### Check Server Firewall
```bash
# Test if ports are accessible
telnet smtp-relay.brevo.com 465
telnet smtp-relay.brevo.com 587
```

## 📊 Expected Result

After the fix, you should see:
```
✅ Email sent successfully!
📋 Test Summary:
   • Email Type: simple
   • Recipient: mohamedco215@gmail.com
   • Mail Driver: smtp
   • From Address: noreply@egyakin.com
   • From Name: EGYAKIN
```

## 🎯 Summary

**Issue**: Port/encryption mismatch between local and server
**Fix**: Change server to use port 465 + SSL encryption
**Status**: Ready to implement
