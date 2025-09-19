# 🧪 Mail Testing Quick Reference

## 🚀 **Quick Test Commands**

### **Test All Mail Templates**
```bash
# Test all templates (recommended)
php artisan mail:test-all your-email@example.com --brevo

# Test all templates via Laravel Mail
php artisan mail:test-all your-email@example.com
```

### **Test Specific Categories**
```bash
# Test only Mailable classes
php artisan mail:test-all your-email@example.com --type=mailable --brevo

# Test only Notification classes
php artisan mail:test-all your-email@example.com --type=notification --brevo
```

### **Test Individual Templates**
```bash
# Test specific mail class
php artisan mail:test-all your-email@example.com --type=specific --specific=WelcomeMailNotification --brevo

# Test daily report
php artisan reports:send-daily --email=your-email@example.com

# Test weekly summary
php artisan reports:send-weekly --email=your-email@example.com
```

## 📋 **All Mail Templates**

### **📧 Mailable Classes (4)**
1. **DailyReportMail** - Daily platform statistics
2. **WeeklySummaryMail** - Weekly platform summary
3. **TestMail** - Email system testing
4. **VerifyEmail** - Email verification

### **🔔 Notification Classes (6)**
1. **WelcomeMailNotification** - Welcome new users
2. **EmailVerificationNotification** - OTP verification
3. **ResetPasswordVerificationNotification** - Password reset
4. **ReminderNotification** - User reminders
5. **ReachingSpecificPoints** - Achievement notifications
6. **ContactRequestNotification** - Contact form submissions

## 🎯 **Testing Scenarios**

### **Development Testing**
```bash
# Test all templates for development
php artisan mail:test-all dev@example.com --brevo
```

### **Production Testing**
```bash
# Test with production email
php artisan mail:test-all production@example.com --brevo
```

### **Specific Feature Testing**
```bash
# Test authentication emails
php artisan mail:test-all test@example.com --type=specific --specific=EmailVerificationNotification --brevo

# Test reporting emails
php artisan mail:test-all test@example.com --type=specific --specific=DailyReportMail --brevo
```

## 📊 **Expected Results**

### **Successful Test Output**
```
🚀 Starting EGYAKIN Mail Template Testing
📧 Testing email: test@example.com
🔧 Type: all
📡 Method: Brevo API

📊 Test Results Summary
═══════════════════════════════════════════════════════════════
✅ Successful: 10
❌ Failed: 0
📧 Total Tested: 10

🎉 All mail templates tested successfully!
```

### **Failed Test Output**
```
📊 Test Results Summary
═══════════════════════════════════════════════════════════════
✅ Successful: 8
❌ Failed: 2
📧 Total Tested: 10

⚠️  2 mail template(s) failed. Check the errors above.
```

## 🔧 **Troubleshooting**

### **Common Issues**
```bash
# Check Brevo API configuration
php artisan config:show services.brevo

# Test Brevo API connection
php artisan mail:test test@example.com --api

# Clear caches
php artisan config:clear
php artisan view:clear
```

### **Debug Commands**
```bash
# Check mail configuration
php artisan config:show mail

# Test individual template
php artisan mail:test-all test@example.com --type=specific --specific=TestMail --brevo

# Check logs
tail -f storage/logs/laravel.log | grep "mail\|brevo"
```

## 📈 **Performance Testing**

### **Load Testing**
```bash
# Test multiple templates quickly
for i in {1..5}; do
  php artisan mail:test-all test$i@example.com --brevo &
done
wait
```

### **Timing Analysis**
The test command provides detailed timing information:
- **Data Generation**: Time to prepare email data
- **Template Rendering**: Time to render HTML/text
- **API Call**: Time to send via Brevo API
- **Total Time**: Complete execution time

## 🎨 **Template Features**

### **Design Elements**
- ✅ **Responsive Design**: Mobile-friendly layouts
- ✅ **Modern HTML**: Professional email designs
- ✅ **Branding**: EGYAKIN colors and styling
- ✅ **Icons**: Emoji and visual elements
- ✅ **Animations**: CSS animations for engagement

### **Content Features**
- ✅ **Personalization**: Dynamic user data
- ✅ **Security**: OTP and verification codes
- ✅ **Analytics**: Comprehensive reporting
- ✅ **Call-to-Actions**: Clear action buttons
- ✅ **Multi-format**: HTML and text versions

## 🚀 **Best Practices**

### **Testing**
1. **Test Regularly**: Run tests after any changes
2. **Use Brevo API**: Test with actual delivery method
3. **Check All Templates**: Don't skip any templates
4. **Verify Delivery**: Check email inboxes
5. **Monitor Logs**: Watch for errors and issues

### **Development**
1. **Test Locally**: Use development email addresses
2. **Test Production**: Verify with production emails
3. **Test Edge Cases**: Test with invalid data
4. **Performance**: Monitor execution times
5. **Documentation**: Keep templates documented

---

**💡 Tip**: Bookmark this page for quick access to testing commands!
