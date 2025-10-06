#!/bin/bash

# ================================================
# Filament 403 Fix - Production Environment Update
# ================================================

echo "================================================"
echo "Fixing Filament 403 Error on Production"
echo "================================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Check if in correct directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Are you in the correct directory?${NC}"
    echo "Please cd to your Laravel project root (~/public_html/api.egyakin.com)"
    exit 1
fi

echo -e "${YELLOW}Step 1: Checking current .env configuration...${NC}"
if grep -q "SESSION_DOMAIN" .env; then
    echo -e "  ${GREEN}✓ SESSION_DOMAIN already exists${NC}"
else
    echo -e "  ${RED}✗ SESSION_DOMAIN is missing${NC}"
fi

if grep -q "SESSION_SECURE_COOKIE" .env; then
    echo -e "  ${GREEN}✓ SESSION_SECURE_COOKIE already exists${NC}"
else
    echo -e "  ${RED}✗ SESSION_SECURE_COOKIE is missing${NC}"
fi

if grep -q "SANCTUM_STATEFUL_DOMAINS" .env; then
    echo -e "  ${GREEN}✓ SANCTUM_STATEFUL_DOMAINS already exists${NC}"
else
    echo -e "  ${RED}✗ SANCTUM_STATEFUL_DOMAINS is missing${NC}"
fi

echo ""
echo -e "${YELLOW}Step 2: Creating backup of .env file...${NC}"
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo -e "${GREEN}✓ Backup created${NC}"

echo ""
echo -e "${YELLOW}Step 3: Adding missing configuration...${NC}"

# Check if SESSION_DOMAIN exists, if not add it
if ! grep -q "SESSION_DOMAIN" .env; then
    # Find the line with SESSION_LIFETIME and add after it
    if grep -q "SESSION_LIFETIME" .env; then
        # Use sed to add after SESSION_LIFETIME line
        sed -i.tmp '/SESSION_LIFETIME/a\
\
# Session Configuration for Filament (Added to fix 403 error)\
SESSION_DOMAIN=.egyakin.com\
SESSION_SECURE_COOKIE=true\
SESSION_COOKIE=egyakin_session
' .env
        rm .env.tmp 2>/dev/null
        echo -e "${GREEN}✓ Added SESSION configuration${NC}"
    else
        # If SESSION_LIFETIME not found, append to end
        cat >> .env << 'EOF'

# Session Configuration for Filament (Added to fix 403 error)
SESSION_DOMAIN=.egyakin.com
SESSION_SECURE_COOKIE=true
SESSION_COOKIE=egyakin_session
EOF
        echo -e "${GREEN}✓ Added SESSION configuration at end${NC}"
    fi
fi

# Add SANCTUM_STATEFUL_DOMAINS if not exists
if ! grep -q "SANCTUM_STATEFUL_DOMAINS" .env; then
    cat >> .env << 'EOF'

# Sanctum Configuration for Filament
SANCTUM_STATEFUL_DOMAINS=api.egyakin.com,egyakin.com,www.egyakin.com
EOF
    echo -e "${GREEN}✓ Added SANCTUM configuration${NC}"
fi

echo ""
echo -e "${YELLOW}Step 4: Clearing all caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ All caches cleared${NC}"

echo ""
echo -e "${YELLOW}Step 5: Optimizing for production...${NC}"
php artisan config:cache
echo -e "${GREEN}✓ Configuration cached${NC}"

echo ""
echo -e "${YELLOW}Step 6: Verifying storage permissions...${NC}"
if [ -d "storage/framework/sessions" ]; then
    chmod -R 775 storage/framework/sessions 2>/dev/null
    echo -e "${GREEN}✓ Session directory permissions updated${NC}"
else
    echo -e "${YELLOW}! Session directory not found, may need to be created${NC}"
fi

echo ""
echo "================================================"
echo -e "${GREEN}✓ Fix Applied Successfully!${NC}"
echo "================================================"
echo ""
echo "Next Steps:"
echo "1. Visit https://api.egyakin.com/admin"
echo "2. You should now see the login page (not 403)"
echo "3. Login with your credentials"
echo ""
echo "If you still see 403, check:"
echo "- storage/logs/laravel.log for errors"
echo "- Verify your .env changes: cat .env | grep SESSION"
echo ""
echo "Backup saved to: .env.backup.$(date +%Y%m%d)_*"
echo ""

