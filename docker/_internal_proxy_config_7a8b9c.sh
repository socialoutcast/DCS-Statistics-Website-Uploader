#!/bin/bash

# INTERNAL SCRIPT - DO NOT RUN DIRECTLY
# This script is automatically called by dcs-docker-manager.sh
# Running this script directly may cause unexpected behavior

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

print_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

# Check if .env exists
if [ ! -f "./docker/.env" ]; then
    echo "Error: ./docker/.env file not found!"
    echo "Run pre-flight checks first."
    exit 1
fi

echo ""
echo "======================================="
echo "Proxy Configuration"
echo "======================================="
echo ""
echo "Select your proxy setup:"
echo ""
echo "  1) Nginx Proxy Manager (Recommended)"
echo "     - Full reverse proxy with web UI"
echo "     - SSL certificate management"
echo "     - Access admin panel on port 81"
echo ""
echo "  2) Simple Nginx"
echo "     - Basic web server"
echo "     - No proxy management features"
echo "     - Good for local development"
echo ""
echo "  3) No Proxy"
echo "     - Skip proxy installation"
echo "     - For users with existing nginx/haproxy"
echo "     - You'll need to configure proxy manually"
echo ""
echo -n "Select option [1-3] (default: 1): "
read proxy_choice

case "$proxy_choice" in
    2)
        PROXY_TYPE="simple"
        ;;
    3)
        PROXY_TYPE="none"
        ;;
    *)
        PROXY_TYPE="nginx-proxy-manager"
        ;;
esac

# Update .env file
sed -i "s/^PROXY_TYPE=.*/PROXY_TYPE=$PROXY_TYPE/" "./docker/.env"
print_success "Set proxy type to: $PROXY_TYPE"

# Update docker-compose symlink
cd docker
rm -f docker-compose.yml

case "$PROXY_TYPE" in
    "simple")
        ln -s docker-compose-no-proxy.yml docker-compose.yml
        print_success "Configured for simple nginx (no proxy manager)"
        ;;
    "none")
        ln -s docker-compose-no-proxy.yml docker-compose.yml
        print_warning "No proxy will be installed - configure your own reverse proxy"
        ;;
    *)
        ln -s docker-compose-with-proxy.yml docker-compose.yml
        print_success "Configured for Nginx Proxy Manager"
        
        # Database configuration
        echo ""
        echo "======================================="
        echo "Database Configuration for Nginx Proxy Manager"
        echo "======================================="
        echo ""
        echo "By default, NPM uses a built-in SQLite database (recommended)."
        echo "You can optionally use an external MySQL/MariaDB database."
        echo ""
        echo "  1) Use built-in SQLite database (Recommended)"
        echo "  2) Use external MySQL/MariaDB database"
        echo ""
        echo -n "Select database option [1-2] (default: 1): "
        read db_choice
        
        if [[ "$db_choice" == "2" ]]; then
            echo ""
            print_info "Configure MySQL/MariaDB connection:"
            echo ""
            
            echo -n "Database Host (default: localhost): "
            read db_host
            db_host=${db_host:-localhost}
            
            echo -n "Database Port (default: 3306): "
            read db_port
            db_port=${db_port:-3306}
            
            echo -n "Database Name (default: npm): "
            read db_name
            db_name=${db_name:-npm}
            
            echo -n "Database User (default: npm): "
            read db_user
            db_user=${db_user:-npm}
            
            echo -n "Database Password: "
            read -s db_pass
            echo ""
            
            if [ -z "$db_pass" ]; then
                print_warning "No password set - this is insecure!"
            fi
            
            # Update .env
            cd ..
            cat >> "./docker/.env" <<EOF
NPM_DB_MYSQL_HOST=$db_host
NPM_DB_MYSQL_PORT=$db_port
NPM_DB_MYSQL_NAME=$db_name
NPM_DB_MYSQL_USER=$db_user
NPM_DB_MYSQL_PASSWORD=$db_pass
EOF
            print_success "MySQL/MariaDB configuration saved"
        else
            cd ..
            cat >> "./docker/.env" <<EOF
NPM_DB_MYSQL_HOST=
NPM_DB_MYSQL_PORT=3306
NPM_DB_MYSQL_NAME=
NPM_DB_MYSQL_USER=
NPM_DB_MYSQL_PASSWORD=
EOF
            print_success "Using built-in SQLite database"
        fi
        
        # IPv6 configuration
        echo ""
        echo -n "Disable IPv6 support? (y/N): "
        read ipv6_choice
        
        if [[ "$ipv6_choice" =~ ^[Yy]$ ]]; then
            echo "NPM_DISABLE_IPV6=true" >> "./docker/.env"
            print_success "IPv6 disabled"
        else
            echo "NPM_DISABLE_IPV6=" >> "./docker/.env"
            print_success "IPv6 enabled"
        fi
        
        echo ""
        print_info "Nginx Proxy Manager will be available at:"
        echo "  Admin Panel: http://localhost:81"
        echo "  Default login: admin@example.com / changeme"
        ;;
esac

cd ..
echo ""
print_success "Proxy configuration complete!"
echo "Configuration saved to ./docker/.env"