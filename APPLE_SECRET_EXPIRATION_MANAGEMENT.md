# Apple Client Secret Expiration Management

## ❌ Reality Check: Cannot Make Unexpired

**Apple Client Secrets CANNOT be made unexpired.** Apple enforces a **6-month expiration** policy for security reasons, and this is non-negotiable.

## ✅ Automated Management Solution

I've created a comprehensive system to handle Apple Client Secret expiration automatically:

### **New Commands Created**

1. **`apple:manage-secret`** - Complete secret management
2. **`apple:schedule-check`** - Automated checking and renewal
3. **Scheduled Task** - Weekly automatic checks

## 🛠️ Usage Examples

### **Check Secret Status**
```bash
# Check current secret expiration
php artisan apple:manage-secret check --env=prod

# Check with auto-renewal if expired
php artisan apple:manage-secret check --env=prod --auto-renew
```

### **Generate New Secret**
```bash
# Generate new secret for specific environment
php artisan apple:manage-secret generate --env=prod
```

### **Renew Expired Secret**
```bash
# Renew secret (same as generate)
php artisan apple:manage-secret renew --env=prod
```

### **Check All Environments**
```bash
# Check all environments automatically
php artisan apple:schedule-check
```

## 📅 Automated Scheduling

The system now includes **automatic weekly checks** that will:

1. **Check all environments** (dev, staging, prod)
2. **Auto-renew expired secrets**
3. **Warn about secrets expiring soon** (within 30 days)
4. **Log all activities** for monitoring

### **Schedule Configuration**
- **Frequency**: Weekly
- **Logs**: `storage/logs/apple_secret_management.log`
- **Overlap Protection**: 30-minute timeout
- **Background Processing**: Yes

## 🔍 Secret Status Monitoring

### **Status Indicators**
- ✅ **Valid**: Secret is good for 30+ days
- ⚠️ **Warning**: Secret expires within 30 days
- ❌ **Expired**: Secret has expired

### **Example Output**
```
Apple Client Secret Status for prod
=====================================
Current Secret: eyJhbGciOiJFUzI1NiIsInR5cCI6IkpXVCJ9...
Expires: 2024-04-15 10:30:00
Days until expiry: 45
✅ Secret is valid for 45 more days
```

## 🔄 Manual Renewal Process

### **Step 1: Check Current Status**
```bash
php artisan apple:manage-secret check --env=prod
```

### **Step 2: Renew if Needed**
```bash
php artisan apple:manage-secret renew --env=prod
```

### **Step 3: Verify New Secret**
```bash
php artisan apple:manage-secret check --env=prod
```

## 📊 Monitoring and Alerts

### **Log Files**
- **Main Log**: `storage/logs/apple_secret_management.log`
- **Scheduled Tasks**: `storage/logs/cron.log`

### **Log Examples**
```
[2024-01-15 10:30:00] Apple Client Secret management completed successfully
[2024-01-15 10:30:00] Checked 3 environments
[2024-01-15 10:30:00] Renewed 1 client secret(s)
```

## 🚨 Emergency Procedures

### **If Secret Expires Unexpectedly**

1. **Immediate Action**:
   ```bash
   php artisan apple:manage-secret renew --env=prod
   ```

2. **Verify Fix**:
   ```bash
   php artisan apple:manage-secret check --env=prod
   ```

3. **Test Authentication**:
   ```bash
   curl -X POST http://yourdomain.com/api/auth/social/apple \
     -H "Content-Type: application/json" \
     -d '{"identity_token": "test_token"}'
   ```

## 📋 Best Practices

### **Proactive Management**
1. **Set up monitoring** for secret expiration
2. **Run weekly checks** manually if needed
3. **Keep backup credentials** in secure location
4. **Document renewal process** for team

### **Security Considerations**
1. **Never commit secrets** to version control
2. **Use environment-specific secrets**
3. **Rotate secrets regularly** (before expiration)
4. **Monitor access logs** for authentication failures

## 🔧 Configuration Requirements

### **Environment Variables Needed**
```env
APPLE_TEAM_ID=YOUR_TEAM_ID
APPLE_CLIENT_ID=com.yourcompany.yourapp
APPLE_KEY_ID=YOUR_KEY_ID
APPLE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----
YOUR_PRIVATE_KEY_CONTENT
-----END PRIVATE KEY-----"
APPLE_REDIRECT_URI=https://yourdomain.com/api/auth/social/apple/callback
APPLE_CLIENT_SECRET=YOUR_GENERATED_CLIENT_SECRET
```

### **Cron Job Setup** (Production)
```bash
# Add to crontab for automatic scheduling
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## 📈 Future Improvements

### **Planned Enhancements**
1. **Email notifications** for expiration warnings
2. **Slack/Discord integration** for alerts
3. **Dashboard monitoring** for secret status
4. **Automated testing** after renewal

### **Customization Options**
1. **Adjust warning threshold** (currently 30 days)
2. **Change check frequency** (currently weekly)
3. **Add custom environments** beyond dev/staging/prod
4. **Integrate with CI/CD** pipelines

## 🆘 Troubleshooting

### **Common Issues**

1. **"Missing configuration"**
   - Check all required environment variables are set
   - Verify environment file exists

2. **"Failed to sign JWT token"**
   - Verify private key format is correct
   - Check OpenSSL extension is enabled

3. **"Could not determine expiration"**
   - Secret format might be invalid
   - Try generating a new secret

4. **"Environment file not found"**
   - Create the appropriate .env file
   - Check file permissions

### **Debug Commands**
```bash
# Check PHP OpenSSL extension
php -m | grep openssl

# Verify environment variables
php artisan tinker
>>> config('services.apple')

# Test JWT generation
php artisan apple:manage-secret generate --env=dev
```

## 📞 Support

For issues with Apple Client Secret management:
1. Check the logs in `storage/logs/apple_secret_management.log`
2. Verify all environment variables are correctly set
3. Test with the debug commands above
4. Contact Apple Developer Support for Apple-specific issues

---

**Remember**: While we cannot make Apple Client Secrets unexpired, this automated system ensures you never have to worry about expiration again!
