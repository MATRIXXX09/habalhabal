#!/bin/bash
# Activity Logs Feature Verification Script
# This script checks that all components are properly installed

echo "🔍 Activity Logs Feature Verification"
echo "====================================="
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

ERRORS=0
WARNINGS=0

# Helper functions
check_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} $1"
        return 0
    else
        echo -e "${RED}✗${NC} $1 - MISSING"
        ((ERRORS++))
        return 1
    fi
}

check_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} $1/"
        return 0
    else
        echo -e "${RED}✗${NC} $1/ - MISSING"
        ((ERRORS++))
        return 1
    fi
}

check_contains() {
    if grep -q "$2" "$1" 2>/dev/null; then
        echo -e "${GREEN}✓${NC} $1 contains '$2'"
        return 0
    else
        echo -e "${YELLOW}⚠${NC} $1 missing '$2'"
        ((WARNINGS++))
        return 1
    fi
}

# Check PHP Files
echo "📁 Checking PHP Files..."
echo ""
check_file "src/Service/ActivityLogService.php"
check_file "src/Entity/ActivityLog.php"
check_file "src/Controller/Admin/ActivityLogController.php"
check_file "src/EventSubscriber/AuthenticationActivitySubscriber.php"
check_file "src/EventSubscriber/ActivityLogSubscriber.php"
echo ""

# Check Templates
echo "📄 Checking Templates..."
echo ""
check_file "templates/admin/activity_logs/index.html.twig"
check_file "templates/admin/activity_logs/detail.html.twig"
check_file "templates/admin/partials/_recent_activity.html.twig"
echo ""

# Check Documentation
echo "📚 Checking Documentation..."
echo ""
check_file "ACTIVITY_LOGS_IMPLEMENTATION.md"
check_file "ACTIVITY_LOGS_QUICK_START.md"
check_file "ACTIVITY_LOGS_SUMMARY.md"
echo ""

# Check Key Content
echo "🔑 Checking Key Implementation Details..."
echo ""
check_contains "src/Service/ActivityLogService.php" "logActivity"
check_contains "src/Controller/Admin/ActivityLogController.php" "ROLE_ADMIN"
check_contains "templates/admin/_sidebar.html.twig" "app_admin_activity_logs"
echo ""

# Database Check
echo "🗄️ Database Checks..."
echo ""
echo "Run these SQL commands to verify database setup:"
echo ""
echo "  -- Check if activity_log table exists"
echo "  SHOW TABLES LIKE 'activity_log';"
echo ""
echo "  -- Check table structure"
echo "  DESCRIBE activity_log;"
echo ""
echo "  -- Count existing logs"
echo "  SELECT COUNT(*) FROM activity_log;"
echo ""

# Summary
echo "====================================="
echo "✅ Verification Complete"
echo ""
if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}All required files present!${NC}"
else
    echo -e "${RED}$ERRORS missing files${NC}"
fi

if [ $WARNINGS -gt 0 ]; then
    echo -e "${YELLOW}$WARNINGS warnings${NC}"
fi

echo ""
echo "📋 Next Steps:"
echo "1. Run: php bin/console cache:clear"
echo "2. Check admin dashboard at /admin"
echo "3. Click 'Activity Logs' in sidebar"
echo "4. Test by creating/updating an entity"
echo "5. Verify logs appear in Activity Logs page"
echo ""
echo "📖 Read the documentation:"
echo "  - ACTIVITY_LOGS_QUICK_START.md (recommended first)"
echo "  - ACTIVITY_LOGS_IMPLEMENTATION.md (detailed reference)"
echo "  - ACTIVITY_LOGS_SUMMARY.md (executive overview)"
echo ""
