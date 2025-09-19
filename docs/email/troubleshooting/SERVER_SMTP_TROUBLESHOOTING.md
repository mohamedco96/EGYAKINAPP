# Server SMTP Troubleshooting Guide

## 🚨 Current Issue
**Error**: Connection timeout to `ssl://smtp-relay.brevo.com:465`
**Cause**: Server network/firewall restrictions

## 🔍 Diagnostic Commands

Run these commands on your server to diagnose the issue:

### 1. Test Network Connectivity
```bash
# Test basic connectivity
ping smtp-relay.brevo.com

# Test specific ports
telnet smtp-relay.brevo.com 465
telnet smtp-relay.brevo.com 587
telnet smtp-relay.brevo.com 25
```

### 2. Check DNS Resolution
```bash
# Check if DNS is working
nslookup smtp-relay.brevo.com
dig smtp-relay.brevo.com
```

### 3. Check Server Firewall
```bash
# Check firewall rules
iptables -L OUTPUT | grep -E "(465|587|25)"
ufw status  # if using UFW
```

### 4. Test Outbound Connections
```bash
# Test if server can make outbound connections
curl -v https://google.com
wget -O- https://google.com
```

## 🔧 Alternative SMTP Configurations

Try these configurations in order:

### Option 1: Port 587 with TLS
```env
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

### Option 2: Port 25 (No Encryption)
```env
MAIL_PORT=25
MAIL_ENCRYPTION=null
```

### Option 3: Port 2525
```env
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
```

### Option 4: Alternative Brevo Host
```env
MAIL_HOST=smtp.brevo.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
```

## 🚀 Quick Fix Commands

### Test Each Configuration
```bash
# After each .env change, run:
php artisan config:clear
php artisan config:cache
php artisan mail:test mohamedco215@gmail.com
```

### Check Current Configuration
```bash
# Verify current settings
php artisan tinker
>>> config('mail.mailers.smtp')
```

## 🔧 Server-Specific Solutions

### If Hosting Provider Blocks SMTP
Many hosting providers block outbound SMTP ports. Check with your provider:

1. **cPanel/WHM**: Check "Tweak Settings" → "SMTP Restrictions"
2. **Cloudflare**: Check firewall rules
3. **Server Firewall**: Check iptables/ufw rules

### Alternative Email Services
If Brevo continues to fail, consider:

1. **SendGrid**: Often works better with hosting providers
2. **Mailgun**: Good alternative
3. **Amazon SES**: Reliable option
4. **Hosting Provider SMTP**: Use your host's SMTP

## 📋 SendGrid Alternative Setup

If Brevo doesn't work, try SendGrid:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=your-sendgrid-api-key
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@egyakin.com"
MAIL_FROM_NAME="EGYAKIN"
```

## 🔍 Debug Mode

Enable detailed logging:

```env
# Add to .env
LOG_LEVEL=debug
MAIL_LOG_CHANNEL=mail
```

Then check logs:
```bash
tail -f storage/logs/laravel.log
```

## 🎯 Expected Results

### Successful Connection
```
✅ Email sent successfully!
📋 Test Summary:
   • Email Type: simple
   • Recipient: mohamedco215@gmail.com
   • Mail Driver: smtp
```

### Still Failing
If all configurations fail, the issue is likely:
1. **Hosting provider blocking SMTP**
2. **Server firewall restrictions**
3. **Network connectivity issues**

## 🚀 Next Steps

1. **Run diagnostic commands** above
2. **Try alternative configurations**
3. **Contact hosting provider** if needed
4. **Consider alternative email service**

## 📞 Hosting Provider Checklist

Ask your hosting provider:
- Are outbound SMTP ports (25, 465, 587) blocked?
- Do you need to whitelist Brevo's IPs?
- Is there a specific SMTP service I should use?
- Are there any firewall restrictions?

## 🎯 Summary

**Issue**: Server cannot connect to Brevo SMTP
**Likely Cause**: Hosting provider restrictions
**Solution**: Try alternative configurations or email services
