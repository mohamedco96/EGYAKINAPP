# Patient Outcome Reminder Testing Guide

## 🧪 **Testing Without Waiting 72 Hours**

You now have a dedicated testing command that allows you to test the reminder system immediately with custom time thresholds.

## 📋 **Available Testing Commands**

### **1. Quick Dry-Run Test**
```bash
# Test with 1 hour threshold (no emails sent)
php artisan reminder:test --hours=1 --dry-run

# Test with your email (no emails sent)
php artisan reminder:test --hours=1 --email=your@email.com --dry-run
```

### **2. Create Test Data and Test**
```bash
# Create test data and run dry-run
php artisan reminder:test --hours=1 --create-test-data --dry-run

# Create test data and send real email to your address
php artisan reminder:test --hours=1 --create-test-data --email=your@email.com
```

### **3. Test with Existing Data**
```bash
# Look for existing submit_status records from last 24 hours
php artisan reminder:test --hours=24 --email=your@email.com --dry-run

# Look for existing records from last week
php artisan reminder:test --hours=168 --email=your@email.com --dry-run
```

## 🔧 **Command Options Explained**

| Option | Description | Example |
|--------|-------------|---------|
| `--hours=X` | Look back X hours instead of 72 | `--hours=1` |
| `--email=your@email.com` | Send test email to your address | `--email=mohamed@example.com` |
| `--dry-run` | Validate logic without sending emails | `--dry-run` |
| `--create-test-data` | Create sample patient status data | `--create-test-data` |

## 🎯 **Step-by-Step Testing Process**

### **Step 1: Dry-Run Test**
```bash
# First, test the logic without sending emails
php artisan reminder:test --hours=1 --create-test-data --dry-run
```

**Expected Output:**
```
🧪 Starting REMINDER EMAIL TESTING
⏰ Looking back: 1 hours (instead of 72)
🔍 DRY RUN MODE: No emails will be sent
🔧 Creating test patient status data...
✅ Created test patient ID: 123
✅ Created submit_status record:
   👨‍⚕️ Doctor ID: 1 (Dr. Test)
   🏥 Patient ID: 123
   👤 Patient Name: Test Patient 123 (from answers table, question_id=1)
   ⏰ Created: 2025-09-19 21:00:00 (2 hours ago)
   📝 Key: submit_status, Status: true
📊 Found 1 patient(s) needing outcome reminders
✅ Reminder processed for Patient ID: 123, Doctor ID: 1
📧 Total processed: 1
🔍 This was a DRY RUN - no actual emails were sent
```

### **Step 2: Send Test Email to Your Address**
```bash
# Send actual reminder email to your email address
php artisan reminder:test --hours=1 --email=your@email.com
```

**Expected Output:**
```
🧪 Starting REMINDER EMAIL TESTING
⏰ Looking back: 1 hours (instead of 72)
📧 Test email: your@email.com
📊 Found 1 patient(s) needing outcome reminders
✅ Reminder processed for Patient ID: 123, Doctor ID: 1
   📧 Email: your@email.com
   ⏰ Hours since submit: 2
📧 Total processed: 1
```

### **Step 3: Test with Different Time Thresholds**
```bash
# Test with 5 minutes (very recent data)
php artisan reminder:test --hours=0.1 --email=your@email.com --dry-run

# Test with 6 hours
php artisan reminder:test --hours=6 --email=your@email.com --dry-run

# Test with 24 hours
php artisan reminder:test --hours=24 --email=your@email.com --dry-run
```

## 🗄️ **Manual Database Testing**

If you want to manually create test data in your database:

### **1. Create Submit Status Record**
```sql
INSERT INTO patient_statuses (doctor_id, patient_id, key, status, created_at, updated_at)
VALUES (1, 1, 'submit_status', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 2 HOUR));
```

### **2. Test the Command**
```bash
php artisan reminder:test --hours=1 --email=your@email.com --dry-run
```

### **3. Add Outcome Status (Should Skip Reminder)**
```sql
INSERT INTO patient_statuses (doctor_id, patient_id, key, status, created_at, updated_at)
VALUES (1, 1, 'outcome_status', 1, NOW(), NOW());
```

### **4. Test Again (Should Skip)**
```bash
php artisan reminder:test --hours=1 --email=your@email.com --dry-run
```

## 📧 **Email Testing Scenarios**

### **Scenario 1: Patient Needs Reminder**
- ✅ Has `submit_status = true` older than threshold
- ❌ No `outcome_status = true` record
- ✅ **Result: Reminder sent**

### **Scenario 2: Patient Already Has Outcome**
- ✅ Has `submit_status = true` older than threshold
- ✅ Has `outcome_status = true` record
- ❌ **Result: Reminder skipped**

### **Scenario 3: Recent Submit (Within Threshold)**
- ✅ Has `submit_status = true` newer than threshold
- ❌ No `outcome_status = true` record
- ❌ **Result: Reminder skipped (too recent)**

### **Scenario 4: Already Sent Reminder Recently**
- ✅ Has `submit_status = true` older than threshold
- ❌ No `outcome_status = true` record
- ✅ Has `outcome_reminder_sent = true` within 7 days
- ❌ **Result: Reminder skipped (already sent)**

## 🔍 **Debugging Commands**

### **Check Patient Statuses**
```bash
# View all patient statuses
php artisan tinker
>>> App\Modules\Patients\Models\PatientStatus::all();

# View specific patient's statuses
>>> App\Modules\Patients\Models\PatientStatus::where('patient_id', 1)->get();

# Count submit_status records
>>> App\Modules\Patients\Models\PatientStatus::where('key', 'submit_status')->where('status', true)->count();
```

### **Check Logs**
```bash
# View reminder logs
tail -f storage/logs/laravel.log | grep "Reminder"

# View test logs
tail -f storage/logs/laravel.log | grep "TEST:"
```

## ⚡ **Quick Testing Workflow**

### **For Immediate Testing:**
```bash
# 1. Create test data and dry-run
php artisan reminder:test --hours=1 --create-test-data --dry-run

# 2. Send actual test email to yourself
php artisan reminder:test --hours=1 --email=your@email.com

# 3. Verify email received and check logs
tail -f storage/logs/laravel.log | grep "TEST:"
```

### **For Production Validation:**
```bash
# 1. Test with production threshold but your email
php artisan reminder:test --hours=72 --email=your@email.com --dry-run

# 2. Test actual production command (dry-run)
php artisan reminder:send --dry-run

# 3. Run production command when ready
php artisan reminder:send
```

## 🚨 **Important Notes**

1. **Test Email Override**: Using `--email=your@email.com` sends to your email instead of the doctor's email
2. **No Tracking in Test Mode**: When using `--email`, tracking records aren't created, allowing repeated testing
3. **Dry-Run Safety**: Always test with `--dry-run` first to validate logic
4. **Time Flexibility**: Use `--hours` to test with any time threshold (0.1 = 6 minutes, 1 = 1 hour, 72 = 3 days)
5. **Data Creation**: `--create-test-data` creates realistic test data for immediate testing

## 🎉 **Ready to Test!**

You can now test the reminder system immediately without waiting 72 hours. Start with dry-run tests, then send actual emails to your address to verify everything works correctly!
