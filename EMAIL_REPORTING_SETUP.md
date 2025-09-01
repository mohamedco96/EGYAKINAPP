# EGYAKIN Email Reporting System

This documentation explains how to set up and use the automated email reporting system for EGYAKIN.

## 🚀 Features

- **Daily Reports**: Comprehensive daily statistics sent every morning at 8:00 AM
- **Weekly Summaries**: Detailed weekly analytics sent every Monday at 9:00 AM
- **Professional Email Design**: Responsive HTML templates with modern styling
- **Error Handling**: Comprehensive logging and error notifications
- **Queue Support**: Reports are sent via queue for better performance

## 📧 What's Included in Reports

### Daily Report
- **User Statistics**: New registrations, total users, verified users, active users
- **Patient Management**: New patients, total patients, archived patients
- **Consultations**: New consultations, pending, completed, and open consultations
- **Community Activity**: New posts, media posts, groups activity
- **System Health**: Basic system status and metrics

### Weekly Summary
- **Performance Overview**: Week-over-week comparison with growth percentages
- **Top Performers**: Most active doctors, popular posts, active groups
- **Engagement Insights**: User engagement rates, content performance metrics
- **Trends Analysis**: Consultation patterns, content performance trends
- **Platform Overview**: Total system statistics

## ⚙️ Configuration

### 1. Environment Variables

Add the following to your `.env` file:

```env
# Admin Email for Reports
ADMIN_EMAIL=your-admin@egyakin.com

# Mail Configuration (if not already configured)
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@egyakin.com
MAIL_FROM_NAME="EGYAKIN System"
```

### 2. Queue Configuration

For better performance, ensure your queue system is configured:

```env
QUEUE_CONNECTION=database
# or use redis, sqs, etc.
```

Run queue migrations if using database queues:
```bash
php artisan queue:table
php artisan migrate
```

## 🛠️ Installation & Setup

The email reporting system is already installed! Here's what was added:

### Files Created:
- `app/Mail/DailyReportMail.php` - Daily report mailable
- `app/Mail/WeeklySummaryMail.php` - Weekly summary mailable
- `app/Console/Commands/SendDailyReport.php` - Daily report command
- `app/Console/Commands/SendWeeklySummary.php` - Weekly summary command
- `resources/views/emails/daily-report.blade.php` - Daily report template
- `resources/views/emails/weekly-summary.blade.php` - Weekly summary template

### Files Modified:
- `app/Console/Kernel.php` - Added scheduled jobs
- `config/mail.php` - Added admin email configuration

## 🕒 Scheduling

The reports are automatically scheduled in `app/Console/Kernel.php`:

- **Daily Report**: Every day at 8:00 AM
- **Weekly Summary**: Every Monday at 9:00 AM

### Running the Scheduler

#### For Development (Local Testing):
```bash
# Run the scheduler continuously
php artisan schedule:work

# Or run individual commands manually
php artisan reports:send-daily
php artisan reports:send-weekly

# Test with custom email
php artisan reports:send-daily --email=test@example.com
php artisan reports:send-weekly --email=test@example.com
```

#### For Production:
Add this cron job to your server:
```bash
# Add to crontab (crontab -e)
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

## 📊 Manual Commands

You can send reports manually using these commands:

```bash
# Send daily report
php artisan reports:send-daily

# Send weekly summary  
php artisan reports:send-weekly

# Send to specific email (override admin email)
php artisan reports:send-daily --email=custom@example.com
php artisan reports:send-weekly --email=custom@example.com
```

## 🔍 Monitoring & Logs

### Log Files
Reports create detailed logs in:
- `storage/logs/daily_reports.log` - Daily report execution logs
- `storage/logs/weekly_summaries.log` - Weekly summary execution logs
- `storage/logs/laravel.log` - General application logs

### Error Notifications
If a scheduled report fails:
- Error details are logged
- Admin receives email notification (if configured)
- Command returns proper exit codes

## 🎨 Customization

### Modifying Report Data
Edit the mailable classes to add/remove metrics:
- `app/Mail/DailyReportMail.php`
- `app/Mail/WeeklySummaryMail.php`

### Customizing Email Design
Modify the Blade templates:
- `resources/views/emails/daily-report.blade.php`
- `resources/views/emails/weekly-summary.blade.php`

### Changing Schedule Times
Edit `app/Console/Kernel.php` to modify when reports are sent:
```php
// Change daily report time
$schedule->command('reports:send-daily')->dailyAt('06:00');

// Change weekly summary day/time
$schedule->command('reports:send-weekly')->weeklyOn(0, '10:00'); // Sunday at 10:00 AM
```

## 🧪 Testing

### Test Email Templates
```bash
# Generate test report (won't send email)
php artisan tinker
>>> $report = new App\Mail\DailyReportMail();
>>> $report->render();

# Test with Mail::fake() in tests
Mail::fake();
Mail::to('admin@test.com')->send(new DailyReportMail());
Mail::assertSent(DailyReportMail::class);
```

### Preview in Browser
Add a route for testing (remove in production):
```php
// In routes/web.php
Route::get('/test-daily-report', function () {
    return new App\Mail\DailyReportMail();
});

Route::get('/test-weekly-summary', function () {
    return new App\Mail\WeeklySummaryMail();
});
```

## 🚨 Troubleshooting

### Common Issues

1. **"Admin email not configured"**
   - Add `ADMIN_EMAIL=your-email@domain.com` to `.env`
   - Clear config cache: `php artisan config:clear`

2. **Reports not sending automatically**
   - Ensure cron job is set up correctly
   - Check if scheduler is running: `php artisan schedule:list`
   - Verify queue workers are running

3. **Email styling issues**
   - Check CSS in email templates
   - Test with different email clients
   - Ensure images/assets are accessible

4. **Performance issues**
   - Enable queue system
   - Optimize database queries in mailable classes
   - Consider caching report data

### Debug Commands
```bash
# Check scheduled commands
php artisan schedule:list

# Test scheduler without waiting
php artisan schedule:test

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check queue status
php artisan queue:work --once
```

## 📞 Support

For issues or customization requests, contact the development team or check the Laravel documentation for mail and scheduling features.

---

**Generated for EGYAKIN Platform** - Email Reporting System v1.0
