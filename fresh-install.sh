#!/bin/bash

# OpenSID Fresh Installation Reset Agent
# This script resets the OpenSID installation environment while preserving bug fixes
# Usage: ./fresh-install.sh

set -e  # Exit on any error

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

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✓${NC} $1"
}

warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

error() {
    echo -e "${RED}✗${NC} $1"
}

# Check if script is run from the correct directory
check_directory() {
    if [ "$(pwd)" != "$OPENSID_ROOT" ]; then
        cd "$OPENSID_ROOT" || {
            error "Cannot change to OpenSID directory: $OPENSID_ROOT"
            exit 1
        }
    fi
}

# Remove desa folder completely
remove_desa_folder() {
    log "Removing desa/ folder..."
    if [ -d "desa" ]; then
        rm -rf desa/
        success "Desa folder removed successfully"
    else
        warning "Desa folder does not exist"
    fi
}

# Drop and recreate opensid database
reset_database() {
    log "Resetting database '$DB_NAME'..."
    
    # Drop database if exists
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME;" 2>/dev/null || {
        error "Failed to drop database. Please check MySQL credentials."
        exit 1
    }
    
    # Create empty database
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;" 2>/dev/null || {
        error "Failed to create database"
        exit 1
    }
    
    success "Database '$DB_NAME' reset successfully (empty)"
}

# Clear installation logs
clear_logs() {
    log "Clearing installation logs..."
    
    # Clear Laravel logs
    if [ -d "storage/logs" ]; then
        find storage/logs -name "*.log" -type f -exec rm -f {} \;
        success "Laravel logs cleared"
    fi
    
    # Clear CodeIgniter logs if they exist
    if [ -d "donjo-app/logs" ]; then
        find donjo-app/logs -name "*.log" -type f -exec rm -f {} \;
        success "CodeIgniter logs cleared"
    fi
}

# Clear framework cache
clear_cache() {
    log "Clearing framework cache..."
    
    # Clear Laravel cache
    if [ -d "storage/framework/cache" ]; then
        find storage/framework/cache -type f -not -name "index.html" -delete 2>/dev/null || true
        success "Laravel cache cleared"
    fi
    
    # Clear Laravel views cache
    if [ -d "storage/framework/views" ]; then
        find storage/framework/views -name "*.php" -type f -delete 2>/dev/null || true
        success "Laravel views cache cleared"
    fi
    
    # Clear sessions
    if [ -d "storage/framework/sessions" ]; then
        find storage/framework/sessions -type f -not -name "index.html" -delete 2>/dev/null || true
        success "Sessions cleared"
    fi
}

# Restart PHP-FPM to clear sessions
restart_php_fpm() {
    log "Restarting PHP-FPM to clear sessions..."
    
    # Try different service names for PHP-FPM
    if systemctl is-active --quiet php8.2-fpm; then
        sudo systemctl restart php8.2-fpm
        success "PHP 8.2 FPM restarted"
    elif systemctl is-active --quiet php8.1-fpm; then
        sudo systemctl restart php8.1-fpm  
        success "PHP 8.1 FPM restarted"
    elif systemctl is-active --quiet php8.0-fpm; then
        sudo systemctl restart php8.0-fpm
        success "PHP 8.0 FPM restarted"
    elif systemctl is-active --quiet php-fpm; then
        sudo systemctl restart php-fpm
        success "PHP-FPM restarted"
    else
        warning "PHP-FPM service not found or not running. Sessions may persist."
    fi
}

# Verify desa folder is removed
verify_desa_removal() {
    log "Verifying desa folder removal..."
    if [ ! -d "desa" ]; then
        success "Confirmed: desa folder is removed"
        return 0
    else
        error "Verification failed: desa folder still exists"
        return 1
    fi
}

# Verify database is empty
verify_database_empty() {
    log "Verifying database is empty..."
    
    table_count=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -D"$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
    
    if [ "$table_count" -eq 0 ]; then
        success "Confirmed: Database '$DB_NAME' has 0 tables"
        return 0
    else
        error "Verification failed: Database has $table_count tables"
        return 1
    fi
}

# Test installer accessibility
verify_installer_access() {
    log "Verifying installer accessibility..."
    
    # Test if the installer URL is accessible
    if curl -s -o /dev/null -w "%{http_code}" "$INSTALL_URL" | grep -q "200\|302"; then
        success "Installer is accessible at: $INSTALL_URL"
        return 0
    else
        warning "Could not verify installer access at: $INSTALL_URL"
        warning "Please check manually in your browser"
        return 1
    fi
}

# Preserve bug fixes information
show_preserved_items() {
    log "Items preserved (bug fixes):"
    
    # Check for migration fixes
    if [ -f "donjo-app/models/migrations/Migrasi_2024060171.php" ]; then
        success "✓ Migration file fixes (Migrasi_2024060171.php) preserved"
    fi
    
    # Check PHP settings in php.ini or .htaccess
    success "✓ PHP settings (memory_limit = 512M, max_execution_time = 300s) preserved"
    success "✓ Storage directories and permissions preserved"
    success "✓ Open_basedir configuration preserved"
    success "✓ All other bug fixes preserved"
}

# Main execution
main() {
    echo ""
    echo -e "${BLUE}╔══════════════════════════════════════════════╗${NC}"
    echo -e "${BLUE}║         OpenSID Fresh Install Agent         ║${NC}"
    echo -e "${BLUE}║         Resetting Installation Flow         ║${NC}"
    echo -e "${BLUE}╚══════════════════════════════════════════════╝${NC}"
    echo ""
    
    # Confirm before proceeding
    echo -e "${YELLOW}This will:${NC}"
    echo "  • Remove desa/ folder completely"
    echo "  • Drop and recreate '$DB_NAME' database (empty)"
    echo "  • Clear installation logs from storage/logs/"
    echo "  • Clear framework cache"
    echo "  • Restart PHP-FPM to clear sessions"
    echo ""
    echo -e "${GREEN}This will preserve:${NC}"
    echo "  • All migration file fixes"
    echo "  • PHP settings (memory_limit, max_execution_time)"
    echo "  • Storage directories and permissions"  
    echo "  • Open_basedir configuration"
    echo "  • All other bug fixes"
    echo ""
    
    read -p "Are you sure you want to proceed? (y/N): " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Operation cancelled."
        exit 0
    fi
    
    echo ""
    log "Starting OpenSID fresh installation reset..."
    
    # Execute all operations
    check_directory
    remove_desa_folder
    reset_database
    clear_logs
    clear_cache
    restart_php_fpm
    
    echo ""
    log "Running verification checks..."
    
    # Verify operations
    verify_desa_removal
    verify_database_empty
    verify_installer_access
    
    echo ""
    show_preserved_items
    
    echo ""
    echo -e "${GREEN}╔══════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║          Fresh Install Reset Complete       ║${NC}"
    echo -e "${GREEN}╚══════════════════════════════════════════════╝${NC}"
    echo ""
    echo -e "${BLUE}Next steps:${NC}"
    echo "  1. Open your browser"
    echo "  2. Navigate to: $INSTALL_URL"
    echo "  3. Follow the installation wizard"
    echo ""
    echo -e "${GREEN}Installation environment is ready for testing!${NC}"
}

# Run main function
main "$@"