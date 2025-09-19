# EGYAKIN Job Monitoring System

## Overview
Basic monitoring system for failed jobs and background tasks with automated alerts and logging.

## Features
- ✅ **Real-time Monitoring**: Track failed jobs, pending jobs, and failure rates
- ✅ **Critical Job Detection**: Special monitoring for email and notification jobs
- ✅ **Automated Alerts**: Email notifications to admin team for critical issues
- ✅ **Enhanced Logging**: Detailed job failure logs with context
- ✅ **Scheduled Monitoring**: Automatic checks every 15 minutes
- ✅ **Cleanup**: Automatic cleanup of old monitoring data

## Quick Start

### Basic Monitoring
```bash
# Check current job status
php artisan jobs:monitor

# Show detailed statistics
php artisan jobs:monitor --stats

# Send alerts if issues found
php artisan jobs:monitor --alert

# Clean up old data
php artisan jobs:monitor --cleanup
```

### Configuration
Set up admin emails in `.env`:
```env
ADMIN_MAIL_LIST=admin@egyakin.com,manager@egyakin.com
```

## Automated Monitoring
The system automatically runs:
- **Every 15 minutes**: Basic monitoring check
- **Every hour**: Alert check for critical issues
- **Daily at 3 AM**: Cleanup old monitoring data

## Alert Thresholds
- **High Failure Count**: >10 failed jobs
- **High Failure Rate**: >20% failure rate (24h)
- **Critical Failures**: Email/notification job failures (1h)
- **Queue Backup**: >100 pending jobs

## Log Files
- `storage/logs/job_monitoring.log` - Monitoring activities
- `storage/logs/laravel.log` - General application logs

## Critical Jobs Monitored
- Email notifications (`App\Mail\*`)
- System notifications (`App\Notifications\*`)
- Daily reports (`SendDailyReport`)
- Weekly summaries (`SendWeeklySummary`)
- Reminder emails (`SendReminderEmails`)

The system provides basic visibility into job health and automatically alerts administrators when issues require attention.
