#!/bin/bash

# ================================================
# Fix for "403 After Login" Issue
# ================================================

echo "================================================"
echo "Fixing Filament 403 After Login Error"
echo "================================================"
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check if in correct directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Run this from your Laravel root${NC}"
    exit 1
fi

echo -e "${YELLOW}Creating backup of .env...${NC}"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Backup created${NC}"
echo ""

echo -e "${YELLOW}Adding/updating configuration for 403-after-login fix...${NC}"

# Remove old entries if they exist (to avoid duplicates)
sed -i.tmp '/^SESSION_DOMAIN=/d' .env
sed -i.tmp '/^SESSION_SECURE_COOKIE=/d' .env
sed -i.tmp '/^SESSION_COOKIE=/d' .env
sed -i.tmp '/^SANCTUM_STATEFUL_DOMAINS=/d' .env
rm .env.tmp 2>/dev/null

# Add the complete configuration
cat >> .env << 'EOF'

# ================================================
# Session Configuration for Filament
# CRITICAL: Required to fix 403 after login
# ================================================
SESSION_DOMAIN=.egyakin.com
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=egyakin_session

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=api.egyakin.com,egyakin.com,www.egyakin.com
EOF

echo -e "${GREEN}✓ Configuration added${NC}"
echo ""

echo -e "${YELLOW}Clearing ALL caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear 2>/dev/null
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

echo -e "${YELLOW}Checking storage permissions...${NC}"
chmod -R 775 storage/framework/sessions 2>/dev/null
chmod -R 775 storage/framework/cache 2>/dev/null
echo -e "${GREEN}✓ Permissions updated${NC}"
echo ""

echo -e "${YELLOW}Caching configuration for production...${NC}"
php artisan config:cache
echo -e "${GREEN}✓ Configuration cached${NC}"
echo ""

echo "================================================"
echo -e "${GREEN}✓ Fix Applied!${NC}"
echo "================================================"
echo ""
echo "IMPORTANT: Test your login now:"
echo "1. Visit https://api.egyakin.com/admin"
echo "2. Enter your credentials"
echo "3. Click Login"
echo "4. You should now be redirected to the dashboard!"
echo ""
echo "If still getting 403, check:"
echo "  tail -50 storage/logs/laravel.log"
echo ""

