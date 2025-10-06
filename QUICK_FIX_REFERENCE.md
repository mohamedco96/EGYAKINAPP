# 🚨 Quick Fix Reference - Filament 403 Error

## Problem
Filament returns **403 Forbidden** when `APP_ENV=production`

## ⚡ Quick Fix (90% of cases)

### 1️⃣ SSH into production and run:
```bash
cd ~/public_html/api.egyakin.com

# Add missing config
cat >> .env << 'EOF'

# Session Configuration for Filament (Added to fix 403 error)
SESSION_DOMAIN=.egyakin.com
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=egyakin_session

# Sanctum Configuration for Filament
SANCTUM_STATEFUL_DOMAINS=api.egyakin.com,egyakin.com,www.egyakin.com
EOF
```

### 2️⃣ Clear caches:
```bash
php artisan config:clear && php artisan cache:clear && php artisan config:cache
```

### 3️⃣ Done! Try accessing the admin panel at https://api.egyakin.com/admin

---

## 📋 Detailed Checklist

- [ ] ✅ **TrustProxies** - Already fixed in code (`$proxies = '*'`)
- [ ] ✅ **SESSION_DOMAIN** - Set to `.egyakin.com` in `.env`
- [ ] ✅ **SESSION_SECURE_COOKIE** - Set to `true` in `.env`
- [ ] ✅ **SANCTUM_STATEFUL_DOMAINS** - Include all domains in `.env`
- [ ] ✅ **Clear caches** - Run `php artisan config:clear`
- [ ] ✅ **Permissions** - Check `storage/framework/sessions` is writable

---

## 🔧 Files Modified

| File | Change | Status |
|------|--------|--------|
| `app/Http/Middleware/TrustProxies.php` | Set `$proxies = '*'` | ✅ Done |
| `.env` (production) | Add session/sanctum config | ⚠️ Manual |

---

## 📝 Required .env Variables

```env
# Must Have (for Filament to work)
APP_URL=https://api.egyakin.com
SESSION_DOMAIN=.egyakin.com
SESSION_SECURE_COOKIE=true
SANCTUM_STATEFUL_DOMAINS=api.egyakin.com,egyakin.com

# Optional but Recommended
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE=egyakin_session
```

---

## 🐛 Debugging Commands

```bash
# View recent errors
tail -50 storage/logs/laravel.log

# Test session
php artisan tinker --execute="session(['test' => 'ok']); echo session('test');"

# Check permissions
ls -la storage/framework/sessions

# Clear everything
php artisan config:clear && php artisan cache:clear && php artisan route:clear
```

---

## 🚀 Run the Fix Script

```bash
# Make executable (if not already)
chmod +x fix-filament-403.sh

# Run the diagnostic and fix script
./fix-filament-403.sh
```

---

## 📚 Additional Resources

- **Detailed Guide**: `FILAMENT_403_FIX.md`
- **Environment Template**: `env-production-template.txt`
- **Fix Script**: `fix-filament-403.sh`

---

## 🎯 Expected Result

Before Fix:
```
❌ https://api.egyakin.com/admin → 403 Forbidden
```

After Fix:
```
✅ https://api.egyakin.com/admin → Login Page
✅ Login works → Dashboard loads
```

---

## ⚠️ Common Mistakes

1. **Forgetting to clear cache** - Always run `php artisan config:clear`
2. **Wrong domain format** - Use `.egyakin.com` (with dot) not `egyakin.com`
3. **Missing HTTPS** - `SESSION_SECURE_COOKIE=true` requires HTTPS
4. **Typos in .env** - Double-check variable names
5. **File permissions** - Ensure `storage/` is writable by web server

---

## 🆘 Still Not Working?

1. Check if you're using a load balancer/proxy
2. Verify SSL certificate is valid
3. Check nginx/apache configuration
4. Look for IP restrictions
5. Review `storage/logs/laravel.log`

---

## 📞 Quick Commands Cheatsheet

```bash
# Clear all caches
php artisan optimize:clear

# Re-cache for production
php artisan config:cache
php artisan route:cache

# Fix permissions
chmod -R 775 storage
chown -R www-data:www-data storage

# View config
php artisan config:show session

# Test
curl -I https://api.egyakin.com/admin
```

---

**Last Updated**: October 2025
**Priority**: 🔥 Critical - Production Issue

