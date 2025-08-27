#!/bin/bash

# OpenSID Status Check Agent
# This script checks the current installation state

set -e

# Configuration
OPENSID_ROOT="/home/mulyawansentosa/Documents/Production/Project/OpenSID/Flaz/OpenSID-Flaz"
DB_NAME="opensid"
DB_USER="root"
DB_PASSWORD=""
DB_HOST="localhost"
WEB_URL="http://opensid.local"
INSTALL_URL="${WEB_URL}/install"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Status functions
success() {
    echo -e "${GREEN}✓${NC} $1"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

error() {
    echo -e "${RED}✗${NC} $1"
}

info() {
    echo -e "${BLUE}ℹ${NC} $1"
}

# Check desa folder
check_desa_folder() {
    echo -n "Desa folder status: "
    if [ -d "desa" ]; then
        error "EXISTS (installation completed)"
        return 1
    else
        success "NOT FOUND (ready for fresh install)"
        return 0
    fi
}

# Check database tables
check_database() {
    echo -n "Database status: "
    
    if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "USE $DB_NAME;" 2>/dev/null; then
        error "DATABASE NOT ACCESSIBLE"
        return 1
    fi
    
    table_count=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -D"$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
    
    if [ "$table_count" -eq 0 ]; then
        success "EMPTY ($table_count tables - ready for install)"
        return 0
    else
        warning "HAS DATA ($table_count tables - installation exists)"
        return 1
    fi
}

# Check installer accessibility
check_installer() {
    echo -n "Installer accessibility: "
    
    if command -v curl >/dev/null 2>&1; then
        response=$(curl -s -o /dev/null -w "%{http_code}" "$INSTALL_URL" 2>/dev/null || echo "000")
        case "$response" in
            "200"|"302")
                success "ACCESSIBLE ($INSTALL_URL)"
                return 0
                ;;
            "404")
                warning "NOT FOUND (may need web server configuration)"
                return 1
                ;;
            *)
                warning "UNKNOWN RESPONSE ($response)"
                return 1
                ;;
        esac
    else
        info "CURL NOT AVAILABLE (cannot test - check manually: $INSTALL_URL)"
        return 1
    fi
}

# Check preserved items
check_preserved_items() {
    echo ""
    echo -e "${BLUE}Bug fixes preservation status:${NC}"
    
    # Check migration fixes
    if [ -f "donjo-app/models/migrations/Migrasi_2024060171.php" ]; then
        success "Migration fixes preserved (Migrasi_2024060171.php found)"
    else
        warning "Migration fixes may be missing"
    fi
    
    # Check for common directories
    if [ -d "storage" ] && [ -d "storage/logs" ]; then
        success "Storage directories preserved"
    else
        warning "Storage directories may have issues"
    fi
    
    # Check for key framework files
    if [ -f "artisan" ] && [ -f "index.php" ]; then
        success "Core framework files preserved"
    else
        error "Core framework files missing"
    fi
}

# Check logs
check_logs() {
    echo -n "Recent logs: "
    
    log_count=0
    if [ -d "storage/logs" ]; then
        log_count=$(find storage/logs -name "*.log" -type f 2>/dev/null | wc -l)
    fi
    
    if [ "$log_count" -eq 0 ]; then
        success "CLEAN (no log files)"
    else
        info "$log_count log files exist"
    fi
}

# Main status check
main() {
    echo ""
    echo -e "${BLUE}╔══════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║            OpenSID Status Check             ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════╝${NC}"
    echo ""
    
    cd "$OPENSID_ROOT" || {
        echo -e "${RED}Error: Cannot change to OpenSID directory: $OPENSID_ROOT${NC}"
        exit 1
    }
    
    fresh_install_ready=true
    
    # Run all checks
    if ! check_desa_folder; then fresh_install_ready=false; fi
    if ! check_database; then fresh_install_ready=false; fi
    if ! check_installer; then fresh_install_ready=false; fi
    check_logs
    
    check_preserved_items
    
    echo ""
    echo -e "${BLUE}Overall Status:${NC}"
    
    if [ "$fresh_install_ready" = true ]; then
        echo -e "${GREEN}✓ READY FOR FRESH INSTALLATION${NC}"
        echo ""
        echo "To start fresh installation:"
        echo "  ./task fresh-install"
        echo ""
        echo "Then navigate to: $INSTALL_URL"
    else
        echo -e "${YELLOW}⚠ INSTALLATION EXISTS OR ISSUES DETECTED${NC}"
        echo ""
        echo "To reset for fresh installation:"
        echo "  ./task fresh-install"
        echo ""
        echo "This will clean up and prepare for fresh install."
    fi
    
    echo ""
}

# Run main function
main "$@"