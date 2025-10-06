#!/bin/bash

# Filament 403 Error Fix Script for Production
# This script helps diagnose and fix the Filament 403 error in production

echo "================================================"
echo "Filament 403 Error Fix Script"
echo "================================================"
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then 
    echo -e "${YELLOW}Note: You may need sudo privileges for some operations${NC}"
    echo ""
fi

# Step 1: Check current environment
echo -e "${YELLOW}Step 1: Checking current configuration...${NC}"
echo "Current APP_ENV: $(grep APP_ENV .env | cut -d '=' -f2)"
echo "Current APP_URL: $(grep APP_URL .env | cut -d '=' -f2)"
echo ""

# Step 2: Check required .env variables
echo -e "${YELLOW}Step 2: Checking required .env variables...${NC}"

check_env_var() {
    if grep -q "^$1=" .env; then
        echo -e "  ✓ $1 is set"
    else
        echo -e "  ${RED}✗ $1 is NOT set${NC}"
        return 1
    fi
}

check_env_var "APP_URL"
check_env_var "SESSION_DOMAIN"
check_env_var "SESSION_SECURE_COOKIE"
check_env_var "SANCTUM_STATEFUL_DOMAINS"
echo ""

# Step 3: Check session directory permissions
echo -e "${YELLOW}Step 3: Checking session directory permissions...${NC}"
if [ -d "storage/framework/sessions" ]; then
    PERMS=$(stat -c "%a" storage/framework/sessions 2>/dev/null || stat -f "%Lp" storage/framework/sessions)
    echo "  Session directory permissions: $PERMS"
    if [ -w "storage/framework/sessions" ]; then
        echo -e "  ${GREEN}✓ Session directory is writable${NC}"
    else
        echo -e "  ${RED}✗ Session directory is NOT writable${NC}"
        echo "  Run: chmod -R 775 storage/framework/sessions"
    fi
else
    echo -e "  ${RED}✗ Session directory does not exist${NC}"
    echo "  Creating session directory..."
    mkdir -p storage/framework/sessions
    chmod -R 775 storage/framework/sessions
fi
echo ""

# Step 4: Clear all caches
echo -e "${YELLOW}Step 4: Clearing all caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ All caches cleared${NC}"
echo ""

# Step 5: Check for config cache
echo -e "${YELLOW}Step 5: Checking for cached config...${NC}"
if [ -f "bootstrap/cache/config.php" ]; then
    echo -e "  ${YELLOW}! Cached config found${NC}"
    echo "  This could cause issues if not regenerated"
else
    echo -e "  ${GREEN}✓ No cached config found${NC}"
fi
echo ""

# Step 6: Optimize for production (optional)
read -p "Do you want to optimize for production? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}Optimizing for production...${NC}"
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    echo -e "${GREEN}✓ Optimization complete${NC}"
fi
echo ""

# Step 7: Check storage permissions
echo -e "${YELLOW}Step 7: Checking storage permissions...${NC}"
for dir in "storage/framework/sessions" "storage/framework/cache" "storage/framework/views" "storage/logs"; do
    if [ -d "$dir" ]; then
        if [ -w "$dir" ]; then
            echo -e "  ${GREEN}✓ $dir is writable${NC}"
        else
            echo -e "  ${RED}✗ $dir is NOT writable${NC}"
        fi
    else
        echo -e "  ${YELLOW}! $dir does not exist${NC}"
    fi
done
echo ""

# Step 8: Test session functionality
echo -e "${YELLOW}Step 8: Testing session functionality...${NC}"
php artisan tinker --execute="session(['test' => 'working']); echo session('test');"
echo ""

# Step 9: Check recent logs
echo -e "${YELLOW}Step 9: Checking recent error logs...${NC}"
if [ -f "storage/logs/laravel.log" ]; then
    echo "Last 10 error entries:"
    grep -i "error\|exception\|403\|forbidden" storage/logs/laravel.log | tail -10
else
    echo -e "  ${YELLOW}No log file found${NC}"
fi
echo ""

# Summary
echo "================================================"
echo -e "${GREEN}Fix Script Complete!${NC}"
echo "================================================"
echo ""
echo "Next Steps:"
echo "1. Review the checks above for any issues"
echo "2. Update your .env file with required variables (see FILAMENT_403_FIX.md)"
echo "3. Try accessing your Filament admin panel"
echo ""
echo "If the issue persists:"
echo "- Check storage/logs/laravel.log for detailed errors"
echo "- Verify your .env SESSION_DOMAIN matches your domain"
echo "- Ensure SESSION_SECURE_COOKIE=true if using HTTPS"
echo "- Review FILAMENT_403_FIX.md for detailed instructions"
echo ""
echo "Test your admin panel at: $(grep APP_URL .env | cut -d '=' -f2)/admin"
echo ""

