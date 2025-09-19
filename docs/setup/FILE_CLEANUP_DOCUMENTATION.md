# File Cleanup System Documentation

## Overview

This file cleanup system provides comprehensive management of file storage in your Laravel application. It automatically removes orphaned files when database records are deleted and provides scheduled cleanup for files that may have been missed.

## Features

- ✅ **Automatic file deletion** when models are deleted
- ✅ **Scheduled cleanup** of orphaned files
- ✅ **Multiple file type support** (JSON arrays, strings, comma-separated)
- ✅ **Configurable retention periods** for safety
- ✅ **Comprehensive logging** for audit trails
- ✅ **Production-safe** with multiple safety measures
- ✅ **Memory efficient** batch processing for large datasets
- ✅ **Dry-run mode** for testing
- ✅ **File backup** before deletion (optional)

## Installation

### 1. Register the Service Provider

Add the service provider to your `config/app.php`:

```php
'providers' => [
    // ... other providers
    App\Providers\FileCleanupServiceProvider::class,
],
```

### 2. Add Environment Variables

Copy the variables from `env-cleanup-example.txt` to your `.env` file and adjust as needed.

### 3. Create Log Directory

Ensure the logs directory exists and is writable:

```bash
mkdir -p storage/logs
chmod 755 storage/logs
```

## Configuration

All configuration is done through the `config/filesystems.php` file under the `cleanup` section.

### Key Configuration Options

```php
'cleanup' => [
    'enabled' => true,                    // Enable/disable the system
    'auto_cleanup_on_delete' => true,    // Auto-delete files on model deletion
    'retention_days' => 7,                // Safety period before deletion
    'schedule' => [
        'enabled' => true,
        'frequency' => 'daily',           // daily, weekly, monthly
        'time' => '02:00',               // 24-hour format
    ],
    'patterns' => [
        'media_images' => [
            'model' => \App\Models\FeedPost::class,
            'column' => 'media_path',
            'type' => 'json_array',
        ],
        // Add more patterns as needed
    ],
]
```

## Usage

### Manual Cleanup Command

Run the cleanup command manually:

```bash
# Basic usage
php artisan files:cleanup

# Dry run (see what would be deleted without deleting)
php artisan files:cleanup --dry-run

# Specify disk and batch size
php artisan files:cleanup --disk=public --batch-size=50

# Force run without confirmation
php artisan files:cleanup --force

# Clean specific disk
php artisan files:cleanup --disk=s3
```

### Scheduled Cleanup

The system automatically schedules cleanup based on your configuration. The scheduler runs:

- **Daily** at 2:00 AM by default
- **Weekly** on Sundays at the specified time
- **Monthly** on the 1st at the specified time

### Automatic Model Cleanup

When you delete models, associated files are automatically removed:

```php
// This will automatically delete associated media files
$post = FeedPost::find(1);
$post->delete();

// This will also clean up old files when updating
$post->media_path = ['new_image.jpg'];
$post->save(); // Old images are automatically deleted
```

## Adding New Models

To add cleanup support for new models:

### 1. Create an Observer

```php
<?php

namespace App\Observers;

use App\Models\YourModel;
use App\Services\FileCleanupService;

class YourModelObserver
{
    protected FileCleanupService $fileCleanupService;

    public function __construct(FileCleanupService $fileCleanupService)
    {
        $this->fileCleanupService = $fileCleanupService;
    }

    public function deleting(YourModel $model): void
    {
        $fileColumns = [
            'file_column' => [
                'type' => 'string', // or 'json_array' or 'comma_separated'
                'disk' => 'public'
            ],
        ];

        $this->fileCleanupService->deleteModelFiles($model, $fileColumns);
    }
}
```

### 2. Register the Observer

In `FileCleanupServiceProvider::boot()`:

```php
YourModel::observe(YourModelObserver::class);
```

### 3. Add to Configuration

In `config/filesystems.php`:

```php
'patterns' => [
    'your_directory' => [
        'model' => \App\Models\YourModel::class,
        'column' => 'file_column',
        'type' => 'string',
        'enabled' => true,
    ],
],
```

## File Type Support

### JSON Array (media_path)
```php
'type' => 'json_array'
// Handles: ["file1.jpg", "file2.jpg"]
```

### String (single file)
```php
'type' => 'string'
// Handles: "profile.jpg"
```

### Comma Separated
```php
'type' => 'comma_separated'
// Handles: "file1.jpg,file2.jpg,file3.jpg"
```

## Safety Features

### 1. File Age Check
Files must be older than the retention period (default: 7 days) before deletion.

### 2. Exclude Patterns
System files and important files are automatically excluded:
- `default_profile.png`
- `logo.*`
- `.gitkeep`
- `.DS_Store`

### 3. Backup Before Delete (Optional)
Enable backup before deletion:
```php
'safety' => [
    'backup_before_delete' => true,
    'backup_disk' => 'local',
],
```

### 4. Confirmation Required
By default, manual cleanup requires confirmation unless `--force` is used.

### 5. Max Files Per Run
Limit the number of files processed in a single run to prevent system overload.

## Monitoring and Logging

### Log Files

- **Main log**: `storage/logs/file_cleanup.log`
- **Scheduled log**: `storage/logs/scheduled_cleanup.log`
- **Laravel log**: `storage/logs/laravel.log`

### Log Levels

- `INFO`: Successful operations
- `WARNING`: Non-critical issues
- `ERROR`: Failed operations

### Sample Log Entry

```json
{
    "timestamp": "2024-01-15T02:00:00.000Z",
    "dry_run": false,
    "deleted_count": 15,
    "skipped_count": 142,
    "error_count": 0,
    "deleted_files": [
        "media_images/old_image_1.jpg",
        "media_images/old_image_2.jpg"
    ]
}
```

## Performance Considerations

### Memory Usage
- Uses batch processing to handle large datasets
- Configurable batch sizes (default: 100 files)
- Minimal memory footprint per batch

### Database Queries
- Optimized queries with specific column selection
- Bulk operations where possible
- Efficient relationship loading

### File System
- Respects file system limits
- Handles network storage (S3, etc.)
- Graceful error handling

## Troubleshooting

### Common Issues

1. **Files not being deleted**
   - Check if cleanup is enabled in config
   - Verify file paths are correct
   - Check file age vs retention period
   - Review exclude patterns

2. **Permission errors**
   - Ensure Laravel has write permissions
   - Check storage disk configuration
   - Verify file ownership

3. **Memory issues with large datasets**
   - Reduce batch size
   - Increase PHP memory limit
   - Run during off-peak hours

### Debug Mode

Enable debug logging:
```bash
FILE_CLEANUP_LOG_LEVEL=debug
```

Run with dry-run to see what would be processed:
```bash
php artisan files:cleanup --dry-run
```

## Best Practices

1. **Always test with dry-run first**
2. **Start with small batch sizes**
3. **Monitor logs regularly**
4. **Set appropriate retention periods**
5. **Use backups for critical files**
6. **Run during low-traffic periods**
7. **Keep exclude patterns updated**

## Security Considerations

- Files are only deleted if they match specific patterns
- Exclude patterns prevent deletion of system files
- Retention period provides safety buffer
- Comprehensive logging for audit trails
- No external file access outside configured directories

## Support

For issues or questions:
1. Check the logs for detailed error messages
2. Verify configuration settings
3. Test with dry-run mode
4. Review file permissions and paths

