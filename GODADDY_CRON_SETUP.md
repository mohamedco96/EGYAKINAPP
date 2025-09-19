# 🚀 GoDaddy Server Cron Setup Guide

## 📋 Your Server Details
- **Server**: 92.205.0.163
- **Username**: mipzp4cjitnd
- **Path**: /home/mipzp4cjitnd/public_html/test.egyakin.com
- **Email**: mohamedco215@gmail.com

---

## 🎯 **Option 1: cPanel Setup (Recommended)**

### **Step 1: Access cPanel**
1. Go to your GoDaddy account
2. Find your hosting account
3. Click "Manage" → "cPanel"

### **Step 2: Find Cron Jobs**
1. In cPanel, search for "Cron Jobs"
2. Click on "Cron Jobs"

### **Step 3: Add New Cron Job**
**Command:**
```bash
cd /home/mipzp4cjitnd/public_html/test.egyakin.com && php artisan reports:send-daily --email=mohamedco215@gmail.com >> /home/mipzp4cjitnd/public_html/test.egyakin.com/storage/logs/cron.log 2>&1
```

**Schedule Settings:**
- **Minute**: 0
- **Hour**: 9
- **Day**: *
- **Month**: *
- **Weekday**: *

**Description**: EGYAKIN Daily Report (9 AM Daily)

### **Step 4: Test Cron Job (Every 5 Minutes)**
For testing, add another cron job:
**Command:** (Same as above)
**Schedule:**
- **Minute**: */5
- **Hour**: *
- **Day**: *
- **Month**: *
- **Weekday**: *

---

## 🔧 **Option 2: SSH Setup**

### **Step 1: Connect via SSH**
```bash
ssh mipzp4cjitnd@92.205.0.163
# Password: Sonic!@#96
```

### **Step 2: Navigate to Project**
```bash
cd /home/mipzp4cjitnd/public_html/test.egyakin.com
```

### **Step 3: Create Setup Script**
```bash
# Create the setup script
cat > setup_cron.sh << 'EOF'
#!/bin/bash
echo "🚀 Setting up EGYAKIN Daily Report Cron Job"
echo "=========================================="

PROJECT_DIR="/home/mipzp4cjitnd/public_html/test.egyakin.com"
echo "Project directory: $PROJECT_DIR"

# Create log file
echo "Creating log file..."
mkdir -p storage/logs
touch storage/logs/cron.log
chmod 644 storage/logs/cron.log

# Backup existing crontab
echo "Backing up existing crontab..."
crontab -l > crontab_backup_$(date +%Y%m%d_%H%M%S).txt 2>/dev/null || echo "No existing crontab found"

# Add cron job
echo "Adding cron job..."
(crontab -l 2>/dev/null; echo "") | crontab - 2>/dev/null || true
(crontab -l 2>/dev/null; echo "# EGYAKIN Daily Report - Daily at 9:00 AM") | crontab -
(crontab -l 2>/dev/null; echo "0 9 * * * cd $PROJECT_DIR && php artisan reports:send-daily --email=mohamedco215@gmail.com >> $PROJECT_DIR/storage/logs/cron.log 2>&1") | crontab -

# Add test cron job (every 5 minutes)
echo "Adding test cron job (every 5 minutes)..."
(crontab -l 2>/dev/null; echo "# EGYAKIN Daily Report - Test (every 5 minutes)") | crontab -
(crontab -l 2>/dev/null; echo "*/5 * * * * cd $PROJECT_DIR && php artisan reports:send-daily --email=mohamedco215@gmail.com >> $PROJECT_DIR/storage/logs/cron.log 2>&1") | crontab -

echo "✅ Cron job setup complete!"
echo ""
echo "📋 Current cron jobs:"
crontab -l
echo ""
echo "📝 To monitor logs: tail -f storage/logs/cron.log"
echo "🧪 To test manually: php artisan reports:send-daily --email=mohamedco215@gmail.com"
EOF

chmod +x setup_cron.sh
```

### **Step 4: Run Setup Script**
```bash
./setup_cron.sh
```

### **Step 5: Test Manually**
```bash
# Test the command
php artisan reports:send-daily --email=mohamedco215@gmail.com

# Check logs
tail -f storage/logs/cron.log
```

---

## 🧪 **Testing Steps**

### **1. Test Command Manually**
```bash
cd /home/mipzp4cjitnd/public_html/test.egyakin.com
php artisan reports:send-daily --email=mohamedco215@gmail.com
```

### **2. Check Brevo API Key**
```bash
# Verify API key is set
php artisan config:show services.brevo
```

### **3. Monitor Logs**
```bash
# Watch logs in real-time
tail -f storage/logs/cron.log

# Check recent logs
tail -20 storage/logs/cron.log
```

### **4. Verify Cron Job**
```bash
# List cron jobs
crontab -l
```

---

## 🔍 **Troubleshooting**

### **Common Issues:**

1. **Permission Denied**
   ```bash
   chmod 755 storage/logs
   chmod +x artisan
   ```

2. **PHP Path Issues**
   ```bash
   # Find PHP path
   which php
   # Use full path: /usr/bin/php artisan ...
   ```

3. **Environment Variables**
   ```bash
   # Check .env file
   cat .env | grep BREVO_API_KEY
   ```

4. **Database Connection**
   ```bash
   # Test database
   php artisan migrate:status
   ```

---

## 📧 **Email Verification**

### **Check Email Delivery:**
1. Check mohamedco215@gmail.com inbox
2. Check spam folder
3. Verify Brevo API logs

### **Test Email Functionality:**
```bash
php artisan mail:test mohamedco215@gmail.com --api
```

---

## ⚡ **Quick Commands Summary**

```bash
# Connect to server
ssh mipzp4cjitnd@92.205.0.163

# Navigate to project
cd /home/mipzp4cjitnd/public_html/test.egyakin.com

# Test daily report
php artisan reports:send-daily --email=mohamedco215@gmail.com

# Check cron jobs
crontab -l

# Monitor logs
tail -f storage/logs/cron.log

# Add cron job manually
crontab -e
# Add: 0 9 * * * cd /home/mipzp4cjitnd/public_html/test.egyakin.com && php artisan reports:send-daily --email=mohamedco215@gmail.com >> /home/mipzp4cjitnd/public_html/test.egyakin.com/storage/logs/cron.log 2>&1
```

---

## ✅ **Success Checklist**

- [ ] Connected to server
- [ ] Navigated to project directory
- [ ] Tested command manually
- [ ] Added cron job (daily at 9 AM)
- [ ] Added test cron job (every 5 minutes)
- [ ] Verified logs are being written
- [ ] Confirmed email delivery
- [ ] Monitored for 10+ minutes

---

**🎯 Recommendation**: Use **cPanel method** first as it's easier and more reliable for GoDaddy shared hosting.

