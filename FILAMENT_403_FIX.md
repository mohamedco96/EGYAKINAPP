# Filament 403 Forbidden Error Fix - Production Environment

## Problem
When `APP_ENV=production`, Filament admin panel returns 403 Forbidden error at https://api.egyakin.com/admin

## Root Cause
Session/cookie configuration issues combined with proxy trust settings in production environment.

## Solution Steps

### Step 1: Update Your .env File

Add or update these lines in your production `.env` file:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.egyakin.com

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_DOMAIN=.egyakin.com
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=egyakin_session

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=api.egyakin.com,egyakin.com,www.egyakin.com
```

**Important Notes:**
- `SESSION_DOMAIN` should start with a dot (`.egyakin.com`) to work across subdomains
- `SESSION_SECURE_COOKIE=true` requires HTTPS (which you have)
- Add all your domains to `SANCTUM_STATEFUL_DOMAINS`

### Step 2: Update TrustProxies Middleware

The TrustProxies middleware needs to trust your production proxies. Update the file:

**File**: `app/Http/Middleware/TrustProxies.php`

Change from:
```php
protected $proxies;
```

To:
```php
protected $proxies = '*'; // Trust all proxies
```

Or for better security, specify your actual proxy IPs:
```php
protected $proxies = [
    '192.168.1.1', // Your load balancer IP
    // Add other proxy IPs
];
```

### Step 3: Clear All Caches on Production Server

Run these commands on your production server:

```bash
# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optional: Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 4: Verify Session Directory Permissions

Ensure the session storage directory is writable:

```bash
chmod -R 775 storage/framework/sessions
chown -R www-data:www-data storage/framework/sessions
```

(Replace `www-data` with your web server user if different)

### Step 5: Check Nginx/Apache Configuration

If using Nginx, ensure you're properly forwarding headers:

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
    
    # Forward proxy headers
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;
}
```

## Alternative Quick Fix (If Above Doesn't Work)

If you need immediate access, temporarily set in `.env`:

```env
APP_ENV=production
APP_DEBUG=false
SESSION_SECURE_COOKIE=false  # Temporary - less secure
SESSION_DOMAIN=null
```

Then clear caches. This is **NOT recommended for production** long-term but will help diagnose the issue.

## Debugging Steps

### 1. Check Laravel Logs
```bash
tail -f storage/logs/laravel.log
```

### 2. Enable Temporary Debug Mode
Temporarily set `APP_DEBUG=true` in `.env` to see detailed error messages, then revert back.

### 3. Test Session Working
Create a test route to verify sessions work:

```php
// routes/web.php
Route::get('/session-test', function() {
    session(['test' => 'working']);
    return session('test');
});
```

Visit `https://api.egyakin.com/session-test` - should return "working"

### 4. Check Cookie in Browser
1. Open browser DevTools (F12)
2. Go to Application > Cookies
3. Try logging into Filament
4. Check if `egyakin_session` cookie is being set
5. Verify cookie has `Secure` flag if using HTTPS

## Common Pitfalls

1. ❌ **Cached config** - Always clear config cache after changes
2. ❌ **Wrong domain** - SESSION_DOMAIN must match your actual domain
3. ❌ **File permissions** - Session files must be writable
4. ❌ **Proxy not trusted** - TrustProxies must be configured
5. ❌ **HTTPS mismatch** - If using HTTPS, SESSION_SECURE_COOKIE should be true

## Verification

After implementing the fix:

1. ✅ Visit https://api.egyakin.com/admin
2. ✅ Should see login page (not 403)
3. ✅ Login should work and create session
4. ✅ Dashboard should load after login

## Additional Notes

- Your `User::canAccessPanel()` method currently returns `true` for all users, which is fine for testing
- Consider reverting to proper access control after fixing:
  ```php
  public function canAccessPanel(Panel $panel): bool
  {
      return $this->hasRole(['Admin', 'Tester']) ||
             str_ends_with($this->email, '@egyakin.com') ||
             in_array($this->email, [
                 'mohamedco215@gmail.com',
                 'Darsh1980@mans.edu.eg',
                 'aboelkhaer@yandex.com',
             ]);
  }
  ```

## Still Having Issues?

If the problem persists:

1. Check `storage/logs/laravel.log` for specific errors
2. Verify your SSL certificate is valid
3. Check if your server has any IP restrictions
4. Verify firewall rules allow access to admin panel
5. Check if there are any nginx/apache access restrictions

## Contact
For additional help, check the Laravel and Filament documentation:
- https://laravel.com/docs/10.x/session
- https://filamentphp.com/docs/3.x/panels/users

